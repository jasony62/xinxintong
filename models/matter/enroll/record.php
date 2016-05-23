<?php
namespace matter\enroll;

class record_model extends \TMS_MODEL {
	/**
	 * 活动登记（不包括登记数据）
	 *
	 * @param string $siteId
	 * @param string $app
	 * @param object $user
	 * @param int $enroll_at
	 */
	public function enroll($siteId, &$app, &$user, $enroll_at = null, $referrer = '') {
		$ek = $this->genKey($siteId, $app->id);
		$record = array(
			'aid' => $app->id,
			'siteid' => $siteId,
			'mpid' => $siteId,
			'enroll_at' => $enroll_at === null ? time() : $enroll_at,
			'enroll_key' => $ek,
			'userid' => $user->uid,
			'referrer' => $referrer,
		);
		/*记录所属轮次*/
		$modelRun = \TMS_APP::M('matter\enroll\round');
		if ($activeRound = $modelRun->getActive($siteId, $app->id)) {
			$record['rid'] = $activeRound->rid;
		}
		/*登记用户昵称*/
		$entryRule = $app->entry_rule;
		if (isset($entryRule->scope) && $entryRule->scope === 'member') {
			foreach ($entryRule->member as $schemaId => $rule) {
				if (isset($user->members->{$schemaId})) {
					$record['nickname'] = $user->members->{$schemaId}->name;
					break;
				}
			}
		} else if (isset($entryRule->scope) && $entryRule->scope === 'sns') {
			foreach ($entryRule->sns as $snsName => $rule) {
				if (isset($user->sns->{$snsName})) {
					$record['nickname'] = $user->sns->{$snsName}->nickname;
					break;
				}
			}
		} else {
			$record['nickname'] = $user->nickname;
		}

		$this->insert('xxt_enroll_record', $record, false);

		return $ek;
	}
	/**
	 * 保存登记的数据
	 */
	public function setData($user, $siteId, &$app, $ek, $data, $submitkey = '') {
		if (empty($data)) {
			return array(true);
		}
		if (empty($submitkey)) {
			$submitkey = $user->uid;
		}
		// 已有的登记数据
		$q = array(
			'name',
			'xxt_enroll_record_data',
			"aid='{$app->id}' and enroll_key='$ek'",
		);
		$fields = $this->query_vals_ss($q);
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
					'xxt_enroll_record_data',
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
				$this->insert('xxt_enroll_record_data', $ic, false);
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
			'xxt_enroll_record',
			"enroll_key='$ek'",
		);
		if (($record = $this->query_obj_ss($q)) && $cascaded === 'Y') {
			$record->data = $this->dataById($ek);
		}

		return $record;
	}
	/**
	 * 获得用户的登记清单
	 */
	public function byUser($siteId, $aid, $openid, $rid = null) {
		if (empty($openid)) {
			return false;
		}

		$q = array(
			'*',
			'xxt_enroll_record',
			"state=1 and mpid='$siteId' and aid='$aid' and openid='$openid'",
		);
		if (empty($rid)) {
			$modelRun = \TMS_APP::M('matter\enroll\round');
			if ($activeRound = $modelRun->getActive($siteId, $aid)) {
				$q[2] .= " and rid='$activeRound->rid'";
			}
		} else {
			$q[2] .= " and rid='$rid'";
		}
		$q2 = array('o' => 'enroll_at desc');

		$list = $this->query_objs_ss($q, $q2);

		return $list;
	}
	/**
	 * 根据指定的数据查找匹配的记录
	 */
	public function &byData($siteId, &$app, &$data) {
		$matchedRecords = array();
		/*需要匹配的条件*/
		$conditions = array();
		foreach ($data as $key => $val) {
			$conditions[] = "(name='$key' and value='$val')";
		}
		if (empty($conditions)) {
			return $matchedRecords;
		}
		/*需要匹配的条件的数量*/
		$countOfConditions = count($conditions);
		/*将条件转换为SQL*/
		$conditions = '(' . implode(' or ', $conditions) . ')';
		/*查找匹配条件的数据*/
		$q = array(
			'enroll_key',
			'xxt_enroll_record_data',
			"state=1 and aid='{$app->id}' and $conditions",
		);
		/*记录每条记录匹配的次数*/
		$mapOfCount = new \stdClass;
		$pendings = $this->query_objs_ss($q);
		foreach ($pendings as &$pending) {
			if (isset($mapOfCount->{$pending->enroll_key})) {
				$mapOfCount->{$pending->enroll_key} += 1;
			} else {
				$mapOfCount->{$pending->enroll_key} = 1;
			}
			if ($mapOfCount->{$pending->enroll_key} === $countOfConditions) {
				$matchedRecords[] = $pending->enroll_key;
			}
		}

		return $matchedRecords;
	}
	/**
	 * 登记清单
	 *
	 * 1、如果活动仅限会员报名，那么要叠加会员信息
	 * 2、如果报名的表单中有扩展信息，那么要提取扩展信息
	 *
	 * $siteId
	 * $aid
	 * $options
	 * --creater openid
	 * --visitor openid
	 * --page
	 * --size
	 * --rid 轮次id
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
			$inviter = isset($options->inviter) ? $options->inviter : null;
			$orderby = isset($options->orderby) ? $options->orderby : '';
			$page = isset($options->page) ? $options->page : null;
			$size = isset($options->size) ? $options->size : null;
			$rid = null;
			if (!empty($options->rid)) {
				if ($options->rid === 'ALL') {
					$rid = null;
				} else if (!empty($options->rid)) {
					$rid = $options->rid;
				}
			} else if ($activeRound = $this->M('matter\enroll\round')->getActive($siteId, $app->id)) {
				$rid = $activeRound->rid;
			}
			$kw = isset($options->kw) ? $options->kw : null;
			$by = isset($options->by) ? $options->by : null;
		}
		$result = new \stdClass; // 返回的结果
		$result->total = 0;
		/* 获得登记数据 */
		$w = "e.state=1 and e.siteid='$siteId' and e.aid='{$app->id}'";
		if (!empty($creater)) {
			$w .= " and e.userid='$creater'";
		} else if (!empty($inviter)) {
			$user = new \stdClass;
			$user->openid = $inviter;
			$inviterek = $this->getLastKey($siteId, $aid, $user);
			$w .= " and e.referrer='ek:$inviterek'";
		}
		!empty($rid) && $w .= " and e.rid='$rid'";
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
		/*tags*/
		if (!empty($options->tags)) {
			$aTags = explode(',', $options->tags);
			foreach ($aTags as $tag) {
				$w .= "and concat(',',e.tags,',') like '%,$tag,%'";
			}
		}
		$q = array(
			'e.enroll_key,e.enroll_at,e.tags,e.follower_num,e.score,e.remark_num,e.userid,e.nickname,e.verified',
			"xxt_enroll_record e",
			$w,
		);
		$q2 = array(
			'r' => array('o' => ($page - 1) * $size, 'l' => $size),
		);
		switch ($orderby) {
		case 'time':
			$q2['o'] = 'e.enroll_at desc';
			break;
		case 'score':
			$q2['o'] = 'e.score desc';
			break;
		case 'remark':
			$q2['o'] = 'e.remark_num desc';
			break;
		case 'follower':
			$q2['o'] = 'e.follower_num desc';
			break;
		default:
			$q2['o'] = 'e.enroll_at desc';
		}
		if ($records = $this->query_objs_ss($q, $q2)) {
			foreach ($records as &$r) {
				/* 获得填写的登记数据 */
				$qc = array(
					'name,value',
					'xxt_enroll_record_data',
					"enroll_key='$r->enroll_key'",
				);
				$cds = $this->query_objs_ss($qc);
				$r->data = new \stdClass;
				foreach ($cds as $cd) {
					$r->data->{$cd->name} = $cd->value;
				}
				/*获得点赞记录*/
				$app->can_like_record === 'Y' && $r->likers = $this->likers($r->enroll_key, 1, 3);
				/*获得邀请数据*/
				if ($app->can_invite === 'Y') {
					$qf = array(
						'id,enroll_key,enroll_at,openid,nickname',
						'xxt_enroll_record',
						"aid='$aid' and referrer='ek:$r->enroll_key'",
					);
					$qf2 = array('o' => 'enroll_at');
					$r->followers = $this->query_objs_ss($qf, $qf2);
				}
				/*获得关联抽奖活动记录*/
				$ql = array(
					'award_title',
					'xxt_lottery_log',
					"enroll_key='$r->enroll_key'",
				);
				$lotteryResult = $this->query_objs_ss($ql);
				if (!empty($lotteryResult)) {
					$lrs = array();
					foreach ($lotteryResult as $lr) {
						$lrs[] = $lr->award_title;
					}
					$r->data->lotteryResult = implode(',', $lrs);
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
	 * 获得指定用户最后一次登记记录
	 * 如果设置轮次，只返回当前轮次的情况
	 */
	public function getLast($siteId, $app, $user, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = array(
			$fields,
			'xxt_enroll_record',
			"siteid='$siteId' and aid='{$app->id}' and state=1",
		);
		$q[2] .= " and userid='{$user->uid}'";
		if ($activeRound = \TMS_APP::M('matter\enroll\round')->getActive($siteId, $app->id)) {
			$q[2] .= " and rid='$activeRound->rid'";
		}
		$q2 = array(
			'o' => 'enroll_at desc',
			'r' => array('o' => 0, 'l' => 1),
		);
		$records = $this->query_objs_ss($q, $q2);

		return count($records) === 1 ? $records[0] : false;
	}
	/**
	 * 获得指定用户最后一次登记的key
	 * 如果设置轮次，只检查当前轮次的情况
	 *
	 * @param string $siteId
	 * @param object $app
	 * @param object $user
	 *
	 */
	public function getLastKey($siteId, &$app, &$user) {
		$last = $this->getLast($siteId, $app, $user);

		return $last ? $last->enroll_key : false;
	}
	/**
	 *
	 */
	public function hasAcceptedInvite($aid, $openid, $ek) {
		$q = array(
			'enroll_key',
			'xxt_enroll_record',
			"aid='$aid' and openid='$openid' and referrer='ek:$ek'",
		);
		$records = $this->query_objs_ss($q);
		if (empty($records)) {
			return false;
		} else {
			return $records[0]->enroll_key;
		}
	}
	/**
	 * 获得一条登记记录的数据
	 */
	public function dataById($ek) {
		$q = array(
			'name,value',
			'xxt_enroll_record_data',
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
	 * 返回登记人
	 */
	public function &enrollers($aid, $rid = '', $page = 1, $size = 30) {
		$w = "aid='$aid' and state=1";
		!empty($rid) && $w .= " and rid='$rid'";
		$q = array(
			'distinct openid,nickname',
			'xxt_enroll_record',
			$w,
		);
		$enrollers = $this->query_objs_ss($q);

		$result = array(
			'enrollers' => $enrollers,
		);

		return $result;
	}
	/**
	 * 生成活动登记的key
	 */
	public function genKey($siteId, $aid) {
		return md5(uniqid() . $siteId . $aid);
	}
	/**
	 *
	 */
	public function modify($ek, $data) {
		$rst = $this->update(
			'xxt_enroll_record',
			$data,
			"enroll_key='$ek'"
		);
		return $rst;
	}
	/**
	 * 清除一条登记记录
	 *
	 * @param string $aid
	 * @param string $ek
	 */
	public function removeByUser($site, $appId, $ek) {
		$rst = $this->update(
			'xxt_enroll_record_data',
			array('state' => 0),
			"aid='$appId' and enroll_key='$ek'"
		);
		$rst = $this->update(
			'xxt_enroll_record',
			array('state' => 0),
			"aid='$appId' and enroll_key='$ek'"
		);

		return $rst;
	}
	/**
	 * 清除一条登记记录
	 *
	 * @param string $aid
	 * @param string $ek
	 */
	public function remove($appId, $ek, $byDelete = false) {
		if ($byDelete) {
			$rst = $this->delete(
				'xxt_enroll_record_data',
				"aid='$appId' and enroll_key='$ek'"
			);
			$rst = $this->delete(
				'xxt_enroll_record',
				"aid='$appId' and enroll_key='$ek'"
			);
		} else {
			$rst = $this->update(
				'xxt_enroll_record_data',
				array('state' => 100),
				"aid='$appId' and enroll_key='$ek'"
			);
			$rst = $this->update(
				'xxt_enroll_record',
				array('state' => 100),
				"aid='$appId' and enroll_key='$ek'"
			);
		}

		return $rst;
	}
	/**
	 * 清除登记记录
	 *
	 * @param string $appId
	 */
	public function clean($appId, $byDelete = false) {
		if ($byDelete) {
			$rst = $this->delete(
				'xxt_enroll_record_data',
				"aid='$appId'"
			);
			$rst = $this->delete(
				'xxt_enroll_record',
				"aid='$appId'"
			);
		} else {
			$rst = $this->update(
				'xxt_enroll_record_data',
				array('state' => 0),
				"aid='$appId'"
			);
			$rst = $this->update(
				'xxt_enroll_record',
				array('state' => 0),
				"aid='$appId'"
			);
		}

		return $rst;
	}
}