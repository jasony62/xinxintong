<?php
namespace matter\signin;

require_once dirname(dirname(__FILE__)) . '/enroll/record_base.php';
/**
 * 签到记录
 */
class record_model extends \matter\enroll\record_base {
	/**
	 * 活动登记（不包括登记数据）
	 *
	 * @param string $siteId
	 * @param string $app
	 * @param object $user
	 * @param int $enrollAt
	 *
	 */
	public function enroll(&$oApp, $oUser = null, $data = null) {
		$enrollAt = isset($data['enrollAt']) ? $data['enrollAt'] : time();
		$referrer = isset($data['referrer']) ? $data['referrer'] : '';

		$options = [
			'fields' => 'enroll_key',
			'cascaded' => 'N',
		];
		if (!empty($oUser) && ($oUserRecord = $this->byUser($oUser, $oApp, $options))) {
			// 已经登记过，用现有的登记记录
			$ek = $userRecord->enroll_key;
		} else {
			// 没有登记过，产生一条新的登记记录
			$ek = $this->genKey($oApp->siteid, $oApp->id);
			$record = [
				'siteid' => $oApp->siteid,
				'aid' => $oApp->id,
				'enroll_at' => $enrollAt,
				'enroll_key' => $ek,
				'userid' => empty($oUser->uid) ? '' : $oUser->uid,
				'nickname' => empty($oUser->nickname) ? '' : $this->escape($oUser->nickname),
				'referrer' => $referrer,
			];
			$record['verified'] = isset($data['verified']) ? $data['verified'] : 'N';
			isset($data['verified_enroll_key']) && $record['verified_enroll_key'] = $data['verified_enroll_key'];

			/* 移动用户未签到的原因 */
			if (!empty($oUser->uid)) {
				if (isset($oApp->absent_cause->{$oUser->uid})) {
					$record['comment'] = $this->escape($oApp->absent_cause->{$oUser->uid});
					unset($oApp->absent_cause->{$oUser->uid});
					/* 更新原未签到记录 */
					$newAbsentCause = $this->escape($this->toJson($oApp->absent_cause));
					$this->update(
						'xxt_signin',
						['absent_cause' => $newAbsentCause],
						['id' => $oApp->id]
					);
				}
			}

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
	public function &signin(&$oUser, $oApp, $signinData = null) {
		$modelRnd = $this->model('matter\signin\round');
		$modelLog = $this->model('matter\signin\log');
		$oSigninState = new \stdClass;

		if ($record = $this->byUser($oUser, $oApp)) {
			// 已经登记过，不需要再登记
			$ek = $record->enroll_key;
			$oSigninState->enrolled = true;
		} else if ($signinData && ($records = $this->byData($oApp, $signinData)) && count($records) === 1) {
			// 已经有手工添加的记录，不需要再登记
			$ek = $records[0]->enroll_key;
			$this->update(
				'xxt_signin_record',
				['userid' => $oUser->uid, 'nickname' => $this->escape($oUser->nickname)],
				"enroll_key='$ek' and state=1"
			);
			$oSigninState->enrolled = true;
		} else {
			// 没有登记过，先登记
			$ek = $this->enroll($oApp, $oUser);
			$oSigninState->enrolled = false;
		}
		/**
		 * 执行签到，在每个轮次上只能进行一次签到，第一次签到后再提交也不会更改签到时间等信息
		 */
		$oActiveRnd = $modelRnd->getActive($oApp->siteid, $oApp->id);
		if ($oSinginLog = $modelLog->byRecord($ek, $oActiveRnd->rid)) {
			/* 登记记录有对应的签到记录 */
			$oSigninState->signed = true;
			if (!empty($oActiveRnd->late_at)) {
				$oSigninState->late = $oSinginLog->signin_at + 60 > $oActiveRnd->late_at;
			} else {
				$oSigninState->late = false;
			}
			if (empty($oSinginLog->userid) || empty($oSinginLog->nickname)) {
				$this->update(
					'xxt_signin_log',
					['userid' => $oUser->uid, 'nickname' => $this->escape($oUser->nickname)],
					['enroll_key' => $ek, 'rid' => $oActiveRnd->rid, 'state' => 1]
				);
			}
		} else {
			// 记录签到日志
			$signinAt = time();
			$this->insert(
				'xxt_signin_log',
				[
					'siteid' => $oApp->siteid,
					'aid' => $oApp->id,
					'rid' => $oActiveRnd->rid,
					'enroll_key' => $ek,
					'userid' => $oUser->uid,
					'nickname' => $this->escape($oUser->nickname),
					'signin_at' => $signinAt,
				],
				false
			);
			// 记录签到摘要
			$record = $this->byId($ek);
			$signinLog = $record->signin_log;
			$signinLog->{$oActiveRnd->rid} = $signinAt;
			$signinLog = $this->toJson($signinLog);
			// 更新状态
			$sql = "update xxt_signin_record set signin_at=$signinAt,signin_num=signin_num+1,signin_log='$signinLog'";
			$sql .= " where aid='{$oApp->id}' and enroll_key='$ek'";
			$rst = $this->update($sql);

			$oSigninState->signed = false;
			if (!empty($oActiveRnd->late_at)) {
				$oSigninState->late = $signinAt + 60 > $oActiveRnd->late_at;
			} else {
				$oSigninState->late = false;
			}
		}

		$oSigninState->ek = $ek;

		return $oSigninState;
	}
	/**
	 * 检查用户在指定轮次是否已经签到
	 *
	 * 1个用户在1个轮次上只有1条签到记录
	 * 1条登记记录在1个轮次上只对应1条签到记录
	 *
	 */
	public function &userSigned(&$oUser, &$oApp, &$oRound = null) {
		$log = false;
		if (empty($oRound)) {
			$oRound = $this->model('matter\signin\round')->getActive($oApp->siteid, $oApp->id);
		}
		if ($oRound) {
			$q = [
				'*',
				'xxt_signin_log',
				"aid='{$oApp->id}' and rid='{$oRound->rid}' and userid='{$oUser->uid}' and state=1",
			];
			$log = $this->query_obj_ss($q);
		}

		return $log;
	}
	/**
	 * 保存登记的数据
	 */
	public function setData($siteId, &$app, $ek, $data, $submitkey = '') {
		if (empty($data)) {
			return [true];
		}
		// 处理后的登记记录
		$dbData = new \stdClass;

		// 清空已有的登记数据
		$this->delete('xxt_signin_record_data', "aid='{$app->id}' and enroll_key='$ek'");

		foreach ($data as $n => $v) {
			if ($n === 'member' && is_object($v)) {
				//
				$dbData->{$n} = $v;
				/* 自定义用户信息 */
				$treatedValue = new \stdClass;
				isset($v->name) && $treatedValue->name = $v->name;
				isset($v->email) && $treatedValue->email = $v->email;
				isset($v->mobile) && $treatedValue->mobile = $v->mobile;
				if (!empty($v->extattr)) {
					$extattr = new \stdClass;
					foreach ($v->extattr as $mek => $mev) {
						$extattr->{$mek} = $mev;
					}
					$treatedValue->extattr = $extattr;
				}
			} elseif (is_array($v) && (isset($v[0]->serverId) || isset($v[0]->imgSrc))) {
				/* 上传图片 */
				$treatedValue = [];
				$fsuser = \TMS_APP::model('fs/user', $siteId);
				foreach ($v as $img) {
					$rst = $fsuser->storeImg($img);
					if (false === $rst[0]) {
						return $rst;
					}
					$treatedValue[] = $rst[1];
				}
				$treatedValue = implode(',', $treatedValue);
				$dbData->{$n} = $treatedValue;
			} elseif (is_array($v) && isset($v[0]->uniqueIdentifier)) {
				/* 上传文件 */
				$fsUser = \TMS_APP::M('fs/local', $siteId, '_user');
				$fsResum = \TMS_APP::M('fs/local', $siteId, '_resumable');
				$fsAli = \TMS_APP::M('fs/alioss', $siteId);
				$treatedValue = [];
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
					$treatedValue[] = $file;
				}
				//
				$dbData->{$n} = $treatedValue;
			} else {
				if (is_string($v)) {
					$treatedValue = $v;
				} elseif (is_object($v) || is_array($v)) {
					$treatedValue = implode(',', array_keys(array_filter((array) $v, function ($i) {return $i;})));
				} else {
					$treatedValue = $v;
				}
				//
				$dbData->{$n} = $treatedValue;
			}
			// 记录数据
			if (is_object($treatedValue) || is_array($treatedValue)) {
				$treatedValue = $this->toJson($treatedValue);
			}
			$ic = [
				'aid' => $app->id,
				'enroll_key' => $ek,
				'name' => $n,
				'value' => $this->escape($treatedValue),
			];
			$this->insert('xxt_signin_record_data', $ic, false);
		}
		// 记录数据
		$dbData = $this->escape($this->toJson($dbData));
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
	public function byUser(&$oUser, &$oApp, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$userid = isset($oUser->uid) ? $oUser->uid : (isset($oUser->userid) ? $oUser->userid : '');
		if (empty($userid)) {
			return false;
		}

		$q = [
			$fields,
			'xxt_signin_record',
			["state" => 1, "aid" => $oApp->id, "userid" => $userid],
		];
		if ($userRecord = $this->query_obj_ss($q)) {
			$userRecord->data = empty($userRecord->data) ? new \stdClass : json_decode($userRecord->data);
			$userRecord->signin_log = empty($userRecord->signin_log) ? new \stdClass : json_decode($userRecord->signin_log);
		}

		return $userRecord;
	}
	/**
	 * 获得指定项目下的登记记录
	 *
	 * @param int $missionId
	 */
	public function &byMission($missionId, $options) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = [
			$fields,
			'xxt_signin_record r',
		];
		$missionId = $this->escape($missionId);
		$where = "state=1 and exists(select 1 from xxt_signin s where r.aid=s.id and s.mission_id={$missionId})";

		if (isset($options['userid'])) {
			$where .= " and userid='" . $this->escape($options['userid']) . "'";
		}
		$q[2] = $where;

		$list = $this->query_objs_ss($q);
		if (count($list)) {
			foreach ($list as &$record) {
				if ($fields === '*' || strpos($fields, 'data') !== false) {
					$record->data = json_decode($record->data);
				}
				if ($fields === '*' || strpos($fields, 'signin_log') !== false) {
					$record->signin_log = json_decode($record->signin_log);
				}
			}
		}

		return $list;
	}
	/**
	 * 根据验证记录获得用户的登记记录
	 */
	public function &byVerifiedEnrollKey($verifiedEnrollKey, $aid = null, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_signin_record',
			["state" => 1, "verified_enroll_key" => $verifiedEnrollKey],
		];
		if (empty($aid)) {
			$records = $this->query_objs_ss($q);
			if (count($records)) {
				if ($fields === '*' || (false !== strpos($fields, 'data') && false !== strpos($fields, 'signin_log'))) {
					$fnHandler = function (&$record) {
						$record->data = empty($record->data) ? new \stdClass : json_decode($record->data);
						$record->signin_log = empty($record->signin_log) ? new \stdClass : json_decode($record->signin_log);
					};
				} else if (false !== strpos($fields, 'data')) {
					$fnHandler = function (&$record) {
						$record->data = empty($record->data) ? new \stdClass : json_decode($record->data);
					};
				} else if (false !== strpos($fields, 'signin_log')) {
					$fnHandler = function (&$record) {
						$record->signin_log = empty($record->signin_log) ? new \stdClass : json_decode($record->signin_log);
					};
				}
				if (isset($fnHandler)) {
					foreach ($records as &$record) {
						$fnHandler($record);
					}
				}
			}
			return $records;
		} else {
			if ($record = $this->query_obj_ss($q)) {
				if ($fields === '*' || false !== strpos($fields, 'data')) {
					$record->data = empty($record->data) ? new \stdClass : json_decode($record->data);
				}
				if ($fields === '*' || false !== strpos($fields, 'signin_log')) {
					$record->signin_log = empty($record->signin_log) ? new \stdClass : json_decode($record->signin_log);
				}
			}
			return $record;
		}
	}
	/**
	 * 根据指定的数据查找匹配的记录
	 *
	 * 不是所有的字段都检查，只检查字符串类型
	 */
	public function &byData($oApp, $data, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$records = false;

		// 查找条件
		$whereByData = '';
		foreach ($data as $k => $v) {
			if (!empty($v) && is_string($v)) {
				/* 通讯录字段简化处理 */
				if (strpos($k, 'member.') === 0) {
					$k = str_replace('member.', '', $k);
				}
				$whereByData .= ' and (';
				$whereByData .= 'data like \'%"' . $k . '":"' . $v . '"%\'';
				$whereByData .= ' or data like \'%"' . $k . '":"%,' . $v . '"%\'';
				$whereByData .= ' or data like \'%"' . $k . '":"%,' . $v . ',%"%\'';
				$whereByData .= ' or data like \'%"' . $k . '":"' . $v . ',%"%\'';
				$whereByData .= ')';
			}
		}

		// 没有指定条件时就认为没有符合条件的记录
		if (empty($whereByData)) {
			return $records;
		}

		// 查找匹配条件的数据
		$q = [
			$fields,
			'xxt_signin_record',
			"state=1 and aid='{$oApp->id}' $whereByData",
		];
		$records = $this->query_objs_ss($q);
		foreach ($records as &$record) {
			if (empty($record->data)) {
				$record->data = new \stdClass;
			} else {
				$data = json_decode($record->data);
				if ($data === null) {
					$record->data = 'json error(' . json_last_error() . '):' . $r->data;
				} else {
					$record->data = $data;
				}
			}
		}

		return $records;
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
	 * @param object $oApp
	 * @param object $oRecord
	 *
	 */
	public function remove($oApp, $oRecord) {
		$rst = $this->update(
			'xxt_signin_record',
			['state' => 100],
			["aid" => $oApp->id, "enroll_key" => $oRecord->enroll_key, 'state' => 1]
		);
		if ($rst !== 1) {
			return $rst;
		}
		$this->update(
			'xxt_signin_log',
			['state' => 100],
			["aid" => $oApp->id, "enroll_key" => $oRecord->enroll_key, 'state' => 1]
		);
		$this->update(
			'xxt_signin_record_data',
			['state' => 100],
			["aid" => $oApp->id, "enroll_key" => $oRecord->enroll_key, 'state' => 1]
		);

		if (!empty($oApp->mission_id) && !empty($oRecord->userid)) {
			$this->update(
				'xxt_mission_user',
				['signin_num' => (object) ['op' => '-=', 'pat' => $oRecord->signin_num]],
				['mission_id' => $oApp->mission_id, 'userid' => $oRecord->userid, 'state' => 1, 'signin_num' => (object) ['op' => '>', 'pat' => 0]]
			);
		}

		return $rst;
	}
	/**
	 * 清除用户记录
	 *
	 * @param object $oApp
	 */
	public function clean($oApp) {
		if (!empty($oApp->mission_id)) {
			$q = [
				'userid,signin_num',
				'xxt_signin_record',
				['aid' => $oApp->id, 'state' => 1],
			];
			$users = $this->query_objs_ss($q);
			foreach ($users as $oUser) {
				$this->update(
					'xxt_mission_user',
					['signin_num' => (object) ['op' => '-=', 'pat' => $oUser->signin_num]],
					['mission_id' => $oApp->mission_id, 'userid' => $oUser->userid, 'state' => 1, 'signin_num' => (object) ['op' => '>', 'pat' => 0]]
				);
			}
		}
		$rst = $this->update(
			'xxt_signin_record',
			['state' => 0],
			["aid" => $oApp->id, 'state' => 1]
		);
		$this->update(
			'xxt_signin_log',
			['state' => 0],
			["aid" => $oApp->id, 'state' => 1]
		);
		$this->update(
			'xxt_signin_record_data',
			['state' => 0],
			["aid" => $oApp->id, 'state' => 1]
		);

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
	public function byApp($app, $options = null, $criteria = null) {
		if (is_string($app)) {
			$app = $this->model('matter\signin')->byId($app, ['cascaded' => 'Y']);
		}
		if ($app === false) {
			return false;
		}
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

		// 只列出迟到的
		if (isset($criteria->late) && $criteria->late === 'Y') {
			$w .= ' and exists(select 1 from xxt_signin_log l,xxt_signin_round r';
			$w .= " where l.rid=r.rid and l.enroll_key=e.enroll_key and r.late_at>0 and l.signin_at>r.late_at+60";
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

		// 指定了按关键字过滤
		if (!empty($criteria->keyword)) {
			$whereByData = '';
			$whereByData .= ' and (data like \'%' . $criteria->keyword . '%\')';
			$w .= $whereByData;
		}

		// 查询参数
		$q = [
			'e.enroll_key,e.enroll_at,e.signin_at,e.signin_num,e.signin_log,e.userid,e.nickname,e.verified,e.comment,e.tags,e.data,e.verified_enroll_key',
			'xxt_signin_record e',
			$w,
		];
		$q2 = [];
		if (!empty($page) && !empty($size)) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}
		$q2['o'] = 'e.signin_at desc';

		// 处理查询结果
		if ($records = $this->query_objs_ss($q, $q2)) {
			foreach ($records as &$r) {
				// 签到日志
				$r->signin_log = json_decode($r->signin_log);
				// 计算迟到次数
				$lateCount = 0;
				foreach ($app->rounds as $round) {
					if (isset($r->signin_log->{$round->rid}) && !empty($round->late_at)) {
						if ($r->signin_log->{$round->rid} > $round->late_at + 59) {
							$lateCount++;
						}
					}
				}
				$r->lateCount = $lateCount;
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
	 * 活动登记人名单
	 *
	 * @param object $oApp
	 * @param object $options
	 *
	 */
	public function enrolleeByApp($oApp, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : 'enroll_key,userid';

		$w = "state=1 and aid='{$oApp->id}' and userid<>''";

		// 获得填写的登记数据
		$q = [
			$fields,
			"xxt_signin_record",
			$w,
		];
		$records = $this->query_objs_ss($q);
		if (count($records)) {
			$fnDataHandlers = [];
			if ($fields === '*' || strpos($fields, 'signin_log') !== false) {
				$fnDataHandlers[] = function (&$oRecord) {
					$oRecord->signin_log = empty($oRecord->signin_log) ? new \stdClass : json_decode($oRecord->signin_log);
				};
			}
			if (count($fnDataHandlers)) {
				foreach ($records as $oRecord) {
					foreach ($fnDataHandlers as $fnHandler) {
						$fnHandler($oRecord);
					}
				}
			}
		}

		return $records;
	}
	/**
	 * 缺席用户
	 *
	 * 1、如果活动指定了通讯录用户参与；如果活动指定了分组活动的分组用户
	 * 2、如果活动关联了分组活动
	 * 3、如果活动所属项目指定了用户名单
	 */
	public function absentByApp($oApp, $rid = '') {
		/* 获得当前活动的参与人 */
		$oUsers = $this->enrolleeByApp($oApp, ['fields' => 'id,userid,signin_log']);
		$oSigninedUsers = []; // 做过签到的用户
		foreach ($oUsers as $oUser) {
			if (empty($rid)) {
				$oSigninedUsers[] = $oUser->userid;
			} else if (isset($oUser->signin_log->{$rid})) {
				$oSigninedUsers[] = $oUser->userid;
			}
		}
		$aAllUsrs = [];
		if (!empty($oApp->entryRule->group->id)) {
			$modelGrpUsr = $this->model('matter\group\player');
			$aGrpUsrs = $modelGrpUsr->byApp($oApp->entryRule->group->id, ['fields' => 'userid,nickname,is_leader,round_id,round_title']);
			foreach ($aGrpUsrs->players as $oGrpUsr) {
				if (false === in_array($oGrpUsr->userid, $oSigninedUsers)) {
					$aAllUsrs[] = $oGrpUsr;
				}
			}
		} else if (!empty($oApp->entryRule->enroll->id)) {
			$modelRec = $this->model('matter\enroll\record');
			$result = $modelRec->byApp($oApp->entryRule->enroll->id);
			if (!empty($result->records)) {
				foreach ($result->records as $oRec) {
					if (false === in_array($oRec->userid, $oSigninedUsers)) {
						$aAllUsrs[] = $oRec;
					}
				}
			}
		} else if (isset($oApp->entryRule->scope->member) && $oApp->entryRule->scope->member === 'Y' && !empty($oApp->entryRule->member)) {
			$modelMem = $this->model('site\user\member');
			foreach ($oApp->entryRule->member as $mschemaId => $rule) {
				$members = $modelMem->byMschema($mschemaId);
				foreach ($members as $oMember) {
					if (false === in_array($oMember->userid, $oSigninedUsers)) {
						$oUser = new \stdClass;
						$oUser->userid = $oMember->userid;
						$oUser->nickname = $oMember->name;
						$aAllUsrs[] = $oUser;
					}
				}
			}
		}
		/* userid去重 */
		$aAbsentUsrs = [];
		foreach ($aAllUsrs as $aAbsentUsr) {
			$isNew = true;
			foreach ($aAbsentUsrs as $aAbsentUsr2) {
				if ($aAbsentUsr->userid === $aAbsentUsr2->userid || empty($aAbsentUsr->userid)) {
					$isNew = false;
					break;
				}
			}
			if ($isNew) {
				if (isset($oApp->absent_cause->{$aAbsentUsr->userid})) {
					$aAbsentUsr->absent_cause = $oApp->absent_cause->{$aAbsentUsr->userid};
				} else {
					$aAbsentUsr->absent_cause = '';
				}
				$aAbsentUsrs[] = $aAbsentUsr;
			}
		}

		$oResult = new \stdClass;
		$oResult->users = $aAbsentUsrs;

		return $oResult;
	}
}