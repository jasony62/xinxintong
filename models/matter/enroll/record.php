<?php
namespace matter\enroll;

require_once dirname(__FILE__) . '/record_base.php';
/**
 * 登记活动记录
 */
class record_model extends record_base {
	/**
	 * 活动登记（不包括登记数据）
	 *
	 * @param object $app
	 * @param object $oUser [uid,nickname]
	 */
	public function enroll(&$oApp, $oUser = null, $aOptions = []) {
		$referrer = isset($aOptions['referrer']) ? $aOptions['referrer'] : '';
		$enrollAt = isset($aOptions['enrollAt']) ? $aOptions['enrollAt'] : time();
		isset($aOptions['assignRid']) && $assignRid = $aOptions['assignRid'];

		$ek = $this->genKey($oApp->siteid, $oApp->id);

		$record = [
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
			$record['rid'] = $assignRid;
		} else if ($oActiveRound = $modelRun->getActive($oApp)) {
			$record['rid'] = $oActiveRound->rid;
		}
		/* 登记用户昵称 */
		if (isset($aOptions['nickname'])) {
			$record['nickname'] = $this->escape($aOptions['nickname']);
		} else {
			$nickname = $this->model('matter\enroll')->getUserNickname($oApp, $oUser, $aOptions);
			$record['nickname'] = $this->escape($nickname);
		}
		/* 登记用户的社交账号信息 */
		if (!empty($oUser)) {
			$oUserOpenids = $this->model('site\user\account')->byId($oUser->uid, ['fields' => 'wx_openid,yx_openid,qy_openid']);
			if ($oUserOpenids) {
				$record['wx_openid'] = $oUserOpenids->wx_openid;
				$record['yx_openid'] = $oUserOpenids->yx_openid;
				$record['qy_openid'] = $oUserOpenids->qy_openid;
			}
		}

		/* 移动用户未签到的原因 */
		if (!empty($oUser->uid)) {
			$rid = !empty($record['rid']) ? $record['rid'] : 'ALL';
			if (isset($oApp->absent_cause->{$oUser->uid}) && isset($oApp->absent_cause->{$oUser->uid}->{$rid})) {
				$record['comment'] = $this->escape($oApp->absent_cause->{$oUser->uid}->{$rid});
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

		$this->insert('xxt_enroll_record', $record, false);

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
	public function setData($oUser, &$oApp, $ek, $submitData, $submitkey = '', $firstSubmit = false, $assignScore = null) {
		if (empty($submitData)) {
			return [true];
		}
		// 登记记录
		$oRecord = $this->byId($ek);
		if (false === $oRecord) {
			return [false, '指定的对象不存在'];
		}
		/*new*/
		$oResult = $this->model('matter\enroll\data')->setData($oUser, $oApp, $oRecord, $submitData, $submitkey);
		/*new*/
		/* 更新在登记记录上记录数据 */
		$oRecordUpdated = new \stdClass;
		$oRecordUpdated->data = $this->escape($this->toJson($oResult->dbData));
		if (count(get_object_vars($oResult->score)) > 1) {
			$oRecordUpdated->score = $this->escape($this->toJson($oResult->score));
		}
		/* 记录提交日志 */
		if ($firstSubmit === false) {
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
	 * @param array $submitTag 用户提交的填写项标签
	 */
	public function setTag($oUser, &$oApp, $ek, $submitTag) {
		$wholeTags = new \stdClass;
		/*record data*/
		foreach ($submitTag as $schemaId => $tags) {
			/*题目中对应的标签*/
			$tagOlds = [];
			$q = [
				'id,tag',
				'xxt_enroll_record_data',
				['enroll_key' => $ek, 'schema_id' => $schemaId, 'state' => 1, 'multitext_seq' => 0],
			];
			if ($recordData = $this->query_obj_ss($q)) {
				!empty($recordData->tag) && $tagOlds = json_decode($recordData->tag);
			}

			/* 保证以字符串的格式存储标签id，便于以后检索 */
			$jsonTags = [];
			$tagAdd = []; //对比上一次新增的标签
			foreach ($tags as $oTag) {
				if (($key = array_search($oTag->id, $tagOlds)) === false) {
					$tagAdd[] = $oTag->id;
				} else {
					/*如果有剩余的标签说明是对比上一次本次不使用的标签，其使用数量应该减 1*/
					unset($tagOlds[$key]);
				}

				$jsonTags[] = (string) $oTag->id;
			}
			if (!empty($tagAdd)) {
				$updateAddWhere = "(" . implode(',', $tagAdd) . ")";
				$this->update("update xxt_enroll_record_tag set use_num = use_num +1 where id in $updateAddWhere");
			}
			if (!empty($tagOlds)) {
				$updateDelWhere = "(" . implode(',', $tagOlds) . ")";
				$this->update("update xxt_enroll_record_tag set use_num = use_num -1 where id in $updateDelWhere");
			}

			$wholeTags->{$schemaId} = $jsonTags;
			$jsonTags = json_encode($jsonTags);
			$rst = $this->update(
				'xxt_enroll_record_data',
				['tag' => $this->escape($jsonTags)],
				['id' => $recordData->id]
			);
		}

		$rst = $this->update(
			'xxt_enroll_record',
			['data_tag' => $this->escape(json_encode($wholeTags))],
			['enroll_key' => $ek, 'state' => 1]
		);

		return $rst;
	}
	/**
	 * 保存登记的数据
	 *
	 * @param object $oUser [uid]
	 * @param object $oApp
	 * @param string $ek
	 * @param array $submitSupp 用户提交的补充说明
	 */
	public function setSupplement($oUser, &$oApp, $ek, $submitSupp) {
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
	 *
	 */
	private function _processRecord(&$oRecord, $fields, $verbose = 'Y') {
		if (property_exists($oRecord, 'data')) {
			$oRecord->data = empty($oRecord->data) ? new \stdClass : json_decode($oRecord->data);
		}
		if (property_exists($oRecord, 'data_tag')) {
			$oRecord->data_tag = empty($oRecord->data_tag) ? new \stdClass : json_decode($oRecord->data_tag);
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
	public function byUser(&$oApp, &$oUser, $aOptions = []) {
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

		$schemas = is_object($oApp->data_schemas) ? $oApp->data_schemas : json_decode($oApp->data_schemas);
		foreach ($schemas as $schema) {
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
	 * @param object/string 登记活动/登记活动的id
	 * @param object/array $aOptions
	 * --creater openid
	 * --visitor openid
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
	public function byApp($oApp, $oOptions = null, $oCriteria = null) {
		if (is_string($oApp)) {
			$oApp = $this->model('matter\enroll')->byId($oApp, ['cascaded' => 'N']);
		}
		if (empty($oApp)) {
			return false;
		}
		// 数值型的填空题需要计算分值
		$bRequireScore = false;
		$oSchemasById = new \stdClass;
		foreach ($oApp->dataSchemas as $oSchema) {
			$oSchemasById->{$oSchema->id} = $oSchema;
			if ($oSchema->type == 'shorttext' && isset($oSchema->format) && $oSchema->format === 'number') {
				$bRequireScore = true;
				break;
			}
		}

		if ($oOptions) {
			is_array($oOptions) && $oOptions = (object) $oOptions;
			$creator = isset($oOptions->creator) ? $oOptions->creator : null;
			$orderby = isset($oOptions->orderby) ? $oOptions->orderby : '';
			$page = isset($oOptions->page) ? $oOptions->page : null;
			$size = isset($oOptions->size) ? $oOptions->size : null;
		}
		$result = new \stdClass; // 返回的结果
		$result->total = 0;

		// 指定登记活动下的登记记录
		$w = "r.state=1 and r.aid='{$oApp->id}'";

		// 指定轮次，或者当前激活轮次
		if (!empty($oOptions->rid)) {
			if (strcasecmp('all', $oOptions->rid) !== 0) {
				$rid = $oOptions->rid;
			}
		} else if (isset($oCriteria->record->assignRid)) {
			$rid = $oCriteria->record->assignRid;
		} else if (!empty($oCriteria->record->rid)) {
			if (strcasecmp('all', $oCriteria->record->rid) !== 0) {
				$rid = $oCriteria->record->rid;
			}
		} else if ($oActiveRnd = $this->model('matter\enroll\round')->getActive($oApp)) {
			$rid = $oActiveRnd->rid;
		}
		isset($rid) && $w .= " and r.rid='$rid'";

		/* 根据用户分组过滤 */
		if (!empty($oOptions->userGroup)) {
			$w .= " and r.group_id='{$oOptions->userGroup}'";
		}
		// 根据填写人筛选（填写端列表页需要）
		if (!empty($creator)) {
			$w .= " and r.userid='$creator'";
		}

		// 指定了登记记录属性过滤条件
		if (!empty($oCriteria->record)) {
			$whereByRecord = '';
			if (!empty($oCriteria->record->verified)) {
				$whereByRecord .= " and verified='{$oCriteria->record->verified}'";
			}
			$w .= $whereByRecord;
		}

		// 指定了记录标签
		if (!empty($oCriteria->tags)) {
			$whereByTag = '';
			foreach ($oCriteria->tags as $tag) {
				$whereByTag .= " and concat(',',r.tags,',') like '%,$tag,%'";
			}
			$w .= $whereByTag;
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
		if (!empty($oOptions->keyword) || !empty($oCriteria->keyword)) {
			$keyword = !empty($oOptions->keyword) ? $oOptions->keyword : $oCriteria->keyword;
			$whereByData = '';
			$whereByData .= ' and (data like \'%' . $keyword . '%\')';
			$w .= $whereByData;
		}

		// 查询参数
		$q = [
			'r.enroll_key,r.rid,r.enroll_at,r.tags,r.userid,r.group_id,r.nickname,r.wx_openid,r.yx_openid,r.qy_openid,r.headimgurl,r.verified,r.comment,r.data,r.supplement,r.data_tag,r.agreed,r.like_num,r.like_log,remark_num',
			"xxt_enroll_record r",
			$w,
		];

		//测验场景或数值填空题共用score字段
		if ($oApp->scenario === 'quiz' || $bRequireScore) {
			$q[0] .= ',r.score';
		}

		$q2 = [];
		// 查询结果分页
		if (!empty($page) && !empty($size)) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}
		// 查询结果排序
		if (!empty($oCriteria->order->orderby) && !empty($oCriteria->order->schemaId)) {
			$schemaId = $oCriteria->order->schemaId;
			$orderby = $oCriteria->order->orderby;
			$q[1] .= ",xxt_enroll_record_data d";
			$q[2] .= " and r.enroll_key = d.enroll_key and d.schema_id = '$schemaId' and d.multitext_seq = 0";
			$q2['o'] = 'd.' . $orderby . ' desc';
		} elseif (!empty($oCriteria->order->orderby) && $oCriteria->order->orderby === 'sum') {
			$q2['o'] = 'r.score desc';
		} else {
			$q2['o'] = 'r.enroll_at desc';
		}
		/* 处理获得的数据 */
		$aRoundsById = []; // 缓存轮次数据
		if ($records = $this->query_objs_ss($q, $q2)) {
			foreach ($records as $oRec) {
				$oRec->like_log = empty($oRec->like_log) ? new \stdClass : json_decode($oRec->like_log);
				$oRec->data_tag = empty($oRec->data_tag) ? new \stdClass : json_decode($oRec->data_tag);
				$data = str_replace("\n", ' ', $oRec->data);
				$data = json_decode($data);
				//测验场景或数值填空题共用score字段
				if (($oApp->scenario === 'quiz' || $bRequireScore) && !empty($oRec->score)) {
					$score = str_replace("\n", ' ', $oRec->score);
					$score = json_decode($score);

					if ($score === null) {
						$oRec->score = 'json error(' . json_last_error_msg() . '):' . $oRec->score;
					} else {
						$oRec->score = $score;
					}
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
				}
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
				// 记录的分数
				if ($oApp->scenario === 'voting' || $oApp->scenario === 'common') {
					if (!isset($scoreSchemas)) {
						$scoreSchemas = $this->_mapOfScoreSchema($oApp);
						$countScoreSchemas = count(array_keys((array) $scoreSchemas));
					}
					$oRec->_score = $this->_calcVotingScore($scoreSchemas, $data);
					$oRec->_average = $countScoreSchemas === 0 ? 0 : $oRec->_score / $countScoreSchemas;
				}
			}
			$result->records = $records;

			// 符合条件的数据总数
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
	 * --rid 轮次id
	 *
	 * return
	 */
	public function enrolleeByApp($oApp, $options = null, $criteria = null) {
		if ($options) {
			is_array($options) && $options = (object) $options;
			$rid = null;
			if (!empty($options->rid)) {
				if (strcasecmp($options->rid, 'all') === 0) {
					$rid = null;
				} else if (!empty($options->rid)) {
					$rid = $options->rid;
				}
			} else if ($activeRound = \TMS_APP::M('matter\enroll\round')->getActive($oApp)) {
				$rid = $activeRound->rid;
			}
		}
		$fields = isset($options->fields) ? $options->fields : 'enroll_key,userid';

		$w = "state=1 and aid='{$oApp->id}'";
		if (!empty($options->userid)) {
		} else {
			$w .= " and userid<>''";
		}

		// 按轮次过滤
		!empty($rid) && $w .= " and e.rid='$rid'";

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

		// 获得填写的登记数据
		$q = [
			$fields,
			"xxt_enroll_record e",
			$w,
		];
		$enrollees = $this->query_objs_ss($q);

		return $enrollees;
	}
	/**
	 * 已删除的登记清单
	 *
	 * 1、如果活动仅限会员报名，那么要叠加会员信息
	 * 2、如果报名的表单中有扩展信息，那么要提取扩展信息
	 *
	 * $siteId
	 * $aid
	 * $options
	 * --page
	 * --size
	 * --rid 轮次id
	 *
	 *
	 * return
	 * [0] 数据列表
	 * [1] 数据总条数
	 * [2] 数据项的定义
	 */
	public function recycle($siteId, &$app, $options = null) {
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
			} else if ($activeRound = $this->M('matter\enroll\round')->getActive($app)) {
				$rid = $activeRound->rid;
			}
		}
		$result = new \stdClass; // 返回的结果
		$result->total = 0;

		// 指定登记活动下的登记记录
		$w = "(e.state=100 or e.state=101 or e.state=0) and e.aid='{$app->id}'";

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
				if ($app->scenario === 'voting') {
					if (!isset($scoreSchemas)) {
						$scoreSchemas = $this->_mapOfScoreSchema($app);
						$countScoreSchemas = count(array_keys((array) $scoreSchemas));
					}
					$r->_score = $this->_calcVotingScore($scoreSchemas, $data);
					$r->_average = $countScoreSchemas === 0 ? 0 : $r->_score / $countScoreSchemas;
				}
			}
			$result->records = $records;

			// 符合条件的数据总数
			$q[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q);
			$result->total = $total;
		}

		return $result;
	}
	/**
	 * 返回指定登记项的登记记录
	 *
	 */
	public function list4Schema(&$oApp, $schemaId, $options = null) {
		$schemaId = $this->escape($schemaId);
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

		$result = new \stdClass; // 返回的结果
		$result->records = [];
		$result->total = 0;

		// 查询参数
		$q = [
			'enroll_key,value,like_log,like_num',
			"xxt_enroll_record_data",
			"state=1 and aid='{$oApp->id}' and schema_id='{$schemaId}' and value<>'' and multitext_seq = 0",
		];
		if ($oDataSchema->type === 'date') {

		}
		/* 指定用户 */
		if (!empty($options->owner)) {
			$q[2] .= " and userid='" . $options->owner . "'";
		}
		/* 指定登记轮次 */
		if (!empty($rid)) {
			if ($rid !== 'ALL') {
				$q[2] .= " and rid='{$rid}'";
			}
		} else {
			/* 没有指定轮次，就使用当前轮次 */
			if ($activeRound = $this->model('matter\enroll\round')->getActive($oApp)) {
				$q[2] .= " and rid='{$activeRound->rid}'";
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
				$result->sum = $sum;
			}
			/* 补充记录标识 */
			if (!isset($oApp->rpConfig) || empty($oApp->rpConfig->marks)) {
				$defaultMark = new \stdClass;
				$defaultMark->id = 'nickname';
				$defaultMark->name = 'nickname';
				$marks = [$defaultMark];
			} else {
				$marks = $oApp->rpConfig->marks;
			}
			foreach ($records as &$record) {
				$rec = $this->byId($record->enroll_key, ['fields' => 'rid,nickname,data,enroll_at']);
				$rec->enroll_key = $record->enroll_key;
				$rec->like_log = empty($record->like_log) ? new \stdClass : json_decode($record->like_log);
				$rec->like_num = $record->like_num;
				$result->records[] = $rec;
			}
		}

		// 符合条件的数据总数
		$q[0] = 'count(*)';
		$total = (int) $this->query_val_ss($q, $q2);
		$result->total = $total;

		return $result;
	}
	/**
	 * 计算指定登记项所有记录的合计
	 */
	public function sum4Schema($oApp, $rid = 'ALL') {
		if (empty($oApp->data_schemas)) {
			return false;
		}

		$result = new \stdClass;
		$dataSchemas = json_decode($oApp->data_schemas);
		if (empty($rid)) {
			if ($activeRound = $this->model('matter\enroll\round')->getActive($oApp)) {
				$rid = $activeRound->rid;
			}
		}
		/* 每道题目的合计 */
		foreach ($dataSchemas as $schema) {
			if (isset($schema->format) && $schema->format === 'number') {
				$q = [
					'sum(value)',
					'xxt_enroll_record_data',
					['aid' => $oApp->id, 'schema_id' => $schema->id, 'state' => 1],
				];
				$rid !== 'ALL' && !empty($rid) && $q[2]['rid'] = $rid;

				$sum = (float) $this->query_val_ss($q);
				$sum = number_format($sum, 2, '.', '');
				$result->{$schema->id} = (float) $sum;
			}
		}

		return $result;
	}
	/**
	 * 计算指定登记项所有记录的合计
	 */
	public function score4Schema($oApp, $rid = 'ALL') {
		if (empty($oApp->data_schemas)) {
			return false;
		}

		$result = new \stdClass;
		$dataSchemas = isset($oApp->dataSchemas) ? $oApp->dataSchemas : json_decode($oApp->data_schemas);
		if (empty($rid)) {
			if ($activeRound = $this->model('matter\enroll\round')->getActive($oApp)) {
				$rid = $activeRound->rid;
			}
		}
		/* 每道题目的得分 */
		foreach ($dataSchemas as $oSchema) {
			if ((isset($oSchema->requireScore) && $oSchema->requireScore === 'Y') || (isset($oSchema->format) && $oSchema->format === 'number')) {
				$q = [
					'sum(score)',
					'xxt_enroll_record_data',
					['aid' => $oApp->id, 'schema_id' => $oSchema->id, 'state' => 1],
				];
				$rid !== 'ALL' && !empty($rid) && $q[2]['rid'] = $rid;

				$sum = (float) $this->query_val_ss($q);
				$sum = number_format($sum, 2, '.', '');
				$result->{$oSchema->id} = (float) $sum;
			}
		}

		/*所有题的得分合计*/
		$q = [
			'sum(score)',
			'xxt_enroll_record_data',
			['aid' => $oApp->id, 'state' => 1],
		];
		$rid !== 'ALL' && !empty($rid) && $q[2]['rid'] = $rid;

		$sum = (float) $this->query_val_ss($q);
		$sum = number_format($sum, 2, '.', '');
		$result->sum = (float) $sum;

		return $result;
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
				'userid,enroll_num,remark_num,like_num,like_remark_num,remark_other_num,like_other_num,like_other_remark_num,user_total_coin',
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
						'remark_other_num' => (object) ['op' => '-=', 'pat' => $oUser->remark_other_num],
						'like_other_num' => (object) ['op' => '-=', 'pat' => $oUser->like_other_num],
						'like_other_remark_num' => (object) ['op' => '-=', 'pat' => $oUser->like_other_remark_num],
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
				'remark_other_num' => 0,
				'like_other_num' => 0,
				'like_other_remark_num' => 0,
				'user_total_coin' => 0,
			],
			['aid' => $oApp->id]
		);
		$this->update(
			'xxt_enroll_record_tag',
			['state' => 0],
			['aid' => $oApp->id, 'state' => 1]
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
			$oApp = $this->model('matter\enroll')->byId($appIdOrObj, ['fields' => 'id,data_schemas,round_cron', 'cascaded' => 'N']);
		} else {
			$oApp = $appIdOrObj;
		}
		if (empty($rid)) {
			if ($activeRound = $this->model('matter\enroll\round')->getActive($oApp)) {
				$rid = $activeRound->rid;
			}
		} elseif ($rid !== 'ALL') {
			$rid = $rid;
		}

		$current = time();
		$rid = $this->escape($rid);
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
				if ($rid !== 'ALL' && !empty($rid)) {
					$q[2] .= " and rid = '$rid'";
				}

				$newCnt = (int) $this->query_val_ss($q);
			} else {
				$newCnt = 999;
			}
			// 如果更新的登记数据，重新计算统计结果
			if ($newCnt > 0) {
				$result = $this->_calcStat($oApp, $rid);
				// 保存统计结果
				$this->delete(
					'xxt_enroll_record_stat',
					['aid' => $oApp->id, 'rid' => $rid]
				);
				foreach ($result as $id => $oDataBySchema) {
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
				$result = [];
				$q = [
					'id,title,v,l,c',
					'xxt_enroll_record_stat',
					['aid' => $oApp->id, 'rid' => $rid],
				];
				$aCached = $this->query_objs_ss($q);
				foreach ($aCached as $oDataByOp) {
					if (empty($result[$oDataByOp->id])) {
						$oDataBySchema = (object) [
							'id' => $oDataByOp->id,
							'title' => $oDataByOp->title,
							'ops' => [],
							'sum' => 0,
						];
						$result[$oDataByOp->id] = $oDataBySchema;
					} else {
						$oDataBySchema = $result[$oDataByOp->id];
					}
					$op = (object) [
						'v' => $oDataByOp->v,
						'l' => $oDataByOp->l,
						'c' => $oDataByOp->c,
					];
					$oDataBySchema->ops[] = $op;
					$oDataBySchema->sum += $op->c;
				}
			}
		} else {
			$result = $this->_calcStat($oApp, $rid);
		}

		return $result;
	}
	/**
	 * 统计选择题、记分题汇总信息
	 */
	private function &_calcStat($oApp, $rid) {
		$result = [];

		$dataSchemas = $oApp->dataSchemas;
		foreach ($dataSchemas as $oSchema) {
			if (!in_array($oSchema->type, ['single', 'multiple', 'phase', 'score', 'multitext'])) {
				continue;
			}
			$result[$oSchema->id] = $oDataBySchema = (object) [
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
					if (isset($rid)) {
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
					if (isset($rid)) {
						$rid = $this->escape($rid);
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
				if (isset($rid)) {
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

		return $result;
	}
	/**
	 * 获得schemasB中和schemasA兼容的登记项定义及对应关系
	 *
	 * 从目标应用中导入和指定应用的数据定义中名称（title）和类型（type）一致的项
	 * 如果是单选题、多选题、打分题选项必须一致
	 * 如果是打分题，分值设置范围必须一致
	 * name,email,mobile,shorttext,longtext认为是同一种类型
	 * 忽略：项目阶段，说明描述
	 */
	public function compatibleSchemas($schemasA, $schemasB) {
		if (empty($schemasB) || empty($schemasA)) {
			return [];
		}
		$mapOfCompatibleType = [
			'shorttext' => 'text',
			'longtext' => 'text',
			'name' => 'text',
			'email' => 'text',
			'mobile' => 'text',
			'location' => 'text',
			'date' => 'text',
			'single' => 'single',
			'multiple' => 'multiple',
			'score' => 'score',
			'file' => 'file',
			'image' => 'image',
		];
		$mapAByType = [];
		foreach ($schemasA as $schemaA) {
			if (!isset($mapOfCompatibleType[$schemaA->type])) {
				continue;
			}
			$compatibleType = $mapOfCompatibleType[$schemaA->type];
			if (!isset($mapAByType[$compatibleType])) {
				$mapAByType[$compatibleType] = [];
			}
			$mapAByType[$compatibleType][] = $schemaA;
		}

		$result = [];
		foreach ($schemasB as $schemaB) {
			if (!isset($mapOfCompatibleType[$schemaB->type])) {
				continue;
			}
			$compatibleType = $mapOfCompatibleType[$schemaB->type];
			if (!isset($mapAByType[$compatibleType])) {
				continue;
			}
			foreach ($mapAByType[$compatibleType] as $schemaA) {
				if ($schemaA->title !== $schemaB->title) {
					continue;
				}
				if ($compatibleType === 'single' || $compatibleType === 'multiple' || $compatibleType === 'score') {
					if (count($schemaA->ops) !== count($schemaB->ops)) {
						continue;
					}
					$isCompatible = true;
					for ($i = 0, $ii = count($schemaA->ops); $i < $ii; $i++) {
						if ($schemaA->ops[$i]->l !== $schemaB->ops[$i]->l) {
							$isCompatible = false;
							break;
						}
					}
					if ($isCompatible === false) {
						continue;
					}
				}
				$result[] = [$schemaB, $schemaA];
			}
		}

		return $result;
	}
}