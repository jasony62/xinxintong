<?php
namespace matter\signin;

class record_model extends \TMS_MODEL {
	/**
	 * 活动登记（不包括登记数据）
	 *
	 * @param string $siteId
	 * @param string $app
	 * @param object $user
	 * @param int $enrollAt
	 *
	 */
	public function enroll(&$user, $siteId, &$app, $enrollAt = null, $referrer = '') {
		$options = array(
			'fields' => 'enroll_key',
			'cascaded' => 'N',
		);
		if ($userRecord = $this->byUser($user, $siteId, $app, $options)) {
			/*不允许多次登记*/
			$ek = $userRecord->enroll_key;
		} else {
			$ek = $this->genKey($siteId, $app->id);
			$record = array(
				'siteid' => $siteId,
				'aid' => $app->id,
				'enroll_at' => $enrollAt === null ? time() : $enrollAt,
				'enroll_key' => $ek,
				'userid' => $user->uid,
				'nickname' => $user->nickname,
				'referrer' => $referrer,
			);
			$this->insert('xxt_signin_record', $record, false);
		}

		return $ek;
	}
	/**
	 * 签到
	 *
	 * 如果用户已经做过活动登记，那么设置签到时间
	 * 如果用户没有做个活动登记，那么要先产生一条登记记录，并记录签到时间
	 */
	public function &signin(&$user, $siteId, &$app) {
		$state = new \stdClass;
		/* 如果当前用户没有登记过，就先签到后登记 */
		if ($record = $this->byUser($user, $siteId, $app)) {
			$ek = $record->enroll_key;
			$state->enrolled = true;
		} else {
			$ek = $this->enroll($user, $siteId, $app);
			$state->enrolled = true;
		}
		/* 执行签到 */
		$activeRound = \TMS_APP::M('matter\signin\round')->getActive($siteId, $app->id);
		if (!$this->userSigned($user, $siteId, $app, $activeRound)) {
			/* 记录日志 */
			$signinAt = time();
			$this->insert(
				'xxt_signin_log',
				array(
					'siteid' => $siteId,
					'aid' => $app->id,
					'rid' => $activeRound->rid,
					'enroll_key' => $ek,
					'userid' => $user->uid,
					'nickname' => $user->nickname,
					'signin_at' => $signinAt,
				),
				false
			);
			/* 更新状态 */
			$sql = "update xxt_signin_record set signin_at=$signinAt,signin_num=signin_num+1";
			$sql .= " where siteid='$siteId' and aid='{$app->id}' and enroll_key='$ek'";
			$rst = $this->update($sql);
			$state->signed = false;
		} else {
			$state->signed = true;
		}
		$state->ek = $ek;

		return $state;
	}
	/**
	 * 检查用户在指定轮次是否已经签到
	 */
	public function &userSigned(&$user, $siteId, &$app, &$round = null) {
		$log = false;
		if (empty($round)) {
			$round = \TMS_APP::M('matter\signin\round')->getActive($siteId, $app->id);
		}
		if ($round) {
			$q = array(
				'*',
				'xxt_signin_log',
				"siteid='$siteId' and aid='{$app->id}' and rid='{$round->rid}' and userid='{$user->uid}'",
			);
			$log = $this->query_obj_ss($q);
		}
		return $log;
	}
	/**
	 * 保存登记的数据
	 */
	public function setData(&$user, $siteId, &$app, $ek, $data, $submitkey = '') {
		if (empty($data)) {
			return array(true);
		}
		if (empty($submitkey)) {
			$submitkey = $user->uid;
		}
		// 已有的登记数据
		$fields = $this->query_vals_ss(array('name', 'xxt_signin_record_data', "aid='{$app->id}' and enroll_key='$ek'"));
		foreach ($data as $n => $v) {
			/**
			 * 插入自定义属性
			 */
			if ($n === 'member' && is_object($v)) {
				/* 用户认证信息 */
				$vv = new \stdClass;
				isset($v->name) && $vv->name = urlencode($v->name);
				isset($v->email) && $vv->email = urlencode($v->email);
				isset($v->mobile) && $vv->mobile = urlencode($v->mobile);
				if (!empty($v->extattr)) {
					$extattr = new \stdClass;
					foreach ($v->extattr as $mek => $mev) {
						$extattr->{$mek} = urlencode($mev);
					}
					$vv->extattr = $extattr;
				}
				$vv = urldecode(json_encode($vv));
			} else if (is_array($v) && (isset($v[0]->serverId) || isset($v[0]->imgSrc))) {
				/* 上传图片 */
				$vv = array();
				$fsuser = \TMS_APP::model('fs/user', $siteId);
				foreach ($v as $img) {
					$rst = $fsuser->storeImg($img);
					if (false === $rst[0]) {
						return $rst;
					}
					$vv[] = $rst[1];
				}
				$vv = implode(',', $vv);
			} else if (is_array($v) && isset($v[0]->uniqueIdentifier)) {
				/* 上传文件 */
				$fsUser = \TMS_APP::M('fs/local', $siteId, '_user');
				$fsResum = \TMS_APP::M('fs/local', $siteId, '_resumable');
				$fsAli = \TMS_APP::M('fs/alioss', $siteId);
				$vv = array();
				foreach ($v as $file) {
					if (defined('SAE_TMP_PATH')) {
						$dest = '/' . $app->id . '/' . $submitkey . '_' . $file->name;
						$fileUploaded2 = $fsAli->getBaseURL() . $dest;
					} else {
						$fileUploaded = $fsResum->rootDir . '/' . $submitkey . '_' . $file->uniqueIdentifier;
						!file_exists($fsUser->rootDir . '/' . $submitkey) && mkdir($fsUser->rootDir . '/' . $submitkey, 0777, true);
						$fileUploaded2 = $fsUser->rootDir . '/' . $submitkey . '/' . $file->name;
						if (false === rename($fileUploaded, $fileUploaded2)) {
							return array(false, '移动上传文件失败');
						}
					}
					unset($file->uniqueIdentifier);
					$file->url = $fileUploaded2;
					$vv[] = $file;
				}
				$vv = json_encode($vv);
			} else {
				if (is_string($v)) {
					$vv = $this->escape($v);
				} else if (is_object($v) || is_array($v)) {
					$vv = implode(',', array_keys(array_filter((array) $v, function ($i) {return $i;})));
				} else {
					$vv = $v;
				}
			}
			if (!empty($fields) && in_array($n, $fields)) {
				$this->update(
					'xxt_signin_record_data',
					array('value' => $vv),
					"aid='{$app->id}' and enroll_key='$ek' and name='$n'"
				);
				unset($fields[array_search($n, $fields)]);
			} else {
				$ic = array(
					'aid' => $app->id,
					'enroll_key' => $ek,
					'name' => $n,
					'value' => $vv,
				);
				$this->insert('xxt_signin_record_data', $ic, false);
			}
		}

		return array(true);
	}
	/**
	 * 根据ID返回登记记录
	 */
	public function byId($ek, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';

		$q = array(
			$fields,
			'xxt_signin_record',
			"enroll_key='$ek'",
		);
		if (($record = $this->query_obj_ss($q)) && $cascaded === 'Y') {
			$record->data = $this->dataById($ek);
		}

		return $record;
	}

	/**
	 * 获得用户的登记记录
	 */
	public function &byUser(&$user, $siteId, &$app, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';

		$q = array(
			$fields,
			'xxt_signin_record',
			"state=1 and siteid='$siteId' and aid='{$app->id}' and userid='{$user->uid}'",
		);
		if ($userRecord = $this->query_obj_ss($q)) {
			if ($cascaded === 'Y') {
				/* 登记记录有可能未进行过登记 */
				if ($userRecord->enroll_at) {
					$userRecord->data = $this->dataById($userRecord->enroll_key);
				}
				if ($userRecord->signin_at) {
					$userRecord->signinlogs = $this->signinLogByUser($user, $siteId, $app);
				}
			}
		}

		return $userRecord;
	}
	/**
	 * 获得一条登记记录的数据
	 */
	public function dataById($ek) {
		$q = array(
			'name,value',
			'xxt_signin_record_data',
			"enroll_key='$ek'",
		);
		$cusdata = array();
		$cdata = $this->query_objs_ss($q);
		if (count($cdata) > 0) {
			foreach ($cdata as $cd) {
				$cusdata[$cd->name] = $cd->value;
			}
		}

		return $cusdata;
	}
	/**
	 * 用户的签到记录
	 */
	public function signinLogByUser(&$user, $siteId, &$app, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : 'signin_at';

		$q = array(
			$fields,
			'xxt_signin_log',
			"siteid='$siteId' and aid='{$app->id}' and userid='{$user->uid}'",
		);
		$q2 = array('o' => 'signin_at desc');

		$logs = $this->query_objs_ss($q, $q2);

		return $logs;
	}
	/**
	 * 生成活动登记的key
	 */
	public function genKey($siteId, $appId) {
		return md5(uniqid() . $siteId . $appId);
	}
	/**
	 *
	 */
	public function modify($ek, $data) {
		$rst = $this->update(
			'xxt_signin_record',
			$data,
			"enroll_key='$ek'"
		);
		return $rst;
	}
	/**
	 * 清除一条用户记录
	 *
	 * @param string $appId
	 * @param string $ek
	 */
	public function remove($appId, $ek, $byDelete = false) {
		if ($byDelete) {
			$rst = $this->delete(
				'xxt_signin_log',
				"aid='$appId' and enroll_key='$ek'"
			);
			$rst = $this->delete(
				'xxt_signin_record_data',
				"aid='$appId' and enroll_key='$ek'"
			);
			$rst = $this->delete(
				'xxt_signin_record',
				"aid='$appId' and enroll_key='$ek'"
			);
		} else {
			$rst = $this->update(
				'xxt_signin_log',
				array('state' => 0),
				"aid='$appId' and enroll_key='$ek'"
			);
			$rst = $this->update(
				'xxt_signin_record_data',
				array('state' => 0),
				"aid='$appId' and enroll_key='$ek'"
			);
			$rst = $this->update(
				'xxt_signin_record',
				array('state' => 0),
				"aid='$appId' and enroll_key='$ek'"
			);
		}

		return $rst;
	}
	/**
	 * 清除用户记录
	 *
	 * @param string $appId
	 */
	public function clean($appId, $byDelete = false) {
		if ($byDelete) {
			$rst = $this->delete(
				'xxt_signin_log',
				"aid='$appId'"
			);
			$rst = $this->delete(
				'xxt_signin_record_data',
				"aid='$appId'"
			);
			$rst = $this->delete(
				'xxt_signin_record',
				"aid='$appId'"
			);
		} else {
			$rst = $this->update(
				'xxt_signin_log',
				array('state' => 0),
				"aid='$appId'"
			);
			$rst = $this->update(
				'xxt_signin_record_data',
				array('state' => 0),
				"aid='$appId'"
			);
			$rst = $this->update(
				'xxt_signin_record',
				array('state' => 0),
				"aid='$appId'"
			);
		}

		return $rst;
	}
	/**
	 * 登记清单
	 *
	 * 1、如果活动仅限会员报名，那么要叠加会员信息
	 * 2、如果报名的表单中有扩展信息，那么要提取扩展信息
	 *
	 * $siteId
	 * $appId
	 * $options
	 * --creater openid
	 * --page
	 * --size
	 * --kw 检索关键词
	 * --by 检索字段
	 *
	 *
	 * return
	 * [0] 数据列表
	 * [1] 数据总条数
	 * [2] 数据项的定义
	 */
	public function find($siteId, &$app, $options = null) {
		/* 获得活动的定义 */
		if ($options) {
			is_array($options) && $options = (object) $options;
			$creater = isset($options->creater) ? $options->creater : null;
			$orderby = isset($options->orderby) ? $options->orderby : '';
			$page = isset($options->page) ? $options->page : null;
			$size = isset($options->size) ? $options->size : null;
			$signinStartAt = isset($options->signinStartAt) ? $options->signinStartAt : null;
			$signinEndAt = isset($options->signinEndAt) ? $options->signinEndAt : null;
			$kw = isset($options->kw) ? $options->kw : null;
			$by = isset($options->by) ? $options->by : null;
		}
		$result = new \stdClass; // 返回的结果
		$result->total = 0;
		/* 获得登记数据 */
		$w = "e.state=1 and e.siteid='$siteId' and e.aid='{$app->id}'";
		if (!empty($creater)) {
			$w .= " and e.userid='$creater'";
		}
		if (!empty($kw) && !empty($by)) {
			switch ($by) {
			case 'mobile':
				$kw && $w .= " and m.mobile like '%$kw%'";
				break;
			case 'nickname':
				$kw && $w .= " and e.nickname like '%$kw%'";
				break;
			}
		}
		/*签到时间*/
		//if (!empty($signinStartAt) && !empty($signinEndAt)) {
		//	$w .= " and exists(select 1 from xxt_signin_log l";
		//	$w .= " where l.signin_at>=$signinStartAt and l.signin_at<=$signinEndAt and l.enroll_key=e.enroll_key";
		//	$w .= ")";
		//}
		/*tags*/
		if (!empty($options->tags)) {
			$aTags = explode(',', $options->tags);
			foreach ($aTags as $tag) {
				$w .= "and concat(',',e.tags,',') like '%,$tag,%'";
			}
		}
		$q = array(
			'e.enroll_key,e.enroll_at,e.signin_at,e.signin_num,e.userid,e.nickname,e.verified',
			"xxt_signin_record e",
			$w,
		);
		$q2 = array(
			'r' => array('o' => ($page - 1) * $size, 'l' => $size),
			'o' => 'e.signin_at desc',
		);
		if ($records = $this->query_objs_ss($q, $q2)) {
			foreach ($records as &$r) {
				/* 获得填写的登记数据 */
				$qc = array(
					'name,value',
					'xxt_signin_record_data',
					"enroll_key='$r->enroll_key'",
				);
				$cds = $this->query_objs_ss($qc);
				$r->data = new \stdClass;
				foreach ($cds as $cd) {
					$r->data->{$cd->name} = $cd->value;
				}
				$qs = array(
					'signin_at',
					'xxt_signin_log',
					"enroll_key='$r->enroll_key'",
				);
				$qs2 = array('o' => 'signin_at desc');
				$r->signinLogs = $this->query_objs_ss($qs, $qs2);
			}
			$result->records = $records;
			/* total */
			$q[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q);
			$result->total = $total;
		}

		return $result;
	}
}