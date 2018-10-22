<?php
namespace matter\enroll;

require_once dirname(__FILE__) . '/record_base.php';
/**
 * 记录活动记录
 */
class record_model extends record_base {
	/**
	 * 活动登记（不包括登记数据）
	 *
	 * @param object $app
	 * @param object $oUser [uid,nickname]
	 */
	public function enroll($oApp, $oUser = null, $aOptions = []) {
		$referrer = isset($aOptions['referrer']) ? $aOptions['referrer'] : '';
		$enrollAt = isset($aOptions['enrollAt']) ? $aOptions['enrollAt'] : time();
		isset($aOptions['assignRid']) && $assignRid = $aOptions['assignRid'];

		$ek = $this->genKey($oApp->siteid, $oApp->id);

		$aRecord = [
			'aid' => $oApp->id,
			'siteid' => $oApp->siteid,
			'enroll_at' => $enrollAt,
			'first_enroll_at' => $enrollAt,
			'enroll_key' => $ek,
			'userid' => empty($oUser->uid) ? '' : $oUser->uid,
			'group_id' => empty($oUser->group_id) ? '' : $oUser->group_id,
			'referrer' => $referrer,
		];
		/* 记录所属轮次 */
		$modelRun = $this->model('matter\enroll\round');
		if (isset($assignRid)) {
			$aRecord['rid'] = $assignRid;
		} else if ($oActiveRound = $modelRun->getActive($oApp)) {
			$aRecord['rid'] = $oActiveRound->rid;
		}
		/* 登记用户昵称 */
		if (isset($aOptions['nickname'])) {
			$aRecord['nickname'] = $this->escape($aOptions['nickname']);
		} else {
			$nickname = $this->model('matter\enroll')->getUserNickname($oApp, $oUser, $aOptions);
			$aRecord['nickname'] = $this->escape($nickname);
		}
		/* 登记用户的社交账号信息，还需要吗？ */
		if (!empty($oUser->uid)) {
			$oUserOpenids = $this->model('site\user\account')->byId($oUser->uid, ['fields' => 'wx_openid,yx_openid,qy_openid']);
			if ($oUserOpenids) {
				$aRecord['wx_openid'] = $oUserOpenids->wx_openid;
				$aRecord['yx_openid'] = $oUserOpenids->yx_openid;
				$aRecord['qy_openid'] = $oUserOpenids->qy_openid;
			}
		}

		/* 登记记录的表态 */
		if (isset($oApp->actionRule->record->default->agreed)) {
			$agreed = $oApp->actionRule->record->default->agreed;
			if (in_array($agreed, ['A', 'D'])) {
				$aRecord['agreed'] = $agreed;
			}
		}

		/* 移动用户未签到的原因 */
		if (!empty($oUser->uid)) {
			$rid = !empty($aRecord['rid']) ? $aRecord['rid'] : 'ALL';
			if (isset($oApp->absent_cause->{$oUser->uid}) && isset($oApp->absent_cause->{$oUser->uid}->{$rid})) {
				$aRecord['comment'] = $this->escape($oApp->absent_cause->{$oUser->uid}->{$rid});
				unset($oApp->absent_cause->{$oUser->uid}->{$rid});
				if (count(get_object_vars($oApp->absent_cause->{$oUser->uid})) == 0) {
					unset($oApp->absent_cause->{$oUser->uid});
				}
				/* 更新原未签到记录 */
				$newAbsentCause = $this->escape($this->toJson($oApp->absent_cause));
				$this->update(
					'xxt_enroll',
					['absent_cause' => $newAbsentCause],
					['id' => $oApp->id]
				);
			}
		}

		$this->insert('xxt_enroll_record', $aRecord, false);

		/*记录和轮次的关系*/
		$modelRun->createRecord((object) $aRecord);

		return $ek;
	}
	/**
	 * 保存登记的数据
	 *
	 * @param object $oUser [uid]
	 * @param object $oApp
	 * @param string $ek
	 * @param array $submitData 用户提交的数据
	 */
	public function setData($oUser, $oApp, $ek, $submitData, $submitkey = '', $bFirstSubmit = false) {
		if (empty($submitData)) {
			return [true];
		}
		// 数据对应的登记记录
		$oRecord = $this->byId($ek);
		if (false === $oRecord || $oRecord->state !== '1') {
			return [false, '指定的对象不存在'];
		}
		$oResult = $this->model('matter\enroll\data')->setData($oUser, $oApp, $oRecord, $submitData, $submitkey);
		if (is_array($oResult) && false === $oResult[0]) {
			return $oResult;
		}

		/* 更新在登记记录上记录数据 */
		$oRecordUpdated = new \stdClass;
		$oRecordUpdated->data = $this->escape($this->toJson($oResult->dbData));
		if (count(get_object_vars($oResult->score)) > 1) {
			$oRecordUpdated->score = $this->escape($this->toJson($oResult->score));
		}
		/* 记录提交日志 */
		if ($bFirstSubmit === false) {
			if (empty($oRecord->submit_log)) {
				$recordSubmitLogs = [];
			} else {
				$recordSubmitLogs = json_decode($oRecord->submit_log);
			}
			$newSubmitLog = new \stdClass;
			$newSubmitLog->submitAt = $oRecord->enroll_at;
			$newSubmitLog->userid = $oRecord->userid;
			$recordSubmitLogs[] = $newSubmitLog;
			$oRecordUpdated->submit_log = json_encode($recordSubmitLogs);
		}

		$this->update('xxt_enroll_record', $oRecordUpdated, ['enroll_key' => $ek]);

		$oRecordUpdated->data = $oResult->dbData;
		if (isset($oResult->score)) {
			$oRecordUpdated->score = $oResult->score;
		}
		unset($oRecordUpdated->submit_log);

		return [true, $oRecordUpdated];
	}
	/**
	 * 保存登记的数据
	 *
	 * @param object $oUser [uid]
	 * @param object $oApp
	 * @param string $ek
	 * @param array $submitSupp 用户提交的补充说明
	 */
	public function setSupplement($oUser, $oApp, $ek, $submitSupp) {
		/*record*/
		$rst = $this->update(
			'xxt_enroll_record',
			['supplement' => $this->escape($this->toJson($submitSupp))],
			['enroll_key' => $ek, 'state' => 1]
		);

		/*record data*/
		foreach ($submitSupp as $schemaId => $sSupplement) {
			$rst = $this->update(
				'xxt_enroll_record_data',
				['supplement' => $this->escape($sSupplement)],
				['enroll_key' => $ek, 'schema_id' => $schemaId, 'state' => 1]
			);
		}

		return $rst;
	}
	/**
	 * 处理从数据库中取出的数据
	 */
	private function _processRecord(&$oRecord, $fields, $verbose = 'Y') {
		if (property_exists($oRecord, 'data')) {
			$oRecord->data = empty($oRecord->data) ? new \stdClass : json_decode($oRecord->data);
		}
		if ($fields === '*' || false !== strpos($fields, 'supplement')) {
			$oRecord->supplement = empty($oRecord->supplement) ? new \stdClass : json_decode($oRecord->supplement);
		}
		if ($fields === '*' || false !== strpos($fields, 'score')) {
			$oRecord->score = empty($oRecord->score) ? new \stdClass : json_decode($oRecord->score);
		}
		if ($fields === '*' || false !== strpos($fields, 'agreed_log')) {
			$oRecord->agreed_log = empty($oRecord->agreed_log) ? new \stdClass : json_decode($oRecord->agreed_log);
		}
		if ($fields === '*' || false !== strpos($fields, 'like_log')) {
			$oRecord->like_log = empty($oRecord->like_log) ? new \stdClass : json_decode($oRecord->like_log);
		}
		if ($fields === '*' || false !== strpos($fields, 'dislike_log')) {
			$oRecord->dislike_log = empty($oRecord->dislike_log) ? new \stdClass : json_decode($oRecord->dislike_log);
		}
		if ($verbose === 'Y' && isset($oRecord->enroll_key)) {
			$oRecord->verbose = $this->model('matter\enroll\data')->byRecord($oRecord->enroll_key);
		}
		if (!empty($oRecord->rid)) {
			$oRecord->round = new \stdClass;
			if ($round = $this->model('matter\enroll\round')->byId($oRecord->rid, ['fields' => 'title'])) {
				$oRecord->round->title = $round->title;
			} else {
				$oRecord->round->title = '';
			}
		}

		return $oRecord;
	}
	/**
	 * 根据ID返回登记记录
	 */
	public function byPlainId($id, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
		$verbose = isset($aOptions['verbose']) ? $aOptions['verbose'] : 'N';

		$q = [
			$fields,
			'xxt_enroll_record',
			['id' => $id],
		];
		if (isset($aOptions['state'])) {
			$q[2]['state'] = $aOptions['state'];
		}
		if ($oRecord = $this->query_obj_ss($q)) {
			$this->_processRecord($oRecord, $fields, $verbose);
		}

		return $oRecord;
	}
	/**
	 * 根据ek返回登记记录
	 */
	public function &byId($ek, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
		$verbose = isset($aOptions['verbose']) ? $aOptions['verbose'] : 'N';

		$q = [
			$fields,
			'xxt_enroll_record',
			['enroll_key' => $ek],
		];
		if (isset($aOptions['state'])) {
			$q[2]['state'] = $aOptions['state'];
		}
		if ($oRecord = $this->query_obj_ss($q)) {
			$this->_processRecord($oRecord, $fields, $verbose);
		}

		return $oRecord;
	}
	/**
	 * 获得指定用户最后一次登记记录
	 *
	 * 如果用户是注册用户，那么获得这个注册用户，在活动所属团队下，对应的所有站点用户账号填写的内容
	 *
	 * 如果设置轮次，只返回当前轮次的情况
	 */
	public function lastByUser($oApp, $oUser, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
		$verbose = isset($aOptions['verbose']) ? $aOptions['verbose'] : 'N';
		$assignRid = isset($aOptions['assignRid']) ? $aOptions['assignRid'] : '';

		$q = [
			$fields,
			'xxt_enroll_record',
			['aid' => $oApp->id, 'state' => 1, 'userid' => $oUser->uid],
		];

		/* 指定登记轮次 */
		if (empty($assignRid)) {
			if ($activeRound = $this->model('matter\enroll\round')->getActive($oApp)) {
				$q[2]['rid'] = $activeRound->rid;
			}
		} else {
			$q[2]['rid'] = $assignRid;
		}
		/* 登记的时间 */
		$q2 = [
			'o' => 'enroll_at desc',
			'r' => ['o' => 0, 'l' => 1],
		];

		$records = $this->query_objs_ss($q, $q2);

		$oRecord = count($records) === 1 ? $records[0] : false;
		if ($oRecord) {
			$this->_processRecord($oRecord, $fields, $verbose);
		}

		return $oRecord;
	}
	/**
	 * 获得用户的登记清单
	 *
	 * @param object $oApp
	 * @param object $oUser
	 * @param array $aOptions
	 */
	public function byUser($oApp, $oUser, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
		$verbose = isset($aOptions['verbose']) ? $aOptions['verbose'] : 'N';

		$userid = isset($oUser->uid) ? $oUser->uid : (isset($oUser->userid) ? $oUser->userid : '');
		if (empty($userid)) {
			return false;
		}

		$q = [
			$fields,
			'xxt_enroll_record',
			["state" => 1, "aid" => $oApp->id, "userid" => $userid],
		];
		if (!empty($aOptions['rid'])) {
			if (strcasecmp('all', $aOptions['rid']) !== 0) {
				$q[2]['rid'] = $aOptions['rid'];
			}
		} else {
			if ($oActiveRnd = $this->model('matter\enroll\round')->getActive($oApp)) {
				$q[2]['rid'] = $oActiveRnd->rid;
			}
		}
		$q2 = ['o' => 'enroll_at desc'];

		$records = $this->query_objs_ss($q, $q2);
		foreach ($records as $oRecord) {
			$this->_processRecord($oRecord, $fields, $verbose);
		}

		return $records;
	}
	/**
	 * 获得登记轮次的清单
	 *
	 * @param string $roundId
	 */
	public function &byRound($roundId, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
		if ($fields === 'count(*)') {
			$q = [
				'count(*)',
				'xxt_enroll_record',
				["state" => 1, "rid" => $roundId],
			];
			$cnt = (int) $this->query_val_ss($q);

			return $cnt;
		} else {
			$q = [
				$fields,
				'xxt_enroll_record',
				["state" => 1, "rid" => $roundId],
			];

			$q2 = ['o' => 'enroll_at desc'];

			$list = $this->query_objs_ss($q, $q2);

			return $list;
		}
	}
	/**
	 * 获得指定项目下的登记记录
	 *
	 * @param int $missionId
	 */
	public function &byMission($missionId, $aOptions) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
		$q = [
			$fields,
			'xxt_enroll_record r',
		];
		$missionId = $this->escape($missionId);
		$where = "state=1 and exists(select 1 from xxt_enroll e where r.aid=e.id and e.mission_id={$missionId})";

		if (isset($aOptions['userid'])) {
			$where .= " and userid='" . $this->escape($aOptions['userid']) . "'";
		}
		$q[2] = $where;

		$list = $this->query_objs_ss($q);
		if (count($list)) {
			if ($fields === '*' || strpos($fields, 'data') !== false) {
				foreach ($list as &$record) {
					$record->data = json_decode($record->data);
				}
			}
		}

		return $list;
	}
	/**
	 * 根据指定的数据查找匹配的记录
	 *
	 * 不是所有的字段都检查，只检查字符串类型
	 */
	public function &byData(&$oApp, &$data, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
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
			'xxt_enroll_record',
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
	 * 为了计算每条记录的分数，转换schema的形式
	 */
	private function _mapOfScoreSchema(&$oApp) {
		$scoreSchemas = new \stdClass;

		foreach ($oApp->dataSchemas as $schema) {
			if ($schema->type === 'single' && isset($schema->score) && $schema->score === 'Y') {
				$scoreSchemas->{$schema->id} = new \stdClass;
				$scoreSchemas->{$schema->id}->ops = new \stdClass;
				foreach ($schema->ops as $op) {
					$scoreSchemas->{$schema->id}->ops->{$op->v} = $op;
				}
			}
		}

		return $scoreSchemas;
	}
	/**
	 * 计算记录的分数
	 */
	private function _calcVotingScore(&$scoreSchemas, &$data) {
		$score = 0;
		foreach ($scoreSchemas as $schemaId => $schema) {
			if (!empty($data->{$schemaId})) {
				$opScore = empty($schema->ops->{$data->{$schemaId}}->score) ? 0 : $schema->ops->{$data->{$schemaId}}->score;
				$score += $opScore;
			}
		}

		return $score;
	}
	/**
	 * 登记清单
	 *
	 * @param object/string 记录活动/记录活动的id
	 * @param object/array $aOptions
	 * --page
	 * --size
	 * --kw 检索关键词
	 * --by 检索字段
	 * @param object $criteria 登记数据过滤条件
	 *
	 * @return object
	 * records 数据列表
	 * total 数据总条数
	 */
	public function byApp($oApp, $oOptions = null, $oCriteria = null, $oUser = null) {
		if (is_string($oApp)) {
			$oApp = $this->model('matter\enroll')->byId($oApp, ['cascaded' => 'N']);
		}
		if (false === $oApp) {
			return false;
		}
		if ($oOptions && is_array($oOptions)) {
			$oOptions = (object) $oOptions;
		}

		$oSchemasById = new \stdClass; // 方便查找题目
		if (!empty($oApp->dataSchemas)) {
			foreach ($oApp->dataSchemas as $oSchema) {
				$oSchemasById->{$oSchema->id} = $oSchema;
			}
		}
		// 指定记录活动下的登记记录
		$w = "r.state=1 and r.aid='{$oApp->id}'";

		/* 指定轮次，或者当前激活轮次 */
		if (empty($oCriteria->record->rid)) {
			if (!empty($oApp->appRound->rid)) {
				$rid = $oApp->appRound->rid;
				//$w .= " and (r.rid='$rid'";
				$w .= " and (exists(select 1 from xxt_enroll_record_round rrnd where rrnd.rid='$rid' and rrnd.enroll_key=r.enroll_key)";
				if (isset($oOptions->regardRemarkRoundAsRecordRound) && $oOptions->regardRemarkRoundAsRecordRound === true) {
					$w .= " or exists(select 1 from xxt_enroll_record_remark rr where rr.aid=r.aid and rr.enroll_key=r.enroll_key and rr.rid='$rid')";
				}
				$w .= ')';
			}
		} else {
			if (is_string($oCriteria->record->rid)) {
				if (strcasecmp('all', $oCriteria->record->rid) !== 0) {
					$rid = $oCriteria->record->rid;
					//$w .= " and (r.rid='$rid'";
					$w .= " and (exists(select 1 from xxt_enroll_record_round rrnd where rrnd.rid='$rid' and rrnd.enroll_key=r.enroll_key)";
					if (isset($oOptions->regardRemarkRoundAsRecordRound) && $oOptions->regardRemarkRoundAsRecordRound === true) {
						$w .= " or exists(select 1 from xxt_enroll_record_remark rr where rr.aid=r.aid and rr.enroll_key=r.enroll_key and rr.rid='$rid')";
					}
					$w .= ')';
				}
			} else if (is_array($oCriteria->record->rid)) {
				if (empty(array_intersect(['all', 'ALL'], $oCriteria->record->rid))) {
					$rid = $oCriteria->record->rid;
					//$w .= " and r.rid in('" . implode("','", $rid) . "')";
					$w .= " and exists(select 1 from xxt_enroll_record_round rrnd where rrnd.rid in('" . implode("','", $rid) . "') and rrnd.enroll_key=r.enroll_key)";
				}
			}
		}

		// 根据用户分组过滤
		if (isset($oCriteria->record->group_id)) {
			$w .= " and r.group_id='{$oCriteria->record->group_id}'";
		}

		// 根据填写人筛选（填写端列表页需要）
		if (!empty($oCriteria->record->userid)) {
			$w .= " and r.userid='{$oCriteria->record->userid}'";
		}
		// 记录是否通过审核
		if (!empty($oCriteria->record->verified)) {
			$w .= " and r.verified='{$oCriteria->record->verified}'";
		}

		/**
		 * 记录推荐状态
		 */
		if (isset($oCriteria->record->agreed)) {
			$w .= " and r.agreed='{$oCriteria->record->agreed}'";
		} else {
			// 屏蔽状态的记录默认不可见
			$w .= " and r.agreed<>'N'";
		}
		// 讨论状态的记录仅提交人，同组用户或超级用户可见
		if (isset($oUser)) {
			// 当前用户收藏的
			if (!empty($oUser->unionid) && !empty($oCriteria->record->favored)) {
				$w .= " and exists(select 1 from xxt_enroll_record_favor f where r.id=f.record_id and f.favor_unionid='{$oUser->unionid}' and f.state=1)";
			}
			// 当前用户角色
			if (empty($oUser->is_leader) || $oUser->is_leader !== 'S') {
				if (!empty($oUser->uid)) {
					$w .= " and (";
					$w .= " (r.agreed<>'D'";
					if (isset($oUser->is_editor) && $oUser->is_editor !== 'Y') {
						$w .= " and r.agreed<>''"; // 如果活动指定了编辑，未表态的数据默认不公开
					}
					$w .= ")";
					$w .= " or r.userid='{$oUser->uid}'";
					if (!empty($oUser->group_id)) {
						$w .= " or r.group_id='{$oUser->group_id}'";
					}
					if (isset($oUser->is_editor) && $oUser->is_editor === 'Y') {
						$w .= " or r.group_id=''";
					}
					$w .= ")";
				}
			}
		}

		// 指定了专题的
		if (!empty($oCriteria->record->topic)) {
			$w .= " and exists(select 1 from xxt_enroll_topic_record t where r.id=t.record_id and t.topic_id='{$oCriteria->record->topic}')";
		}

		// 预制条件：指定分组或赞同数大于
		if (isset($oCriteria->GroupOrLikeNum) && is_object($oCriteria->GroupOrLikeNum)) {
			if (!empty($oCriteria->GroupOrLikeNum->group_id) && isset($oCriteria->GroupOrLikeNum->like_num)) {
				$w .= " and (r.group_id='{$oCriteria->GroupOrLikeNum->group_id}' or r.like_num>={$oCriteria->GroupOrLikeNum->like_num})";
			}
		}

		// 指定了记录标签
		if (!empty($oCriteria->record->tags)) {
			if (is_array($oCriteria->record->tags)) {
				foreach ($oCriteria->record->tags as $tagId) {
					$w .= " and exists(select 1 from xxt_enroll_tag_target tt where tt.target_id=r.id and tt.target_type=1 and tt.tag_id=" . $tagId . ")";
				}
			} else {
				$w .= " and exists(select 1 from xxt_enroll_tag_target tt where tt.target_id=r.id and tt.target_type=1 and tt.tag_id=" . $oCriteria->record->tags . ")";
			}
		}

		// 指定了登记数据过滤条件
		if (isset($oCriteria->data)) {
			$whereByData = '';
			foreach ($oCriteria->data as $k => $v) {
				if (!empty($v) && isset($oSchemasById->{$k})) {
					$oSchema = $oSchemasById->{$k};
					$whereByData .= ' and (';
					if ($oSchema->type === 'multiple') {
						// 选项ID是否互斥，不存在，例如：v1和v11
						$bOpExclusive = true;
						$strOpVals = '';
						foreach ($oSchema->ops as $op) {
							$strOpVals .= ',' . $op->v;
						}
						foreach ($oSchema->ops as $op) {
							if (false !== strpos($strOpVals, $op->v)) {
								$bOpExclusive = false;
								break;
							}
						}
						// 拼写sql
						$v2 = explode(',', $v);
						foreach ($v2 as $index => $v2v) {
							if ($index > 0) {
								$whereByData .= ' and ';
							}
							// 获得和题目匹配的子字符串
							$dataBySchema = 'substr(substr(data,locate(\'"' . $k . '":"\',data)),1,locate(\'"\',substr(data,locate(\'"' . $k . '":"\',data)),' . (strlen($k) + 5) . '))';
							$whereByData .= '(';
							if ($bOpExclusive) {
								$whereByData .= $dataBySchema . ' like \'%' . $v2v . '%\'';
							} else {
								$whereByData .= $dataBySchema . ' like \'%"' . $v2v . '"%\'';
								$whereByData .= ' or ' . $dataBySchema . ' like \'%"' . $v2v . ',%\'';
								$whereByData .= ' or ' . $dataBySchema . ' like \'%,' . $v2v . ',%\'';
								$whereByData .= ' or ' . $dataBySchema . ' like \'%,' . $v2v . '"%\'';
							}
							$whereByData .= ')';
						}
					} else {
						$whereByData .= 'data like \'%"' . $k . '":"' . $v . '"%\'';
					}
					$whereByData .= ')';
				}
			}
			$w .= $whereByData;
		}

		// 指定了按关键字过滤
		if (!empty($oOptions->keyword)) {
			$w .= ' and (data like \'%' . $oOptions->keyword . '%\')';
		}
		// 筛选答案
		if (isset($oCriteria->cowork)) {
			$coworkSchemaIds = [];
			foreach ($oSchemasById as $oSchemaId => $oSchema) {
				if (isset($oSchema->cowork) && $oSchema->cowork === 'Y') {
					$coworkSchemaIds[] = $oSchemaId;
				}
			}
			$coworkSchemaIds = "('" . implode("','", $coworkSchemaIds) . "')";
			if (!empty($coworkSchemaIds)) {
				if (isset($oCriteria->cowork->agreed) && empty($oCriteria->cowork->agreed)) {
					// 如果查询未表态的问题需要所有的答案都未表态才返回
					$w .= " and 0 = (select count(rd.id) from xxt_enroll_record_data rd where rd.enroll_key = r.enroll_key and rd.agreed <> '' and rd.state=1 and rd.schema_id in " . $coworkSchemaIds . " and rd.rid = r.rid)";
				} else if (isset($oCriteria->cowork->agreed) && $oCriteria->cowork->agreed === 'answer') {
					// 如果查询已回答的问题，答案表态为A或者Y的都算已回答
					$w .= " and exists(select 1 from xxt_enroll_record_data rd where r.enroll_key = rd.enroll_key and (rd.agreed = 'Y' or rd.agreed = 'A') and rd.state=1 and rd.schema_id in " . $coworkSchemaIds . " and rd.rid = r.rid)";
				} else if (isset($oCriteria->cowork->agreed) && $oCriteria->cowork->agreed === 'unanswer') {
					// 如果查询未回答的问题需要查询所有的答案表态都不是“Y”和“A”才返回
					$w .= " and 0 = (select count(rd.id) from xxt_enroll_record_data rd where rd.enroll_key = r.enroll_key and (rd.agreed = 'Y' or rd.agreed = 'A') and rd.state=1 and rd.schema_id in " . $coworkSchemaIds . " and rd.rid = r.rid)";
				} else if (isset($oCriteria->cowork->agreed)) {
					$w .= " and exists(select 1 from xxt_enroll_record_data rd where r.enroll_key = rd.enroll_key and rd.agreed = '{$oCriteria->cowork->agreed}' and rd.state=1 and rd.schema_id in " . $coworkSchemaIds . " and rd.rid = r.rid)";
				}
			}
		}

		// 查询参数
		$q = [
			'id,enroll_key,rid,enroll_at,userid,group_id,nickname,wx_openid,yx_openid,qy_openid,headimgurl,verified,comment,data,score,supplement,agreed,like_num,like_log,remark_num,favor_num,dislike_num,dislike_log',
			"xxt_enroll_record r",
			$w,
		];

		$q2 = [];
		// 查询结果分页
		if (!empty($oOptions->page) && !empty($oOptions->size)) {
			$q2['r'] = ['o' => ($oOptions->page - 1) * $oOptions->size, 'l' => $oOptions->size];
		}

		// 查询结果排序
		if (!empty($oOptions->orderby)) {
			if (!empty($oOptions->schemaId)) {
				$schemaId = $oOptions->schemaId;
				$orderby = $oOptions->orderby;
				$q[1] .= ",xxt_enroll_record_data d";
				$q[2] .= " and r.enroll_key = d.enroll_key and d.schema_id = '$schemaId' and d.multitext_seq = 0";
				$q2['o'] = 'd.' . $orderby . ' desc';
			} else {
				$fnOrderBy = function ($orderbys) {
					is_string($orderbys) && $orderbys = [$orderbys];
					$sqls = [];
					foreach ($orderbys as $orderby) {
						switch ($orderby) {
						case 'sum':
							$sqls[] = 'r.score desc';
							break;
						case 'agreed':
							$sqls[] = 'r.agreed desc';
							break;
						case 'like_num':
							$sqls[] = 'r.like_num desc';
							break;
						case 'enroll_at':
							$sqls[] = 'r.enroll_at desc';
							break;
						case 'enroll_at asc':
							$sqls[] = 'r.enroll_at';
							break;
						}
					}
					return implode(',', $sqls);
				};
				$q2['o'] = $fnOrderBy($oOptions->orderby);
			}
		} else {
			$q2['o'] = 'r.enroll_at desc';
		}
		/**
		 * 处理获得的数据
		 */
		$oResult = new \stdClass; // 返回的结果
		if ($records = $this->query_objs_ss($q, $q2)) {
			/* 检查题目是否可见 */
			$oResult->records = $this->parse($oApp, $records);
		}
		// 符合条件的数据总数
		$q[0] = 'count(*)';
		$total = (int) $this->query_val_ss($q);
		$oResult->total = $total;

		return $oResult;
	}
	/**
	 * 解析记录的内容，将数据库中的格式转换为应用格式
	 */
	public function parse($oApp, &$records) {
		$bRequireScore = false; // 数值型的填空题需要计算分值
		$visibilitySchemas = []; // 设置了可见性规则的题目
		if (!empty($oApp->dataSchemas)) {
			foreach ($oApp->dataSchemas as $oSchema) {
				if ($oSchema->type == 'shorttext' && isset($oSchema->format) && $oSchema->format === 'number') {
					$bRequireScore = true;
					break;
				}
				if (!empty($oSchema->visibility->rules)) {
					$visibilitySchemas[] = $oSchema;
				}
			}
		}

		$aGroupsById = []; // 缓存分组数据
		$aRoundsById = []; // 缓存轮次数据

		$fnCheckSchemaVisibility = function ($oSchemas, &$oRecordData) {
			foreach ($oSchemas as $oSchema) {
				foreach ($oSchema->visibility->rules as $oRule) {
					if (strpos($oSchema->id, 'member.extattr') === 0) {
						$memberSchemaId = str_replace('member.extattr.', '', $oSchema->id);
						if (!isset($oRecordData->member->extattr->{$memberSchemaId}) || ($oRecordData->member->extattr->{$memberSchemaId} !== $oRule->op && empty($oRecordData->member->extattr->{$memberSchemaId}))) {
							unset($oRecordData->{$oSchema->id});
							break;
						}
					} else if (!isset($oRecordData->{$oRule->schema}) || ($oRecordData->{$oRule->schema} !== $oRule->op && empty($oRecordData->{$oRule->schema}->{$oRule->op}))) {
						unset($oRecordData->{$oSchema->id});
						break;
					}
				}
			}
		};

		$aFnHandlers = []; // 记录处理函数
		if (isset($oApp->scenario)) {
			/* 记录得分 */
			$aFnHandlers[] = function ($oRec) use ($oApp, $bRequireScore) {
				if (($oApp->scenario === 'quiz' || $bRequireScore) && !empty($oRec->score)) {
					$score = str_replace("\n", ' ', $oRec->score);
					$score = json_decode($score);
					if ($score === null) {
						$oRec->score = 'json error(' . json_last_error_msg() . '):' . $oRec->score;
					} else {
						$oRec->score = $score;
					}
				}
			};
			// 记录的分数
			if ($oApp->scenario === 'voting' || $oApp->scenario === 'common') {
				$scoreSchemas = $this->_mapOfScoreSchema($oApp);
				$countScoreSchemas = count(array_keys((array) $scoreSchemas));
				$aFnHandlers[] = function ($oRec) use ($scoreSchemas, $countScoreSchemas) {
					$oRec->_score = $this->_calcVotingScore($scoreSchemas, $oRec->data);
					$oRec->_average = $countScoreSchemas === 0 ? 0 : $oRec->_score / $countScoreSchemas;
				};
			}
		}
		/* 用户所属分组 */
		if (!empty($oApp->group_app_id) || !empty($oApp->entryRule->group->id)) {
			$groupAppId = !empty($oApp->group_app_id) ? $oApp->group_app_id : $oApp->entryRule->group->id;
			$modelGrpUser = $this->model('matter\group\user');
			$aFnHandlers[] = function ($oRec) use ($groupAppId, $modelGrpUser) {
				if (!empty($oRec->userid)) {
					$oGrpUser = $modelGrpUser->byUser((object) ['id' => $groupAppId], $oRec->userid, ['fields' => 'round_id,round_title', 'onlyOne' => true]);
					if ($oGrpUser) {
						if (!isset($oRec->user)) {
							$oRec->user = new \stdClass;
						}
						$oRec->user->group = (object) ['id' => $oGrpUser->round_id, 'title' => $oGrpUser->round_title];
					}
				}
			};
		}
		foreach ($records as $oRec) {
			if (property_exists($oRec, 'like_log')) {
				$oRec->like_log = empty($oRec->like_log) ? new \stdClass : json_decode($oRec->like_log);
			}
			//附加说明
			if (!empty($oRec->supplement)) {
				$supplement = str_replace("\n", ' ', $oRec->supplement);
				$supplement = json_decode($supplement);

				if ($supplement === null) {
					$oRec->supplement = 'json error(' . json_last_error_msg() . '):' . $oRec->supplement;
				} else {
					$oRec->supplement = $supplement;
				}
			}
			if (!empty($oRec->data)) {
				$data = str_replace("\n", ' ', $oRec->data);
				$data = json_decode($data);
				if ($data === null) {
					$oRec->data = 'json error(' . json_last_error_msg() . '):' . $oRec->data;
				} else {
					$oRec->data = $data;
					/* 处理提交数据后分组的问题 */
					if (!empty($oRec->group_id) && !isset($oRec->data->_round_id)) {
						$oRec->data->_round_id = $oRec->group_id;
					}
					/* 处理提交数据后指定昵称题的问题 */
					if ($oRec->nickname && isset($oApp->assignedNickname->valid) && $oApp->assignedNickname->valid === 'Y') {
						if (isset($oApp->assignedNickname->schema->id)) {
							$nicknameSchemaId = $oApp->assignedNickname->schema->id;
							if (0 === strpos($nicknameSchemaId, 'member.')) {
								$nicknameSchemaId = explode('.', $nicknameSchemaId);
								if (!isset($oRec->data->member)) {
									$oRec->data->member = new \stdClass;
								}
								if (!isset($oRec->data->member->{$nicknameSchemaId[1]})) {
									$oRec->data->member->{$nicknameSchemaId[1]} = $oRec->nickname;
								}
							} else {
								if (!isset($oRec->data->{$nicknameSchemaId})) {
									$oRec->data->{$nicknameSchemaId} = $oRec->nickname;
								}
							}
						}
					}
					/* 根据题目的可见性处理数据 */
					if (count($visibilitySchemas)) {
						$fnCheckSchemaVisibility($visibilitySchemas, $oRec->data);
					}
				}
			} else {
				$oRec->data = new \stdClass;
			}
			// 记录的分组
			if (!empty($oRec->group_id)) {
				if (!isset($aGroupsById[$oRec->group_id])) {
					if (!isset($modelGrpRnd)) {
						$modelGrpRnd = $this->model('matter\group\round');
					}
					$oGroup = $modelGrpRnd->byId($oRec->group_id, ['fields' => 'title']);
					$aGroupsById[$oRec->group_id] = $oGroup;
				} else {
					$oGroup = $aGroupsById[$oRec->group_id];
				}
				if ($oGroup) {
					$oRec->group = $oGroup;
				}
			}
			// 用户的分组

			// 记录的登记轮次
			if (!empty($oRec->rid)) {
				if (!isset($aRoundsById[$oRec->rid])) {
					if (!isset($modelRnd)) {
						$modelRnd = $this->model('matter\enroll\round');
					}
					$round = $modelRnd->byId($oRec->rid, ['fields' => 'title']);
					$aRoundsById[$oRec->rid] = $round;
				} else {
					$round = $aRoundsById[$oRec->rid];
				}
				if ($round) {
					$oRec->round = $round;
				}
			}

			foreach ($aFnHandlers as $fnHandler) {
				$fnHandler($oRec);
			}
		}

		return $records;
	}
	/**
	 * 已删除的登记清单
	 */
	public function recycle($oApp, $options = null) {
		if ($options) {
			is_array($options) && $options = (object) $options;
			$page = isset($options->page) ? $options->page : null;
			$size = isset($options->size) ? $options->size : null;
			$rid = null;
			if (!empty($options->rid)) {
				if ($options->rid === 'ALL') {
					$rid = null;
				} else if (!empty($options->rid)) {
					$rid = $options->rid;
				}
			} else if ($activeRound = $this->M('matter\enroll\round')->getActive($oApp)) {
				$rid = $activeRound->rid;
			}
		}
		$oResult = new \stdClass; // 返回的结果
		$oResult->total = 0;

		// 指定记录活动下的登记记录
		$w = "(e.state=100 or e.state=101 or e.state=0) and e.aid='{$oApp->id}'";

		// 指定了轮次
		!empty($rid) && $w .= " and e.rid='$rid'";

		// 指定了登记记录过滤条件
		if (!empty($criteria->record)) {
			$whereByRecord = '';
			if (!empty($criteria->record->verified)) {
				$whereByRecord .= " and verified='{$criteria->record->verified}'";
			}
			$w .= $whereByRecord;
		}

		$q = [
			'e.enroll_key,e.enroll_at,e.tags,e.userid,e.nickname,e.verified,e.comment,e.data,e.state',
			"xxt_enroll_record e",
			$w,
		];

		$q2 = [];
		// 查询结果分页
		if (!empty($page) && !empty($size)) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}
		// 查询结果排序
		$q2['o'] = 'e.enroll_at desc';
		// 处理获得的数据
		if ($records = $this->query_objs_ss($q, $q2)) {
			foreach ($records as &$r) {
				$data = str_replace("\n", ' ', $r->data);
				$data = json_decode($data);
				if ($data === null) {
					$r->data = 'json error(' . json_last_error_msg() . '):' . $r->data;
				} else {
					$r->data = $data;
				}
				// 记录的分数
				if ($oApp->scenario === 'voting') {
					if (!isset($scoreSchemas)) {
						$scoreSchemas = $this->_mapOfScoreSchema($oApp);
						$countScoreSchemas = count(array_keys((array) $scoreSchemas));
					}
					$r->_score = $this->_calcVotingScore($scoreSchemas, $data);
					$r->_average = $countScoreSchemas === 0 ? 0 : $r->_score / $countScoreSchemas;
				}
			}
			$oResult->records = $records;

			// 符合条件的数据总数
			$q[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q);
			$oResult->total = $total;
		}

		return $oResult;
	}
	/**
	 * 返回指定登记项的登记记录
	 *
	 */
	public function list4Schema($oApp, $schemaId, $options = null) {
		foreach ($oApp->dataSchemas as $oSchema) {
			if ($oSchema->id === $schemaId) {
				$oDataSchema = $oSchema;
				break;
			}
		}
		if (!isset($oDataSchema)) {
			return false;
		}

		if (strpos($schemaId, 'member.') === 0) {
			$schemaId = 'member';
		}

		if ($options) {
			is_array($options) && $options = (object) $options;
			$page = isset($options->page) ? $options->page : null;
			$size = isset($options->size) ? $options->size : null;
			$rid = isset($options->rid) ? $this->escape($options->rid) : null;
		}

		$oResult = new \stdClass; // 返回的结果
		$oResult->records = [];
		$oResult->total = 0;

		// 查询参数
		$q = [
			'd.enroll_key,d.like_log,d.like_num,r.nickname,r.rid,r.enroll_at,r.data',
			"xxt_enroll_record_data d,xxt_enroll_record r",
			"d.state=1 and d.aid='{$oApp->id}' and d.schema_id='{$schemaId}' and d.value<>'' and d.multitext_seq = 0 and r.aid = d.aid and r.enroll_key = d.enroll_key",
		];
		/* 指定用户 */
		if (!empty($options->owner)) {
			$q[2] .= " and d.userid='" . $options->owner . "'";
		}
		/* 指定登记轮次 */
		if (!empty($rid)) {
			if ($rid !== 'ALL') {
				$q[2] .= " and d.rid='{$rid}'";
			}
		} else {
			/* 没有指定轮次，就使用当前轮次 */
			if ($activeRound = $this->model('matter\enroll\round')->getActive($oApp)) {
				$q[2] .= " and d.rid='{$activeRound->rid}'";
			}
		}

		$q2 = [];
		// 查询结果分页
		if (!empty($page) && !empty($size)) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}

		$recordsBySchema = [];
		// 处理获得的数据
		if ($records = $this->query_objs_ss($q, $q2)) {
			//如果是数值型计算合计值
			if (isset($oDataSchema->number) && $oDataSchema->number === 'Y') {
				$p = [
					'sum(value)',
					'xxt_enroll_record_data',
					['aid' => $oApp->id, 'schema_id' => $schemaId, 'state' => 1],
				];
				if (!empty($rid)) {
					if ($rid !== 'ALL') {
						$p[2]['rid'] = $rid;
					}
				} else {
					if ($activeRound = $this->model('matter\enroll\round')->getActive($oApp)) {
						$p[2]['rid'] = $activeRound->rid;
					}
				}

				$sum = (int) $this->query_val_ss($p);
				$oResult->sum = $sum;
			}
			foreach ($records as $oRecord) {
				if (empty($oRecord->data)) {
					$oRecord->data = new \stdClass;
				} else {
					$oData = json_decode($oRecord->data);
					$oRecord->data = new \stdClass;
					if (isset($oData) && is_object($oData)) {
						$oRecord->data->{$schemaId} = empty($oData->{$schemaId}) ? '' : $oData->{$schemaId};
						if (!empty($oApp->rpConfig->marks)) {
							foreach ($oApp->rpConfig->marks as $oMark) {
								if (isset($oData->{$oMark->id})) {
									$oRecord->data->{$oMark->id} = $oData->{$oMark->id};
								}
							}
						}
					}
				}
				$oRecord->like_log = empty($oRecord->like_log) ? new \stdClass : json_decode($oRecord->like_log);
				$oResult->records[] = $oRecord;
			}
		}

		// 符合条件的数据总数
		$q[0] = 'count(*)';
		$total = (int) $this->query_val_ss($q, $q2);
		$oResult->total = $total;

		return $oResult;
	}
	/**
	 * 计算指定登记项所有记录的合计
	 */
	public function sum4Schema($oApp, $rid = 'ALL', $gid = '') {
		if (empty($oApp->dynaDataSchemas)) {
			return false;
		}
		$oResult = new \stdClass;
		$dataSchemas = $oApp->dynaDataSchemas;
		if (empty($rid)) {
			if ($activeRound = $this->model('matter\enroll\round')->getActive($oApp)) {
				$rid = $activeRound->rid;
			}
		}
		/* 每道题目的合计 */
		foreach ($dataSchemas as $oSchema) {
			if (!in_array($oSchema->type, ['shorttext', 'score'])) {
				continue;
			}
			if ($oSchema->type === 'shorttext') {
				/* 数字格式的单行填写题 */
				if (!isset($oSchema->format) || $oSchema->format !== 'number') {
					continue;
				}
			}

			switch ($oSchema->type) {
			case 'shorttext':
				$sumSql = 'sum(value)';
				break;
			case 'score':
				$sumSql = 'sum(score)';
				break;
			}

			$q = [
				$sumSql,
				'xxt_enroll_record_data',
				['aid' => $oApp->id, 'schema_id' => $oSchema->id, 'state' => 1],
			];
			if (!empty($rid)) {
				if (is_string($rid)) {
					$rid !== 'ALL' && $q[2]['rid'] = $rid;
				} else if (is_array($rid)) {
					if (empty(array_intersect(['all', 'ALL'], $rid))) {
						$q[2]['rid'] = $rid;
					}
				}
			}
			if (!empty($gid)) {
				$q[2]['group_id'] = $gid;
			}

			$sum = (float) $this->query_val_ss($q);
			$sum = number_format($sum, 2, '.', '');
			$oResult->{$oSchema->id} = (float) $sum;
		}

		return $oResult;
	}
	/**
	 * 计算指定登记项所有记录的合计
	 */
	public function score4Schema($oApp, $rid = 'ALL', $gid = '') {
		if (empty($oApp->dynaDataSchemas)) {
			return false;
		}
		$oResult = new \stdClass;
		$dataSchemas = $oApp->dynaDataSchemas;
		if (empty($rid)) {
			if ($activeRound = $this->model('matter\enroll\round')->getActive($oApp)) {
				$rid = $activeRound->rid;
			}
		}
		/* 每道题目的得分 */
		foreach ($dataSchemas as $oSchema) {
			if ((isset($oSchema->requireScore) && $oSchema->requireScore === 'Y')) {
				$q = [
					'sum(score)',
					'xxt_enroll_record_data',
					['aid' => $oApp->id, 'schema_id' => $oSchema->id, 'state' => 1],
				];
				if (!empty($rid)) {
					if (is_string($rid)) {
						$rid !== 'ALL' && $q[2]['rid'] = $rid;
					} else if (is_array($rid)) {
						if (empty(array_intersect(['all', 'ALL'], $rid))) {
							$q[2]['rid'] = $rid;
						}
					}
				}
				if (!empty($gid)) {
					$q[2]['group_id'] = $gid;
				}

				$sum = (float) $this->query_val_ss($q);
				$sum = number_format($sum, 2, '.', '');
				$oResult->{$oSchema->id} = (float) $sum;
			}
		}

		/*所有题的得分合计*/
		$q = [
			'sum(score)',
			'xxt_enroll_record_data',
			['aid' => $oApp->id, 'state' => 1],
		];
		if (!empty($rid)) {
			if (is_string($rid)) {
				$rid !== 'ALL' && $q[2]['rid'] = $rid;
			} else if (is_array($rid)) {
				if (empty(array_intersect(['all', 'ALL'], $rid))) {
					$q[2]['rid'] = $rid;
				}
			}
		}
		if (!empty($gid)) {
			$q[2]['group_id'] = $gid;
		}

		$sum = (float) $this->query_val_ss($q);
		$sum = number_format($sum, 2, '.', '');
		$oResult->sum = (float) $sum;

		return $oResult;
	}
	/**
	 * 获得指定用户最后一次登记的key
	 * 如果设置轮次，只检查当前轮次的情况
	 *
	 * @param object $oApp
	 * @param object $oUser
	 *
	 */
	public function lastKeyByUser(&$oApp, &$oUser) {
		$last = $this->lastByUser($oApp, $oUser);

		return $last ? $last->enroll_key : false;
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
			['enroll_key' => $ek]
		);
		return $rst;
	}
	/**
	 * 更新用户数据
	 *
	 * @param object $oApp(id,mission_id)
	 * @param object $oRecord(enroll_key,userid,rid)
	 *
	 */
	private function _updateUser($oApp, $oRecord) {
		// 更新活动的累计数据
		$modelEnlUsr = $this->model('matter\enroll\user');
		$aResult = $modelEnlUsr->removeRecord($oApp, $oRecord);
		if (false === $aResult[0]) {
			return $aResult;
		}
		// 更新项目的累计数据
		if (!empty($oApp->mission_id)) {
			$modelMisUsr = $this->model('matter\mission\user');
			$aResult = $modelMisUsr->removeRecord($oApp->mission_id, $oRecord);
			if (false === $aResult[0]) {
				return $aResult;
			}
		}

		return [true];
	}
	/**
	 * 登记人清除一条登记记录
	 *
	 * @param object $oApp
	 * @param object $oRecord
	 */
	public function removeByUser($oApp, $oRecord) {
		$rst = $this->update(
			'xxt_enroll_record',
			['state' => 101],
			['aid' => $oApp->id, 'enroll_key' => $oRecord->enroll_key, 'state' => 1]
		);
		if ($rst !== 1) {
			return $rst;
		}

		$this->delete(
			'xxt_enroll_record_stat',
			['aid' => $oApp->id]
		);
		$this->update(
			'xxt_enroll_record_data',
			['state' => 101],
			['aid' => $oApp->id, 'enroll_key' => $oRecord->enroll_key]
		);

		$this->_updateUser($oApp, $oRecord);

		return $rst;
	}
	/**
	 * 清除一条登记记录
	 *
	 * @param object $oApp
	 * @param object $oRecord
	 */
	public function remove($oApp, $oRecord) {
		$rst = $this->update(
			'xxt_enroll_record',
			['state' => 100],
			['aid' => $oApp->id, 'enroll_key' => $oRecord->enroll_key, 'state' => 1]
		);
		if ($rst !== 1) {
			return $rst;
		}
		$this->update(
			'xxt_enroll_record_data',
			['state' => 100],
			['aid' => $oApp->id, 'enroll_key' => $oRecord->enroll_key, 'state' => 1]
		);
		$this->delete(
			'xxt_enroll_record_stat',
			['aid' => $oApp->id]
		);
		/**
		 * 更新用户累计数据
		 */
		if (!empty($oRecord->userid)) {
			$this->_updateUser($oApp, $oRecord);
		}

		return $rst;
	}
	/**
	 * 清空填写记录
	 * 更新项目中的用户数据
	 *
	 * @param object $oApp
	 */
	public function clean($oApp) {
		$rst = $this->delete(
			'xxt_enroll_record_stat',
			['aid' => $oApp->id]
		);
		/* 更新项目的用户数据 */
		if (!empty($oApp->mission_id)) {
			$q = [
				'userid,enroll_num,remark_num,like_num,like_remark_num,do_remark_num,do_like_num,do_like_remark_num,user_total_coin',
				'xxt_enroll_user',
				['aid' => $oApp->id, 'state' => 1, 'rid' => 'ALL'],
			];
			$users = $this->query_objs_ss($q);
			foreach ($users as $oUser) {
				$this->update(
					'xxt_mission_user',
					[
						'enroll_num' => (object) ['op' => '-=', 'pat' => $oUser->enroll_num],
						'remark_num' => (object) ['op' => '-=', 'pat' => $oUser->remark_num],
						'like_num' => (object) ['op' => '-=', 'pat' => $oUser->like_num],
						'like_remark_num' => (object) ['op' => '-=', 'pat' => $oUser->like_remark_num],
						'do_remark_num' => (object) ['op' => '-=', 'pat' => $oUser->do_remark_num],
						'do_like_num' => (object) ['op' => '-=', 'pat' => $oUser->do_like_num],
						'do_like_remark_num' => (object) ['op' => '-=', 'pat' => $oUser->do_like_remark_num],
						'user_total_coin' => (object) ['op' => '-=', 'pat' => $oUser->user_total_coin],
					],
					['mission_id' => $oApp->mission_id, 'userid' => $oUser->userid, 'state' => 1]
				);
			}
		}
		$rst = $this->update(
			'xxt_enroll_user',
			[
				'state' => 0,
				'enroll_num' => 0,
				'remark_num' => 0,
				'like_num' => 0,
				'like_remark_num' => 0,
				'do_remark_num' => 0,
				'do_like_num' => 0,
				'do_like_remark_num' => 0,
				'user_total_coin' => 0,
			],
			['aid' => $oApp->id]
		);
		$this->update(
			'xxt_enroll_record_remark',
			['state' => 0],
			['aid' => $oApp->id, 'state' => 1]
		);
		$this->update(
			'xxt_enroll_record_data',
			['state' => 0],
			['aid' => $oApp->id, 'state' => 1]
		);
		$rst = $this->update(
			'xxt_enroll_record',
			['state' => 0],
			['aid' => $oApp->id, 'state' => 1]
		);

		return $rst;
	}
	/**
	 *  恢复一条登记记录
	 *
	 * @param object $oApp
	 * @param object $oRecord
	 *
	 */
	public function restore($oApp, $oRecord) {
		$rst = $this->update(
			'xxt_enroll_record',
			['state' => 1],
			['aid' => $oApp->id, 'state' => (object) ['op' => '<>', 'pat' => 1], 'enroll_key' => $oRecord->enroll_key]
		);
		if ($rst !== 1) {
			return $rst;
		}
		$this->update(
			'xxt_enroll_record_data',
			['state' => 1],
			['aid' => $oApp->id, 'enroll_key' => $oRecord->enroll_key]
		);
		/* 更新用户的累计数据 */
		if (!empty($oRecord->userid)) {
			$this->model('matter\enroll\user')->restoreRecord($oApp, $oRecord);
			if (!empty($oApp->mission_id)) {
				$this->model('matter\mission\user')->restoreRecord($oApp->mission_id, $oRecord);
			}
		}

		return $rst;
	}
	/**
	 * 统计选择题、记分题汇总信息
	 */
	public function &getStat($appIdOrObj, $rid = '', $renewCache = 'Y') {
		if (is_string($appIdOrObj)) {
			$oApp = $this->model('matter\enroll')->byId($appIdOrObj, ['fields' => 'id,data_schemas,round_cron,sync_mission_round', 'cascaded' => 'N']);
		} else {
			$oApp = $appIdOrObj;
		}
		if (empty($rid)) {
			if ($activeRound = $this->model('matter\enroll\round')->getActive($oApp)) {
				$rid = $activeRound->rid;
			}
		}

		$current = time();
		if ($renewCache === 'Y') {
			/* 上一次保留统计结果的时间，每条记录的时间都一样 */
			$q = [
				'create_at',
				'xxt_enroll_record_stat',
				['aid' => $oApp->id, 'rid' => $rid],
			];

			$q2 = ['r' => ['o' => 0, 'l' => 1]];
			$last = $this->query_objs_ss($q, $q2);
			/* 上次统计后的新登记记录数 */
			if (count($last) === 1) {
				$last = $last[0];
				$q = [
					'count(*)',
					'xxt_enroll_record',
					"aid='$oApp->id' and state=1 and enroll_at>={$last->create_at}",
				];
				if (!empty($rid) && $rid !== 'ALL') {
					$q[2] .= " and rid = '$rid'";
				}

				$newCnt = (int) $this->query_val_ss($q);
			} else {
				$newCnt = 999;
			}
			// 如果更新的登记数据，重新计算统计结果
			if ($newCnt > 0) {
				$aResult = $this->_calcStat($oApp, $rid);
				// 保存统计结果
				$this->delete(
					'xxt_enroll_record_stat',
					['aid' => $oApp->id, 'rid' => $rid]
				);
				foreach ($aResult as $id => $oDataBySchema) {
					foreach ($oDataBySchema->ops as $op) {
						$r = [
							'siteid' => $oApp->siteid,
							'aid' => $oApp->id,
							'create_at' => $current,
							'id' => $id,
							'title' => $oDataBySchema->title,
							'v' => $op->v,
							'l' => $op->l,
							'c' => $op->c,
							'rid' => $rid,
						];
						$this->insert('xxt_enroll_record_stat', $r);
					}
				}
			} else {
				/* 从缓存中获取统计数据 */
				$aResult = [];
				$q = [
					'id,title,v,l,c',
					'xxt_enroll_record_stat',
					['aid' => $oApp->id, 'rid' => $rid],
				];
				$aCached = $this->query_objs_ss($q);
				foreach ($aCached as $oDataByOp) {
					if (empty($aResult[$oDataByOp->id])) {
						$oDataBySchema = (object) [
							'id' => $oDataByOp->id,
							'title' => $oDataByOp->title,
							'ops' => [],
							'sum' => 0,
						];
						$aResult[$oDataByOp->id] = $oDataBySchema;
					} else {
						$oDataBySchema = $aResult[$oDataByOp->id];
					}
					$oOp = (object) [
						'v' => $oDataByOp->v,
						'l' => $oDataByOp->l,
						'c' => $oDataByOp->c,
					];
					$oDataBySchema->ops[] = $oOp;
					$oDataBySchema->sum += $oOp->c;
				}
			}
		} else {
			$aResult = $this->_calcStat($oApp, $rid);
		}

		// 对选择题和打分题选项排序
		$oSchemas = new \stdClass;
		foreach ($oApp->dynaDataSchemas as $oSchema) {
			$oSchemas->{$oSchema->id} = $oSchema;
		}
		foreach ($aResult as $key => &$value) {
			if (isset($oSchemas->{$key}) && in_array($oSchemas->{$key}->type, ['single', 'multiple', 'score'])) {
				$ops = $value->ops;
				$sortArr = [];
				foreach ($ops as $op) {
					$sortArr[] = $op->v;
				}
				array_multisort($sortArr, SORT_ASC, SORT_NATURAL, $ops);
				$value->ops = $ops;
			}
		}

		return $aResult;
	}
	/**
	 * 统计选择题、记分题汇总信息
	 */
	private function &_calcStat($oApp, $rid) {
		$aResult = [];

		$dataSchemas = $oApp->dynaDataSchemas;
		foreach ($dataSchemas as $oSchema) {
			if (!in_array($oSchema->type, ['single', 'multiple', 'phase', 'score', 'multitext'])) {
				continue;
			}
			$aResult[$oSchema->id] = $oDataBySchema = (object) [
				'title' => isset($oSchema->title) ? $oSchema->title : '',
				'id' => $oSchema->id,
				'ops' => [],
			];
			$oDataBySchema->sum = 0;
			if (in_array($oSchema->type, ['single', 'phase'])) {
				foreach ($oSchema->ops as $op) {
					/**
					 * 获取数据
					 */
					$q = [
						'count(*)',
						'xxt_enroll_record_data',
						['aid' => $oApp->id, 'state' => 1, 'schema_id' => $oSchema->id, 'value' => $op->v],
					];
					if (!empty($rid) && $rid !== 'ALL') {
						$q[2]['rid'] = $rid;
					}
					$op->c = (int) $this->query_val_ss($q);
					$oDataBySchema->ops[] = $op;
					$oDataBySchema->sum += $op->c;
				}
			} else if ($oSchema->type === 'multiple') {
				foreach ($oSchema->ops as $op) {
					/**
					 * 获取数据
					 */
					$q = [
						'count(*)',
						'xxt_enroll_record_data',
						"aid='$oApp->id' and state=1 and schema_id='{$oSchema->id}' and FIND_IN_SET('{$op->v}', value)",
					];
					if (!empty($rid) && $rid !== 'ALL') {
						$q[2] .= " and rid = '$rid'";
					}
					$op->c = (int) $this->query_val_ss($q);
					$oDataBySchema->ops[] = $op;
					$oDataBySchema->sum += $op->c;
				}
			} else if ($oSchema->type === 'score') {
				$scoreByOp = [];
				foreach ($oSchema->ops as &$op) {
					$op->c = 0;
					$oDataBySchema->ops[] = $op;
					$scoreByOp[$op->v] = $op;
				}
				// 计算总分数
				$q = [
					'value',
					'xxt_enroll_record_data',
					['aid' => $oApp->id, 'state' => 1, 'schema_id' => $oSchema->id],
				];
				if (!empty($rid) && $rid !== 'ALL') {
					$q[2]['rid'] = $rid;
				}

				$values = $this->query_objs_ss($q);
				foreach ($values as $oValue) {
					if (!empty($oValue->value)) {
						$oValue = json_decode($oValue->value);
						if (!empty($oValue) && is_object($oValue)) {
							foreach ($oValue as $opKey => $opValue) {
								if (isset($scoreByOp[$opKey]->c)) {
									$scoreByOp[$opKey]->c += (int) $opValue;
								}
							}
						}
					}
				}
				// 计算平均分
				if ($rowNumber = count($values)) {
					foreach ($oSchema->ops as &$op) {
						$op->c = $op->c / $rowNumber;
					}
				} else {
					$op->c = 0;
				}
				$oDataBySchema->sum += $op->c;
			}
		}

		return $aResult;
	}
}