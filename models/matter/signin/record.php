<?php
namespace matter\signin;
/**
 * 签到记录
 */
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
		$options = [
			'fields' => 'enroll_key',
			'cascaded' => 'N',
		];
		if ($userRecord = $this->byUser($user, $siteId, $app, $options)) {
			// 已经登记过，用现有的登记记录
			$ek = $userRecord->enroll_key;
		} else {
			// 没有登记过，产生一条新的登记记录
			$ek = $this->genKey($siteId, $app->id);
			$record = [
				'siteid' => $siteId,
				'aid' => $app->id,
				'enroll_at' => $enrollAt === null ? time() : $enrollAt,
				'enroll_key' => $ek,
				'userid' => $user->uid,
				'nickname' => $user->nickname,
				'referrer' => $referrer,
			];
			$this->insert('xxt_signin_record', $record, false);
		}

		return $ek;
	}
	/**
	 * 签到
	 *
	 * 执行签到，在每个轮次上只能进行一次签到，第一次签到后再提交也不会更改签到时间等信息
	 * 如果用户已经做过活动登记，那么设置签到时间
	 * 如果用户没有做个活动登记，那么要先产生一条登记记录，并记录签到时间
	 */
	public function &signin(&$user, $siteId, &$app) {
		$state = new \stdClass;

		if ($record = $this->byUser($user, $siteId, $app)) {
			// 已经登记过，不需要再登记
			$ek = $record->enroll_key;
			$state->enrolled = true;
		} else {
			// 没有登记过，先登记
			$ek = $this->enroll($user, $siteId, $app);
			$state->enrolled = false;
		}
		/**
		 * 执行签到，在每个轮次上只能进行一次签到，第一次签到后再提交也不会更改签到时间等信息
		 */
		$activeRound = \TMS_APP::M('matter\signin\round')->getActive($siteId, $app->id);
		if (!$this->userSigned($user, $siteId, $app, $activeRound)) {
			// 记录签到日志
			$signinAt = time();
			$this->insert(
				'xxt_signin_log',
				[
					'siteid' => $siteId,
					'aid' => $app->id,
					'rid' => $activeRound->rid,
					'enroll_key' => $ek,
					'userid' => $user->uid,
					'nickname' => $user->nickname,
					'signin_at' => $signinAt,
				],
				false
			);
			// 记录签到摘要
			$record = $this->byId($ek);
			$signinLog = $record->signin_log;
			$signinLog->{$activeRound->rid} = $signinAt;
			$signinLog = $this->toJson($signinLog);
			// 更新状态
			$sql = "update xxt_signin_record set signin_at=$signinAt,signin_num=signin_num+1,signin_log='$signinLog'";
			$sql .= " where aid='{$app->id}' and enroll_key='$ek'";
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
			$q = [
				'*',
				'xxt_signin_log',
				"aid='{$app->id}' and rid='{$round->rid}' and userid='{$user->uid}' and state=1",
			];
			$log = $this->query_obj_ss($q);
		}

		return $log;
	}
	/**
	 * 保存登记的数据
	 */
	public function setData(&$user, $siteId, &$app, $ek, $data, $submitkey = '') {
		if (empty($data)) {
			return [true];
		}
		if (empty($submitkey)) {
			$submitkey = $user->uid;
		}
		// 处理后的登记记录
		$dbData = new \stdClass;

		// 清空已有的登记数据
		$this->delete('xxt_signin_record_data', "aid='{$app->id}' and enroll_key='$ek'");

		foreach ($data as $n => $v) {
			/**
			 * 插入自定义属性
			 */
			if ($n === 'member' && is_object($v)) {
				//
				$dbData->{$n} = $v;
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
			} elseif (is_array($v) && (isset($v[0]->serverId) || isset($v[0]->imgSrc))) {
				/* 上传图片 */
				$vv = [];
				$fsuser = \TMS_APP::model('fs/user', $siteId);
				foreach ($v as $img) {
					$rst = $fsuser->storeImg($img);
					if (false === $rst[0]) {
						return $rst;
					}
					$vv[] = $rst[1];
				}
				$vv = implode(',', $vv);
				$dbData->{$n} = $vv;
			} elseif (is_array($v) && isset($v[0]->uniqueIdentifier)) {
				/* 上传文件 */
				$fsUser = \TMS_APP::M('fs/local', $siteId, '_user');
				$fsResum = \TMS_APP::M('fs/local', $siteId, '_resumable');
				$fsAli = \TMS_APP::M('fs/alioss', $siteId);
				$vv = [];
				foreach ($v as $file) {
					if (defined('SAE_TMP_PATH')) {
						$dest = '/' . $app->id . '/' . $submitkey . '_' . $file->name;
						$fileUploaded2 = $fsAli->getBaseURL() . $dest;
					} else {
						$fileUploaded = $fsResum->rootDir . '/' . $submitkey . '_' . $file->uniqueIdentifier;
						!file_exists($fsUser->rootDir . '/' . $submitkey) && mkdir($fsUser->rootDir . '/' . $submitkey, 0777, true);
						$fileUploaded2 = $fsUser->rootDir . '/' . $submitkey . '/' . $file->name;
						if (false === rename($fileUploaded, $fileUploaded2)) {
							return [false, '移动上传文件失败'];
						}
					}
					unset($file->uniqueIdentifier);
					$file->url = $fileUploaded2;
					$vv[] = $file;
				}
				$vv = json_encode($vv);
				//
				$dbData->{$n} = $vv;
			} else {
				if (is_string($v)) {
					$vv = $this->escape($v);
				} elseif (is_object($v) || is_array($v)) {
					$vv = implode(',', array_keys(array_filter((array) $v, function ($i) {return $i;})));
				} else {
					$vv = $v;
				}
				//
				$dbData->{$n} = $vv;
			}
			// 记录数据
			$ic = [
				'aid' => $app->id,
				'enroll_key' => $ek,
				'name' => $n,
				'value' => $vv,
			];
			$this->insert('xxt_signin_record_data', $ic, false);
		}
		// 记录数据
		$dbData = $this->toJson($dbData);
		$this->update(
			'xxt_signin_record',
			['enroll_at' => time(), 'data' => $dbData],
			"enroll_key='$ek'"
		);

		return [true, $dbData];
	}
	/**
	 * 根据ID返回登记记录
	 */
	public function byId($ek, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_signin_record',
			["enroll_key" => $ek],
		];
		if ($record = $this->query_obj_ss($q)) {
			if ($fields === '*' || strpos($fields, 'data') !== false) {
				$record->data = empty($record->data) ? new \stdClass : json_decode($record->data);
			}
			if ($fields === '*' || strpos($fields, 'signin_log') !== false) {
				$record->signin_log = empty($record->signin_log) ? new \stdClass : json_decode($record->signin_log);
			}
		}

		return $record;
	}
	/**
	 * 获得用户的登记记录
	 */
	public function &byUser(&$user, $siteId, &$app, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';

		$q = [
			$fields,
			'xxt_signin_record',
			["state" => 1, "aid" => $app->id, "userid" => $user->uid],
		];
		if ($userRecord = $this->query_obj_ss($q)) {
			$userRecord->data = empty($userRecord->data) ? new \stdClass : json_decode($userRecord->data);
			$userRecord->signin_log = empty($userRecord->signin_log) ? new \stdClass : json_decode($userRecord->signin_log);
		}

		return $userRecord;
	}
	/**
	 * 生成活动登记的key
	 */
	public function genKey($siteId, $appId) {
		return md5(uniqid() . $siteId . $appId);
	}
	/**
	 * 清除一条用户记录
	 *
	 * @param string $appId
	 * @param string $ek
	 *
	 */
	public function remove($appId, $ek, $byDelete = false) {
		if ($byDelete) {
			$rst = $this->delete(
				'xxt_signin_log',
				["aid" => $appId, "enroll_key" => $ek]
			);
			$rst = $this->delete(
				'xxt_signin_record_data',
				["aid" => $appId, "enroll_key" => $ek]
			);
			$rst = $this->delete(
				'xxt_signin_record',
				["aid" => $appId, "enroll_key" => $ek]
			);
		} else {
			$rst = $this->update(
				'xxt_signin_log',
				['state' => 100],
				["aid" => $appId, "enroll_key" => $ek]
			);
			$rst = $this->update(
				'xxt_signin_record_data',
				['state' => 100],
				["aid" => $appId, "enroll_key" => $ek]
			);
			$rst = $this->update(
				'xxt_signin_record',
				['state' => 100],
				["aid" => $appId, "enroll_key" => $ek]
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
				["aid" => $appId]
			);
			$rst = $this->delete(
				'xxt_signin_record_data',
				["aid" => $appId]
			);
			$rst = $this->delete(
				'xxt_signin_record',
				["aid" => $appId]
			);
		} else {
			$rst = $this->update(
				'xxt_signin_log',
				['state' => 0],
				["aid" => $appId]
			);
			$rst = $this->update(
				'xxt_signin_record_data',
				['state' => 0],
				["aid" => $appId]
			);
			$rst = $this->update(
				'xxt_signin_record',
				['state' => 0],
				["aid" => $appId]
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
	public function find($siteId, &$app, $options = null, $criteria = null) {
		if ($options) {
			is_array($options) && $options = (object) $options;
			$creater = isset($options->creater) ? $options->creater : null;
			$orderby = isset($options->orderby) ? $options->orderby : '';
			$page = isset($options->page) ? $options->page : null;
			$size = isset($options->size) ? $options->size : null;
			$roundId = isset($options->rid) ? $options->rid : null;
		}

		$result = new \stdClass; // 返回的结果
		$result->total = 0;
		/* 获得登记数据 */
		$w = "e.state=1 and e.aid='{$app->id}'";

		if (!empty($creater)) {
			$w .= " and e.userid='$creater'";
		}

		// 签到轮次
		if (!empty($roundId)) {
			$w .= ' and exists(select 1 from xxt_signin_log l';
			$w .= " where l.rid='$roundId' and l.enroll_key=e.enroll_key";
			$w .= ')';
		}

		// 指定了登记记录过滤条件
		if (!empty($criteria->record)) {
			$whereByRecord = '';
			if (!empty($criteria->record->verified)) {
				$whereByRecord .= " and verified='{$criteria->record->verified}'";
			}
			$w .= $whereByRecord;
		}

		// 指定了记录标签
		if (!empty($criteria->tags)) {
			$whereByTag = '';
			foreach ($criteria->tags as $tag) {
				$whereByTag .= " and concat(',',e.tags,',') like '%,$tag,%'";
			}
			$w .= $whereByTag;
		}

		// 指定了登记数据过滤条件
		if (isset($criteria->data)) {
			$whereByData = '';
			foreach ($criteria->data as $k => $v) {
				if (!empty($v)) {
					$whereByData .= ' and (';
					$whereByData .= 'data like \'%"' . $k . '":"' . $v . '"%\'';
					$whereByData .= ' or data like \'%"' . $k . '":"%,' . $v . '"%\'';
					$whereByData .= ' or data like \'%"' . $k . '":"%,' . $v . ',%"%\'';
					$whereByData .= ' or data like \'%"' . $k . '":"' . $v . ',%"%\'';
					$whereByData .= ')';
				}
			}
			$w .= $whereByData;
		}

		// 查询参数
		$q = [
			'e.enroll_key,e.enroll_at,e.signin_at,e.signin_num,e.signin_log,e.userid,e.nickname,e.verified,e.comment,e.tags,e.data',
			'xxt_signin_record e',
			$w,
		];
		$q2 = [
			'r' => ['o' => ($page - 1) * $size, 'l' => $size],
			'o' => 'e.signin_at desc',
		];

		// 处理查询结果
		if ($records = $this->query_objs_ss($q, $q2)) {
			foreach ($records as &$r) {
				// 签到日志
				$r->signin_log = json_decode($r->signin_log);
				// 登记信息
				$data = str_replace("\n", ' ', $r->data);
				$data = json_decode($data);
				if ($data === null) {
					$r->data = 'json error(' . json_last_error() . '):' . $r->data;
				} else {
					$r->data = $data;
				}
			}
			$result->records = $records;
			/* total */
			$q[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q);
			$result->total = $total;
		}

		return $result;
	}
	/**
	 * 签到情况统计
	 */
	public function &summary($siteId, $appId) {
		$summary = [];

		$modelRnd = \TMS_APP::M('matter\signin\round');
		$rounds = $modelRnd->byApp($appId, ['fields' => 'rid,title,start_at,end_at,late_at']);

		if (empty($rounds)) {

		} else {
			$activeRound = $modelRnd->getActive($siteId, $appId);
			foreach ($rounds as $round) {
				/* total */
				$q = [
					'count(*)',
					'xxt_signin_log',
					['aid' => $appId, 'state' => 1, 'rid' => $round->rid],
				];
				$round->total = $this->query_val_ss($q);
				/* late */
				if ($round->total) {
					if ($round->late_at) {
						$q = [
							'count(*)',
							'xxt_signin_log',
							"aid='" . $this->escape($appId) . "' and rid='{$round->rid}' and state=1 and signin_at>" . ((int) $round->late_at + 59),
						];
						$round->late = $this->query_val_ss($q);
					} else {
						$round->late = 0;
					}
				} else {
					$round->late = 0;
				}
				if ($activeRound && $round->rid === $activeRound->rid) {
					$round->active = 'Y';
				}

				$summary[] = $round;
			}
		}

		return $summary;
	}
}