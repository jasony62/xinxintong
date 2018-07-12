<?php
namespace pl\fe\matter\enroll;

require_once dirname(__FILE__) . '/main_base.php';
/*
 * 登记记录
 */
class record extends main_base {
	/**
	 * 活动登记名单
	 *
	 */
	public function get_action($ek) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$mdoelRec = $this->model('matter\enroll\record');
		$oRecord = $mdoelRec->byId($ek, ['verbose' => 'Y']);
		if ($oRecord) {
			$modelApp = $this->model('matter\enroll');
			$oApp = $modelApp->byId($oRecord->aid);
			$dataSchemas = new \stdClass;
			foreach ($oApp->dataSchemas as $schema) {
				$dataSchemas->{$schema->id} = $schema;
			}
			foreach ($oRecord->data as $k => $data) {
				if (isset($dataSchemas->{$k}) && $dataSchemas->{$k}->type === 'multitext') {
					$verboseVals = json_decode($oRecord->verbose->{$k}->value);
					$items = [];
					foreach ($verboseVals as $verboseVal) {
						$res = $this->model('matter\enroll\data')->byId($verboseVal->id);
						$items[] = $res;
					}
					$oRecord->verbose->{$k}->items = $items;
				}
			}
		}

		return new \ResponseData($oRecord);
	}
	/**
	 * 活动登记名单
	 *
	 */
	public function list_action($app, $page = 1, $size = 30) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}
		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$oEnrollApp = $modelApp->byId($app, ['cascaded' => 'N']);

		// 登记数据过滤条件
		$oCriteria = $this->getPostJson();

		// 登记记录过滤条件
		$aOptions = [
			'page' => $page,
			'size' => $size,
		];
		if (!empty($oCriteria->keyword)) {
			$aOptions->keyword = $oCriteria->keyword;
			unset($oCriteria->keyword);
		}

		// 查询结果
		$modelRec = $this->model('matter\enroll\record');
		$oResult = $modelRec->byApp($oEnrollApp, $aOptions, $oCriteria);
		if (!empty($oResult->records)) {
			$remarkables = [];
			$bRequireScore = false;
			foreach ($oEnrollApp->dynaDataSchemas as $oSchema) {
				if (isset($oSchema->remarkable) && $oSchema->remarkable === 'Y') {
					$remarkables[] = $oSchema->id;
				}
				if (isset($oSchema->requireScore) && $oSchema->requireScore == 'Y') {
					$bRequireScore = true;
				}
			}
			if (count($remarkables)) {
				foreach ($oResult->records as $oRec) {
					$modelRem = $this->model('matter\enroll\data');
					$oRecordData = $modelRem->byRecord($oRec->enroll_key, ['schema' => $remarkables]);
					$oRec->verbose = new \stdClass;
					$oRec->verbose->data = $oRecordData;
				}
			}
			if ($bRequireScore) {
				foreach ($oResult->records as $oRec) {
					$one = $modelRec->query_obj_ss([
						'id,score',
						'xxt_enroll_record',
						['siteid' => $oEnrollApp->siteid, 'enroll_key' => $oRec->enroll_key],
					]);
					if (count($one)) {
						$oRec->score = json_decode($one->score);
					} else {
						$oRec->score = new \stdClass;
					}
				}
			}
		}

		return new \ResponseData($oResult);
	}
	/**
	 * 计算指定登记项所有记录的合计
	 * 若不指定登记项，则返回活动中所有数值型登记项的合集
	 * 若指定的登记项不是数值型，返回0
	 */
	public function sum4Schema_action($site, $app, $rid = '', $gid = '') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$enrollApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (false === $enrollApp) {
			return new \ObjectNotFoundError();
		}

		$rid = empty($rid) ? [] : explode(',', $rid);

		// 查询结果
		$modelRec = $this->model('matter\enroll\record');
		$result = $modelRec->sum4Schema($enrollApp, $rid, $gid);

		return new \ResponseData($result);
	}
	/**
	 * 计算指定登记项的得分
	 *
	 * @param string $gid 分组id
	 *
	 */
	public function score4Schema_action($app, $rid = '', $gid = '') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$enrollApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (false === $enrollApp || $enrollApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$rid = empty($rid) ? [] : explode(',', $rid);

		// 查询结果
		$modelRec = $this->model('matter\enroll\record');
		$oResult = $modelRec->score4Schema($enrollApp, $rid, $gid);

		return new \ResponseData($oResult);
	}
	/**
	 * 更新指定活动下所有记录的得分
	 */
	public function renewScore_action($app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$schemasById = []; // 方便获取登记项定义
		foreach ($oApp->dataSchemas as $schema) {
			$schemasById[$schema->id] = $schema;
		}

		$modelRecData = $this->model('matter\enroll\data');
		$renewCount = 0;
		$q = ['id,enroll_key,data,score', 'xxt_enroll_record', ['aid' => $oApp->id]];
		$records = $modelApp->query_objs_ss($q);
		foreach ($records as $oRecord) {
			if (!empty($oRecord->data)) {
				$dbData = json_decode($oRecord->data);
				/* 题目的得分 */
				$oRecordScore = $modelRecData->socreRecordData($oApp, $oRecord, $schemasById, $dbData);
				if ($modelApp->update('xxt_enroll_record', ['score' => json_encode($oRecordScore)], ['id' => $oRecord->id])) {
					unset($oRecordScore->sum);
					foreach ($oRecordScore as $schemaId => $dataScore) {
						$modelApp->update(
							'xxt_enroll_record_data',
							['score' => $dataScore],
							['enroll_key' => $oRecord->enroll_key, 'schema_id' => $schemaId]
						);
					}
					$renewCount++;
				}
			}
		}

		$modelUsr = $this->model('matter\enroll\user');
		$aUpdatedResult = $modelUsr->renew($oApp);

		// 记录操作日志
		$this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'renewScore');

		return new \ResponseData($renewCount);
	}
	/**
	 * 已删除的活动登记名单
	 */
	public function recycle_action($app, $page = 1, $size = 30, $rid = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 登记记录过滤条件
		$aOptions = [
			'page' => $page,
			'size' => $size,
			'rid' => $rid,
		];

		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$enrollApp = $modelApp->byId($app);

		// 查询结果
		$modelRec = $this->model('matter\enroll\record');
		$result = $modelRec->recycle($enrollApp, $aOptions);

		return new \ResponseData($result);
	}
	/**
	 * 返回指定登记项的活动登记名单
	 */
	public function list4Schema_action($site, $app, $rid = null, $schema, $page = 1, $size = 10) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 登记记录过滤条件
		$aOptions = [
			'page' => $page,
			'size' => $size,
		];
		if (!empty($rid)) {
			$aOptions['rid'] = $rid;
		}

		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$enrollApp = $modelApp->byId($app);

		// 查询结果
		$modelRec = $this->model('matter\enroll\record');
		$result = $modelRec->list4Schema($enrollApp, $schema, $aOptions);

		return new \ResponseData($result);
	}
	/**
	 * 手工添加登记信息
	 *
	 * @param string $app
	 */
	public function add_action($app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$posted = $this->getPostJson();
		$modelEnl = $this->model('matter\enroll');
		$modelRec = $this->model('matter\enroll\record')->setOnlyWriteDbConn(true);

		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);

		/* 创建登记记录 */
		$aOptions = [];
		!empty($posted->rid) && $aOptions['assignRid'] = $posted->rid;
		$ek = $modelRec->enroll($oApp, '', $aOptions);
		$record = [];
		$record['verified'] = isset($posted->verified) ? $posted->verified : 'N';
		$record['comment'] = isset($posted->comment) ? $posted->comment : '';
		if (isset($posted->tags)) {
			$record['tags'] = $posted->tags;
			$modelEnl->updateTags($oApp->id, $posted->tags);
		}
		$modelRec->update('xxt_enroll_record', $record, "enroll_key='$ek'");

		/* 记录登记数据 */
		$addUser = $this->model('site\fe\way')->who($oApp->siteid);
		$result = $modelRec->setData(null, $oApp, $ek, $posted->data, $addUser->uid, true, isset($posted->quizScore) ? $posted->quizScore : null);

		/* 记录操作日志 */
		$oRecord = $modelRec->byId($ek, ['fields' => 'enroll_key,data,rid']);
		$this->model('matter\log')->matterOp($oApp->siteid, $user, $oApp, 'add', $oRecord);

		/* 返回完整的记录 */
		$oNewRecord = $modelRec->byId($ek, ['verbose' => 'Y']);

		return new \ResponseData($oNewRecord);
	}
	/**
	 * 将记录导入到其他活动
	 *
	 * @param string $app
	 * @param string $targetApp
	 */
	public function exportToOther_action($app, $targetApp) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$oPosted = $this->getPostJson();
		if (empty($oPosted->eks)) {
			return new \ParameterError('没有指定要导出的记录');
		}
		if (empty($oPosted->mappings)) {
			return new \ParameterError('没有指定题目映射关系');
		}

		$modelEnl = $this->model('matter\enroll');
		$modelRec = $this->model('matter\enroll\record');
		$modelUsr = $this->model('matter\enroll\user');

		$oApp = $modelEnl->byId($app, ['fields' => 'siteid,state,mission_id,sync_mission_round']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oTargetApp = $modelEnl->byId($targetApp, ['fields' => '*']);
		if (false === $oTargetApp || $oTargetApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		foreach ($oPosted->eks as $ek) {
			$oRecord = $modelRec->byId($ek, ['fields' => 'userid,nickname,data']);
			if (!$oRecord) {
				continue;
			}

			/* 传递的数据 */
			$oNewRecData = new \stdClass;
			foreach ($oPosted->mappings as $targetSchemaId => $oMapping) {
				if (isset($oMapping->value)) {
					$oNewRecData->{$targetSchemaId} = $oMapping->value;
				} else if (!empty($oMapping->from) && !empty($oRecord->data->{$oMapping->from})) {
					$oNewRecData->{$targetSchemaId} = $oRecord->data->{$oMapping->from};
				}
			}

			/* 模拟用户 */
			$oMockUser = $modelUsr->byId($oTargetApp, $oRecord->userid, ['fields' => 'id,userid,group_id,nickname']);
			if (false === $oMockUser) {
				$oMockUser = $modelUsr->detail($oTargetApp, (object) ['uid' => $oRecord->userid], $oNewRecData);
			}

			/* 在目标活动中创建新记录 */
			$newek = $modelRec->enroll($oTargetApp, $oMockUser);
			$modelRec->setData($oMockUser, $oTargetApp, $newek, $oNewRecData, '', true);
		}

		return new \ResponseData('ok');
	}
	/**
	 * 投票结果导出到其他活动作为记录
	 */
	public function transferVotes_action($app, $targetApp, $round = '') {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$oPosted = $this->getPostJson();

		if (empty($oPosted->targetSchema)) {
			return new \ParameterError('没有指定目标题目');
		}
		if (empty($oPosted->votingSchemas)) {
			return new \ParameterError('没有指定投票题目');
		}

		$modelEnl = $this->model('matter\enroll');
		$modelRec = $this->model('matter\enroll\record');
		$modelUsr = $this->model('matter\enroll\user');

		$oApp = $modelEnl->byId($app, ['fields' => 'siteid,state,mission_id,sync_mission_round,round_cron,data_schemas', 'appRid' => $round]);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		/* 指定的投票题目 */
		$aVotingSchemas = [];
		foreach ($oApp->dataSchemas as $oSchema) {
			if (in_array($oSchema->id, $oPosted->votingSchemas)) {
				if (in_array($oSchema->type, ['single', 'multiple'])) {
					$aVotingSchemas[] = $oSchema;
				}
			}
		}
		if (empty($aVotingSchemas)) {
			return new \ParameterError('没有指定有效的题目');
		}

		$oTargetApp = $modelEnl->byId($targetApp, ['fields' => '*']);
		if (false === $oTargetApp || $oTargetApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		/* 匹配的轮次 */
		$oAssignedRnd = $oApp->appRound;
		$modelRnd = $this->model('matter\enroll\round');
		if ($oAssignedRnd) {
			$oTargetAppRnd = $modelRnd->byMissionRid($oTargetApp, $oAssignedRnd->mission_rid, ['fields' => 'rid,mission_rid']);
		}
		/* 目标活动的投票结果 */
		$aVotingData = $modelRec->getStat($oApp, $oAssignedRnd ? $oAssignedRnd->rid : '', 'N');
		$newRecordNum = 0;
		/* 根据投票结果创建记录 */
		foreach ($aVotingSchemas as $oVotingSchema) {
			if (empty($aVotingData[$oVotingSchema->id]->ops)) {
				continue;
			}
			$allOps = $aVotingData[$oVotingSchema->id]->ops;
			usort($allOps, function ($a, $b) {
				return $a->c < $b->c;
			});
			$qualifiedOps = []; // 满足条件的选项
			if (!empty($oPosted->limit->scope) && !empty($oPosted->limit->num) && (int) $oPosted->limit->num) {
				if ($oPosted->limit->scope === 'top') {
					$limitNum = (int) $oPosted->limit->num;
					if ($limitNum > count($allOps)) {
						$limitNum = count($allOps);
					}
					for ($i = 0; $i < $limitNum; $i++) {
						$qualifiedOps[] = $allOps[$i];
					}
				} else if ($oPosted->limit->scope === 'checked') {
					for ($i = 0, $ii = count($allOps); $i < $ii; $i++) {
						$oOption = $allOps[$i];
						$checkedNum = (int) $oPosted->limit->num;
						if ($oOption->c < $checkedNum) {
							break;
						}
						$qualifiedOps[] = $oOption;
					}
				}
			}
			foreach ($qualifiedOps as $oQualifiedOp) {
				$oNewRecData = new \stdClass;
				$oNewRecData->{$oPosted->targetSchema} = $oQualifiedOp->l;
				/* 模拟用户 */
				$oVotingOpDs = null;
				foreach ($oVotingSchema->ops as $oOption) {
					if ($oOption->v === $oQualifiedOp->v && !empty($oOption->ds->user)) {
						$oVotingOpDs = $oOption->ds;
						break;
					}
				}
				if (isset($oVotingOpDs)) {
					$oMockUser = $modelUsr->byId($oTargetApp, $oVotingOpDs->user, ['fields' => 'id,userid,group_id,nickname']);
					if (false === $oMockUser) {
						$oMockUser = $modelUsr->detail($oTargetApp, (object) ['uid' => $oVotingOpDs->user], $oNewRecData);
					} else {
						$oMockUser->uid = $oMockUser->userid;
					}
				} else {
					$oMockUser = null;
				}
				/* 在目标活动中创建新记录 */
				$newek = $modelRec->enroll($oTargetApp, $oMockUser);
				$modelRec->setData($oMockUser, $oTargetApp, $newek, $oNewRecData, '', true);
				$newRecordNum++;
			}
		}

		return new \ResponseData($newRecordNum);
	}
	/**
	 * 投票题目和结果导出到其他活动作为记录
	 */
	public function transferSchemaAndVotes_action($app, $targetApp, $round = '') {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$oPosted = $this->getPostJson();
		if (empty($oPosted->questionSchema)) {
			return new \ParameterError('目标活动中没有指定作为问题的题目');
		}
		if (empty($oPosted->answerSchema)) {
			return new \ParameterError('目标活动中没有指定作为答案的题目');
		}

		$modelEnl = $this->model('matter\enroll');
		$modelRec = $this->model('matter\enroll\record');
		$modelData = $this->model('matter\enroll\data');
		$modelUsr = $this->model('matter\enroll\user');

		$oApp = $modelEnl->byId($app, ['fields' => 'siteid,state,mission_id,sync_mission_round,round_cron,data_schemas', 'appRid' => $round]);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		/* 指定的投票题目 */
		$aVotingSchemas = [];
		foreach ($oApp->dynaDataSchemas as $oSchema) {
			if (in_array($oSchema->id, $oPosted->votingSchemas)) {
				if (in_array($oSchema->type, ['single', 'multiple'])) {
					$aVotingSchemas[] = $oSchema;
				}
			}
		}
		if (empty($aVotingSchemas)) {
			return new \ParameterError('没有指定有效的题目');
		}

		$oTargetApp = $modelEnl->byId($targetApp, ['fields' => '*']);
		if (false === $oTargetApp || $oTargetApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		/* 匹配的轮次 */
		$oAssignedRnd = $oApp->appRound;
		if ($oApp->mission_id === $oTargetApp->mission_id) {
			$modelRnd = $this->model('matter\enroll\round');
			if ($oAssignedRnd) {
				$oTargetAppRnd = $modelRnd->byMissionRid($oTargetApp, $oAssignedRnd->mission_rid, ['fields' => 'rid,mission_rid']);
			}
		} else {
			$oTargetAppRnd = $oTargetApp->appRound;
		}
		/* 目标活动的投票结果 */
		$aVotingData = $modelRec->getStat($oApp, $oAssignedRnd ? $oAssignedRnd->rid : '', 'N');
		$newRecordNum = 0;
		/* 根据投票结果创建记录，每道题生成一条记录 */
		foreach ($aVotingSchemas as $oVotingSchema) {
			if (empty($aVotingData[$oVotingSchema->id]->ops)) {
				continue;
			}
			$allOps = $aVotingData[$oVotingSchema->id]->ops;
			usort($allOps, function ($a, $b) {
				return $a->c < $b->c;
			});
			$qualifiedOps = []; // 满足条件的选项
			if (!empty($oPosted->limit->scope) && !empty($oPosted->limit->num) && (int) $oPosted->limit->num) {
				if ($oPosted->limit->scope === 'top') {
					$limitNum = (int) $oPosted->limit->num;
					if ($limitNum > count($allOps)) {
						$limitNum = count($allOps);
					}
					for ($i = 0; $i < $limitNum; $i++) {
						$qualifiedOps[] = $allOps[$i];
					}
				} else if ($oPosted->limit->scope === 'checked') {
					for ($i = 0, $ii = count($allOps); $i < $ii; $i++) {
						$oOption = $allOps[$i];
						$checkedNum = (int) $oPosted->limit->num;
						if ($oOption->c < $checkedNum) {
							break;
						}
						$qualifiedOps[] = $oOption;
					}
				}
			}
			/* 生成记录 */
			if (isset($oVotingSchema->referRecord->ds->user)) {
				$oMockRecUser = $modelUsr->detail($oTargetApp, (object) ['uid' => $oVotingSchema->referRecord->ds->user]);
			} else {
				$oMockRecUser = new \stdClass;
			}
			$newek = $modelRec->enroll($oTargetApp, $oMockRecUser);

			$oNewRecData = new \stdClass; // 问题+答案的记录数据
			/* 写入问题 */
			$oNewRecData->{$oPosted->questionSchema} = $oVotingSchema->title;

			/* 写入答案 */
			$current = time();
			$oRecData = new \stdClass;
			$oRecData->aid = $oTargetApp->id;
			$oRecData->rid = empty($oTargetAppRnd->rid) ? '' : $oTargetAppRnd->rid;
			$oRecData->enroll_key = $newek;
			$oRecData->submit_at = $current;
			$oRecData->userid = isset($oMockRecUser->uid) ? $oMockRecUser->uid : '';
			$oRecData->nickname = isset($oMockRecUser->nickname) ? $modelData->escape($oMockRecUser->nickname) : '';
			$oRecData->group_id = isset($oMockRecUser->group_id) ? $oMockRecUser->group_id : '';
			$oRecData->schema_id = $oPosted->answerSchema;
			$oRecData->multitext_seq = 0;
			$oRecData->value = [];

			foreach ($qualifiedOps as $oQualifiedOp) {
				/* 模拟用户 */
				$oVotingOpDs = null;
				foreach ($oVotingSchema->ops as $oOption) {
					if ($oOption->v === $oQualifiedOp->v && !empty($oOption->referRecord->ds->user)) {
						$oVotingOpDs = $oOption->referRecord->ds;
						break;
					}
				}
				if (isset($oVotingOpDs)) {
					$oMockAnswerUser = $modelUsr->byId($oTargetApp, $oVotingOpDs->user, ['fields' => 'id,userid,group_id,nickname']);
					if (false === $oMockAnswerUser) {
						$oMockAnswerUser = $modelUsr->detail($oTargetApp, (object) ['uid' => $oVotingOpDs->user], $oNewRecData);
					} else {
						$oMockAnswerUser->uid = $oMockAnswerUser->userid;
					}
				} else {
					$oMockAnswerUser = null;
				}

				$oNewItem = new \stdClass;
				$oNewItem->aid = $oRecData->aid;
				$oNewItem->rid = $oRecData->rid;
				$oNewItem->enroll_key = $oRecData->enroll_key;
				$oNewItem->submit_at = $current;
				$oNewItem->userid = isset($oMockAnswerUser->uid) ? $oMockAnswerUser->uid : '';
				$oNewItem->nickname = isset($oMockAnswerUser->nickname) ? $modelData->escape($oMockAnswerUser->nickname) : '';
				$oNewItem->group_id = isset($oMockAnswerUser->group_id) ? $oMockAnswerUser->group_id : '';
				$oNewItem->schema_id = $oPosted->answerSchema;
				$oNewItem->value = $this->escape($oQualifiedOp->l);
				$oNewItem->multitext_seq = count($oRecData->value) + 1;
				$oNewItem->id = $modelData->insert('xxt_enroll_record_data', $oNewItem, true);

				$oRecData->value[] = (object) ['id' => $oNewItem->id, 'value' => $oNewItem->value];
			}
			/* 记录的数据 */
			$oNewRecData->{$oPosted->answerSchema} = $oRecData->value;
			$modelRec->setData($oMockRecUser, $oTargetApp, $newek, $oNewRecData, '', true);
			$newRecordNum++;
			/* 答案的根数据 */
			$oRecData->value = $modelData->escape($modelData->toJson($oRecData->value));
			$oRecData->id = $modelData->insert('xxt_enroll_record_data', $oRecData, true);
		}

		return new \ResponseData($newRecordNum);
	}
	/**
	 * 投票题目和结果导出到其他活动作为记录
	 */
	public function transferGroupAndMarks_action($app, $targetApp, $round = '') {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$oPosted = $this->getPostJson();
		if (empty($oPosted->questionSchema)) {
			return new \ParameterError('目标活动中没有指定作为问题的题目');
		}
		if (empty($oPosted->answerSchema)) {
			return new \ParameterError('目标活动中没有指定作为答案的题目');
		}

		$modelEnl = $this->model('matter\enroll');
		$modelRec = $this->model('matter\enroll\record');
		$modelData = $this->model('matter\enroll\data');
		$modelUsr = $this->model('matter\enroll\user');

		$oApp = $modelEnl->byId($app, ['fields' => 'siteid,state,mission_id,sync_mission_round,round_cron,data_schemas', 'appRid' => $round]);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		/* 指定的投票题目 */
		$aScoreSchemas = [];
		foreach ($oApp->dynaDataSchemas as $oSchema) {
			if (in_array($oSchema->id, $oPosted->scoreSchemas)) {
				if ($oSchema->type === 'score' && isset($oSchema->parent->id)) {
					$aScoreSchemas[$oSchema->parent->id][] = $oSchema;
				}
			}
		}
		if (empty($aScoreSchemas)) {
			return new \ParameterError('没有指定有效的打分题');
		}
		$aGroupSchemas = [];
		foreach ($oApp->dynaDataSchemas as $oSchema) {
			if ($oSchema->type === 'html' && isset($aScoreSchemas[$oSchema->id])) {
				$aGroupSchemas[$oSchema->id] = $oSchema;
			}
		}
		if (empty($aGroupSchemas)) {
			return new \ParameterError('没有指定有效的分组题');
		}

		$oTargetApp = $modelEnl->byId($targetApp, ['fields' => '*']);
		if (false === $oTargetApp || $oTargetApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		/* 匹配的轮次 */
		$oAssignedRnd = $oApp->appRound;
		if ($oApp->mission_id === $oTargetApp->mission_id) {
			$modelRnd = $this->model('matter\enroll\round');
			if ($oAssignedRnd) {
				$oTargetAppRnd = $modelRnd->byMissionRid($oTargetApp, $oAssignedRnd->mission_rid, ['fields' => 'rid,mission_rid']);
			}
		} else {
			$oTargetAppRnd = $oTargetApp->appRound;
		}

		/* 目标活动所有题目的打分结果 */
		$oResult = $modelRec->score4Schema($oTargetApp, isset($oTargetAppRnd->rid) ? $oTargetAppRnd->rid : '');
		unset($oResult->sum);
		$aResult = (array) $oResult;

		$newRecordNum = 0;
		foreach ($aScoreSchemas as $groupSchemaId => $oGroupScoreSchemas) {
			if (empty($aGroupSchemas[$groupSchemaId])) {
				continue;
			}
			$oGroupSchema = $aGroupSchemas[$groupSchemaId];
			/* 生成记录 */
			$oMockRecUser = new \stdClass;
			$newek = $modelRec->enroll($oTargetApp, $oMockRecUser);

			$oNewRecData = new \stdClass; // 问题+答案的记录数据
			/* 写入问题 */
			$oNewRecData->{$oPosted->questionSchema} = $oGroupSchema->title;

			/* 写入答案 */
			$current = time();
			$oRecData = new \stdClass;
			$oRecData->aid = $oTargetApp->id;
			$oRecData->rid = empty($oTargetAppRnd->rid) ? '' : $oTargetAppRnd->rid;
			$oRecData->enroll_key = $newek;
			$oRecData->submit_at = $current;
			$oRecData->userid = isset($oMockRecUser->uid) ? $oMockRecUser->uid : '';
			$oRecData->nickname = isset($oMockRecUser->nickname) ? $modelData->escape($oMockRecUser->nickname) : '';
			$oRecData->group_id = isset($oMockRecUser->group_id) ? $oMockRecUser->group_id : '';
			$oRecData->schema_id = $oPosted->answerSchema;
			$oRecData->multitext_seq = 0;
			$oRecData->value = [];

			foreach ($oGroupScoreSchemas as $oScoreSchema) {
				/* 模拟用户 */
				$oMockAnswerUser = null;

				$oNewItem = new \stdClass;
				$oNewItem->aid = $oRecData->aid;
				$oNewItem->rid = $oRecData->rid;
				$oNewItem->enroll_key = $oRecData->enroll_key;
				$oNewItem->submit_at = $current;
				$oNewItem->userid = isset($oMockAnswerUser->uid) ? $oMockAnswerUser->uid : '';
				$oNewItem->nickname = isset($oMockAnswerUser->nickname) ? $modelData->escape($oMockAnswerUser->nickname) : '';
				$oNewItem->group_id = isset($oMockAnswerUser->group_id) ? $oMockAnswerUser->group_id : '';
				$oNewItem->schema_id = $oPosted->answerSchema;
				$oNewItem->value = $this->escape($oScoreSchema->title);
				$oNewItem->multitext_seq = count($oRecData->value) + 1;
				$oNewItem->id = $modelData->insert('xxt_enroll_record_data', $oNewItem, true);

				$oRecData->value[] = (object) ['id' => $oNewItem->id, 'value' => $oNewItem->value];
			}
			/* 记录的数据 */
			$oNewRecData->{$oPosted->answerSchema} = $oRecData->value;
			$modelRec->setData($oMockRecUser, $oTargetApp, $newek, $oNewRecData, '', true);
			/* 答案的根数据 */
			$oRecData->value = $modelData->escape($modelData->toJson($oRecData->value));
			$oRecData->id = $modelData->insert('xxt_enroll_record_data', $oRecData, true);

			$newRecordNum++;
		}

		return new \ResponseData($newRecordNum);
	}
	/**
	 * 用一个活动中的数据填充指定活动的记录
	 *
	 * 仅支持用单行填写题或单选题进行匹配
	 *
	 * 除更新题目外，支持更新userid
	 *
	 */
	public function fillByOther_action($app, $targetApp, $preview = 'Y') {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}
		list($targetType, $targetId) = explode(',', $targetApp);
		if (empty($targetType) || empty($targetId)) {
			return new \ParameterError('目标活动参数不完整');
		}
		if (!in_array($targetType, ['mschema'])) {
			return new \ParameterError('指定了不支持的目标活动类型');
		}
		$oPosted = $this->getPostJson();
		if (empty($oPosted->intersectedSchemas)) {
			return new \ParameterError('没有指定题目匹配规则');
		}
		if (empty($oPosted->filledSchemas)) {
			return new \ParameterError('没有指定填充题目规则');
		}

		$modelEnl = $this->model('matter\enroll');
		$modelRec = $this->model('matter\enroll\record');

		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		/* 允许填写活动定义的题目和部分字段 */
		$filledAppAttrs = [];
		$filledAppSchemas = [];
		foreach ($oPosted->filledSchemas as $aMapping) {
			if (in_array($aMapping[0], ['userid'])) {
				$filledAppAttrs[] = $aMapping[0];
			} else {
				$bFound = false;
				foreach ($oApp->dataSchemas as $oSchema) {
					if ($aMapping[0] === $oSchema->id) {
						$bFound = true;
						$filledAppSchemas[] = $aMapping[0];
						break;
					}
				}
				if (false === $bFound) {
					return new \ParameterError('指定的填充题目不存在');
				}
			}
		}

		switch ($targetType) {
		case 'mschema':
			$oTargetApp = $this->model('matter\memberschema')->byId($targetId);
			if (false === $oTargetApp) {
				return new \ObjectNotFoundError();
			}
			$modelMember = $this->model('site\user\member');
			$fnMatchHandler = function ($oData) use ($oTargetApp, $modelMember) {
				$members = $modelMember->byMschema($oTargetApp->id, ['filter' => (object) ['attrs' => $oData]]);
				foreach ($members as $oMember) {
					if (!empty($oMember->extattr) && is_string($oMember->extattr)) {
						$oMember->extattr = json_decode($oMember->extattr);
					}
				}
				return $members;
			};
			$filledSchemas = $oPosted->filledSchemas;
			$fnFillHandler = function ($oMember) use ($filledSchemas) {
				$oFilled = new \stdClass;
				foreach ($filledSchemas as $aMapping) {
					if (!empty($oMember->{$aMapping[1]})) {
						$oFilled->{$aMapping[0]} = $oMember->{$aMapping[1]};
					} else if (!empty($oMember->extattr->{$aMapping[1]})) {
						$oFilled->{$aMapping[0]} = $oMember->extattr->{$aMapping[1]};
					}
				}
				return $oFilled;
			};
			break;
		}

		/* 设置记录匹配的交集 */
		$intersectedSchemas = $oPosted->intersectedSchemas;
		$fnRecordIntersect = function ($oRecord) use ($intersectedSchemas) {
			$oIntersection = new \stdClass;
			if (isset($oRecord->data)) {
				foreach ($intersectedSchemas as $aMapping) {
					if (!empty($oRecord->data->{$aMapping[0]})) {
						$oIntersection->{$aMapping[1]} = $oRecord->data->{$aMapping[0]};
					}
				}
			}
			return $oIntersection;
		};
		/* 用填充数据更新记录 */
		$fnUpdateRecord = function ($oRecord, $oFilled) use ($modelRec, $oApp, $filledAppAttrs, $filledAppSchemas) {
			/* 更新记录属性 */
			if (count($filledAppAttrs)) {
				$aUpdatedAttrs = [];
				foreach ($filledAppAttrs as $attr) {
					if (isset($oFilled->{$attr})) {
						$aUpdatedAttrs[$attr] = $oFilled->{$attr};
					}
				}
				if (count($aUpdatedAttrs)) {
					$modelRec->update('xxt_enroll_record', $aUpdatedAttrs, ['enroll_key' => $oRecord->enroll_key]);
					$modelRec->update('xxt_enroll_record_data', $aUpdatedAttrs, ['enroll_key' => $oRecord->enroll_key]);
				}
			}
			/* 更新题目 */
			if (count($filledAppSchemas)) {
				$bModified = false;
				$oUpdatedData = $oRecord->data;
				foreach ($filledAppSchemas as $schemaId) {
					if (isset($oFilled->{$schemaId})) {
						$oUpdatedData->{$schemaId} = $oFilled->{$schemaId};
						$bModified = true;
					}
				}
				if ($bModified) {
					$modelRec->setData(null, $oApp, $oRecord->enroll_key, $oUpdatedData);
				}
			}

			return true;
		};

		$oResult = new \stdClass;
		$oResult->total = 0;
		$oResult->filledCount = 0; // 完成了填充的记录数
		$oResult->matchedCount = 0; // 能够匹配的数据

		/* 指定活动的所有记录 */
		$oSearchResult = $modelRec->byApp($oApp);
		if (!empty($oSearchResult->records)) {
			$oResult->total = count($oSearchResult->records);
			foreach ($oSearchResult->records as $oRecord) {
				$oIntersection = $fnRecordIntersect($oRecord);
				$targetRecords = $fnMatchHandler($oIntersection);
				/* 必须唯一匹配，才能进行填充 */
				if (count($targetRecords) !== 1) {
					continue;
				}
				$oResult->matchedCount++;
				/* 获得要填充的数据 */
				$oFilled = $fnFillHandler($targetRecords[0]);
				if (!empty($oFilled)) {
					if ('Y' !== $preview) {
						/* 更新数据 */
						if ($fnUpdateRecord($oRecord, $oFilled)) {
							$oResult->filledCount++;
						}
					}
				}
			}
		}

		return new \ResponseData($oResult);
	}
	/**
	 * 更新登记记录
	 *
	 * @param string $app
	 * @param $ek record's key
	 */
	public function update_action($site, $app, $ek) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oPosted = $this->getPostJson();
		$modelEnl = $this->model('matter\enroll');
		$modelRec = $this->model('matter\enroll\record')->setOnlyWriteDbConn(true);

		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$oBeforeRecord = $modelRec->byId($ek, ['verbose' => 'N']);
		if (false === $oBeforeRecord || $oBeforeRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}

		/* 更新记录数据 */
		$oUpdated = new \stdClass;
		$oUpdated->enroll_at = time();
		if (isset($oPosted->comment)) {
			$oUpdated->comment = $modelEnl->escape($oPosted->comment);
		}
		if (isset($oPosted->agreed) && $oPosted->agreed !== $oBeforeRecord->agreed) {
			$oUpdated->agreed = in_array($oPosted->agreed, ['Y', 'N', 'A']) ? $oPosted->agreed : '';
			$oAgreedLog = $oBeforeRecord->agreed_log;
			if (isset($oAgreedLog->{$oUser->id})) {
				$oLog = $oAgreedLog->{$oUser->id};
				$oLog->time = time();
				$oLog->value = $oUpdated->agreed;
			} else {
				$oAgreedLog->{$oUser->id} = (object) ['time' => time(), 'value' => $oUpdated->agreed];
			}
			$oUpdated->agreed_log = json_encode($oAgreedLog);
			/* 如果活动属于项目，更新项目内的推荐内容 */
			if (!empty($oApp->mission_id)) {
				$modelMisMat = $this->model('matter\mission\matter');
				$modelMisMat->agreed($oApp, 'R', $oBeforeRecord, $oUpdated->agreed);
			}
			/* 处理了用户汇总数据，积分数据 */
			$this->model('matter\enroll\event')->agreeRecord($oApp, $oBeforeRecord, $oUser, $oUpdated->agreed);
		}
		if (isset($oPosted->tags)) {
			$oUpdated->tags = $modelEnl->escape($oPosted->tags);
			$modelEnl->updateTags($oApp->id, $oUpdated->tags);
		}
		if (isset($oPosted->verified)) {
			$oUpdated->verified = $modelEnl->escape($oPosted->verified);
		}
		if (isset($oPosted->rid)) {
			$userOldRid = $oBeforeRecord->rid;
			$userNewRid = $oPosted->rid;
			/* 同步enroll_user中的轮次 */
			if ($userOldRid !== $userNewRid) {
				$modelUser = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

				/* 获取enroll_user中用户现在的轮次,如果有积分则不能移动 */
				$resOld = $modelUser->byId($oApp, $oBeforeRecord->userid, ['rid' => $userOldRid]);
				if ($resOld->user_total_coin > 0) {
					return new \ResponseError('用户在当前轮次上以获得积分，不能更换轮次！！');
				}
				/* 查询此用户的记录是否被点赞或者被留言，如果有就不能更改 */
				$qd = [
					'count(*)',
					'xxt_enroll_record_data',
					"enroll_key = '$ek' and state = 1 and (like_num > 0 or remark_num > 0)",
				];
				$UsrDataSum = $modelRec->query_val_ss($qd);
				if ($UsrDataSum > 0) {
					return new \ResponseError('此数据在当前轮次上被点赞或被留言，不能更换轮次！！');
				}

				/* 在新的轮次中用户是否以有记录 */
				$resNew = $modelUser->byId($oApp, $oBeforeRecord->userid, ['rid' => $userNewRid]);
				if ($resNew === false) {
					if ($resOld->enroll_num > 1) {
						$modelRec->update("update xxt_enroll_user set enroll_num = enroll_num - 1 where id = $resOld->id");
						//插入新的数据
						$inData = ['last_enroll_at' => time(), 'enroll_num' => 1];
						$inData['rid'] = $userNewRid;
						$oUser = new \stdClass;
						$oUser->uid = $resOld->userid;
						$oUser->nickname = $resOld->nickname;
						$oUser->group_id = empty($resOld->group_id) ? '' : $resOld->group_id;
						$modelUser->add($oApp, $oUser, $inData);
					} else {
						$modelRec->update('xxt_enroll_user',
							['rid' => $userNewRid],
							['id' => $resOld->id]
						);
					}
				} else {
					if ($resOld->enroll_num > 1) {
						$modelRec->update("update xxt_enroll_user set enroll_num = enroll_num - 1 where id = $resOld->id");
					} else {
						$modelRec->delete('xxt_enroll_user', ['id' => $resOld->id]);
					}

					$modelRec->update("update xxt_enroll_user set enroll_num = enroll_num + 1 where id = $resNew->id");
				}

				$oUpdated->rid = $modelEnl->escape($oPosted->rid);
			}
		}
		if (isset($oPosted->supplement)) {
			$oUpdated->supplement = $modelEnl->toJson($oPosted->supplement);
		}
		$modelEnl->update('xxt_enroll_record', $oUpdated, ['enroll_key' => $ek]);

		/* 记录登记数据 */
		if (isset($oPosted->data)) {
			$score = isset($oPosted->quizScore) ? $oPosted->quizScore : null;
			$userSite = $this->model('site\fe\way')->who($site);
			$modelRec->setData($userSite, $oApp, $ek, $oPosted->data, '', false, $score);
		} else if (isset($oPosted->quizScore)) {
			/* 只修改登记项的分值 */
			$oAfterScore = new \stdClass;
			$oAfterScore->sum = 0;
			$oBeforeScore = $modelRec->query_val_ss(['score', 'xxt_enroll_record', ['aid' => $app, 'enroll_key' => $ek, 'state' => 1]]);
			$oBeforeScore = empty($oBeforeScore) ? new stdClass : json_decode($oBeforeScore);
			foreach ($oApp->dataSchemas as $schema) {
				if (empty($schema->requireScore) || $schema->requireScore !== 'Y') {
					continue;
				}
				//主观题评分
				if (in_array($schema->type, ['single', 'multiple'])) {
					$oAfterScore->{$schema->id} = isset($oBeforeScore->{$schema->id}) ? $oBeforeScore->{$schema->id} : 0;
				} else {
					if (isset($oPosted->quizScore->{$schema->id})) {
						$modelEnl->update('xxt_enroll_record_data', ['score' => $oPosted->quizScore->{$schema->id}], ['enroll_key' => $ek, 'schema_id' => $schema->id, 'state' => 1]);
						$oAfterScore->{$schema->id} = $oPosted->quizScore->{$schema->id};
					} else {
						$oAfterScore->{$schema->id} = 0;
					}
				}
				$oAfterScore->sum += (int) $oAfterScore->{$schema->id};
			}
			$newScore = $modelRec->toJson($oAfterScore);
			//更新record表
			$modelRec->update('xxt_enroll_record', ['score' => $newScore], ['aid' => $app, 'enroll_key' => $ek, 'state' => 1]);
		}
		//数值型填空题
		if (isset($oPosted->score)) {
			$dataSchemas = $oApp->dataSchemas;
			$modelUsr = $this->model('matter\enroll\user');
			$modelUsr->setOnlyWriteDbConn(true);
			$d['sum'] = 0;
			foreach ($dataSchemas as &$schema) {
				if (isset($oPosted->score->{$schema->id})) {
					$d[$schema->id] = $oPosted->score->{$schema->id};
					$modelUsr->update('xxt_enroll_record_data', ['score' => $oPosted->score->{$schema->id}], ['enroll_key' => $ek, 'schema_id' => $schema->id, 'state' => 1]);
					$d['sum'] += $d[$schema->id];
				}
			}
			$newScore = $modelRec->toJson($d);
			//更新record表
			$modelRec->update('xxt_enroll_record', ['score' => $newScore], ['aid' => $app, 'enroll_key' => $ek, 'state' => 1]);
			//更新enroll_user表
			$result = $modelRec->byId($ek);
			if (isset($result->score->sum)) {
				$upData['score'] = $result->score->sum;
			}
			$modelUsr->update(
				'xxt_enroll_user',
				$upData,
				['siteid' => $site, 'aid' => $result->aid, 'rid' => $result->rid, 'userid' => $result->userid]
			);
			/* 更新用户获得的分数 */
			$users = $modelUsr->query_objs_ss([
				'id,score',
				'xxt_enroll_user',
				"siteid='$site' and aid='$result->aid' and userid='$result->userid' and rid !='ALL'",
			]);
			$total = 0;
			foreach ($users as $v) {
				if (!empty($v->score)) {
					$total += (float) $v->score;
				}
			}
			$upDataALL['score'] = $total;
			$modelUsr->update(
				'xxt_enroll_user',
				$upDataALL,
				['siteid' => $site, 'aid' => $result->aid, 'rid' => 'ALL', 'userid' => $result->userid]
			);
		}

		/* 更新登记项数据的轮次 */
		if (isset($oPosted->rid)) {
			$modelEnl->update('xxt_enroll_record_data', ['rid' => $modelEnl->escape($oPosted->rid)], ['enroll_key' => $ek, 'state' => 1]);
		}
		if (isset($oUpdated->verified) && $oUpdated->verified === 'Y') {
			$this->_whenVerifyRecord($oApp, $ek);
		}

		/* 返回完整的记录 */
		$oNewRecord = $modelRec->byId($ek, ['verbose' => 'Y']);

		/* 记录操作日志 */
		$oOperation = new \stdClass;
		$oOperation->enroll_key = $ek;
		isset($oPosted->data) && $oOperation->data = $oPosted->data;
		isset($oPosted->quizScore) && $oOperation->quizScore = $oPosted->quizScore;
		isset($oPosted->score) && $oOperation->score = $oPosted->score;
		isset($oPosted->tags) && $oOperation->tags = $oPosted->tags;
		isset($oPosted->comment) && $oOperation->comment = $oPosted->comment;
		$oOperation->rid = $oNewRecord->rid;
		isset($oNewRecord->round) && $oOperation->round = $oNewRecord->round;
		$this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'updateData', $oOperation);

		return new \ResponseData($oNewRecord);
	}
	/**
	 * 根据reocrd_data中的数据，修复record中的data字段
	 */
	public function repair_action($ek) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelRec = $this->model('matter\enroll\record');
		$oRecord = $modelRec->byId($ek);
		if (false === $oRecord) {
			return new \ParameterError();
		}

		$q = [
			'schema_id,value',
			'xxt_enroll_record_data',
			['enroll_key' => $ek, 'state' => 1],
		];
		$schemaValues = $modelRec->query_objs_ss($q);

		$oRecordData = new \stdClass;
		foreach ($schemaValues as $schemaValue) {
			if (strlen($schemaValue->value)) {
				if ($jsonVal = json_decode($schemaValue->value)) {
					$oRecordData->{$schemaValue->schema_id} = $jsonVal;
				} else {
					$oRecordData->{$schemaValue->schema_id} = $schemaValue->value;
				}
			}
		}

		$sRecordData = $modelRec->escape($modelRec->toJson($oRecordData));

		$rst = $modelRec->update('xxt_enroll_record', ['data' => $sRecordData], ['enroll_key' => $ek]);

		return new \ResponseData($rst);
	}
	/**
	 * 删除一条登记信息
	 */
	public function remove_action($app, $key) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$modelEnlRec = $this->model('matter\enroll\record');
		$oRecord = $modelEnlRec->byId($key, ['fields' => 'userid,state,enroll_key,data,rid']);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}
		// 如果已经获得积分不允许删除
		if (!empty($oRecord->userid)) {
			$modelEnlUsr = $this->model('matter\enroll\user');
			$oEnlUsrRnd = $modelEnlUsr->byId($oApp, $oRecord->userid, ['fields' => 'user_total_coin', 'rid' => $oRecord->rid]);
			if ($oEnlUsrRnd && $oEnlUsrRnd->user_total_coin > 0) {
				return new \ResponseError('提交的记录已经获得活动积分，不能删除');
			}
		}
		// 删除数据
		$rst = $modelEnlRec->remove($oApp, $oRecord);

		// 记录操作日志
		unset($oRecord->userid);
		unset($oRecord->state);
		$this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'removeData', $oRecord);

		return new \ResponseData($rst);
	}
	/**
	 * 恢复一条登记信息
	 */
	public function restore_action($app, $key) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$modelEnlRec = $this->model('matter\enroll\record');
		$oRecord = $modelEnlRec->byId($key, ['fields' => 'userid,enroll_key,data,rid']);
		if (false === $oRecord) {
			return new ObjectNotFoundError();
		}

		$rst = $modelEnlRec->restore($oApp, $oRecord);

		// 记录操作日志
		$this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'restoreData', $oRecord);

		return new \ResponseData($rst);
	}
	/**
	 * 清空登记信息
	 */
	public function empty_action($app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$app = $this->escape($app);
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$modelRec = $this->model('matter\enroll\record');
		/* 清除填写记录 */
		$rst = $modelRec->clean($oApp);

		// 记录操作日志
		$this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'empty');

		return new \ResponseData($rst);
	}
	/**
	 * 所有记录通过审核
	 */
	public function verifyAll_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$app = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);

		$rst = $this->model()->update(
			'xxt_enroll_record',
			['verified' => 'Y'],
			"aid='{$app->id}'"
		);

		// 记录操作日志
		$app->type = 'enroll';
		$this->model('matter\log')->matterOp($site, $oUser, $app, 'verify.all');

		return new \ResponseData($rst);
	}
	/**
	 * 指定记录通过审核
	 */
	public function batchVerify_action($site, $app, $all = 'N') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		$modelRun = $this->model('matter\enroll\round');
		if ($activeRound = $modelRun->getActive($oApp)) {
			$rid = $activeRound->rid;
		}

		if ($all === 'Y') {
			$modelApp->update(
				'xxt_enroll_record',
				['verified' => 'Y'],
				['aid' => $oApp->id]
			);
			// 记录操作日志
			$operationData = new \stdClass;
			if (isset($rid)) {
				$operationData->rid = $rid;
			}
			$this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'verify.all', $operationData);
		} else {

			$posted = $this->getPostJson();
			$eks = $posted->eks;

			$model = $this->model();
			foreach ($eks as $ek) {
				$modelApp->update(
					'xxt_enroll_record',
					['verified' => 'Y'],
					['enroll_key' => $ek]
				);
				// 进行后续处理
				$this->_whenVerifyRecord($oApp, $ek);
			}
			// 记录操作日志
			$operationData = new \stdClass;
			$operationData->data = $eks;
			if (isset($rid)) {
				$operationData->rid = $rid;
			}
			$this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'verify.batch', $operationData);
		}

		return new \ResponseData('ok');
	}
	/**
	 * 验证通过时，如果登记记录有对应的签到记录，且签到记录没有验证通过，那么验证通过
	 */
	private function _whenVerifyRecord(&$app, $enrollKey) {
		if ($app->mission_id) {
			$modelSigninRec = $this->model('matter\signin\record');
			$q = [
				'id',
				'xxt_signin',
				"enroll_app_id='{$app->id}'",
			];
			$signinApps = $modelSigninRec->query_objs_ss($q);
			if (count($signinApps)) {
				$enrollRecord = $this->model('matter\enroll\record')->byId(
					$enrollKey, ['fields' => 'userid,data', 'cascaded' => 'N']
				);
				if (!empty($enrollRecord->data)) {
					foreach ($signinApps as $signinApp) {
						// 更新对应的签到记录，如果签到记录已经审核通过就不更新
						$q = [
							'*',
							'xxt_signin_record',
							"state=1 and verified='N' and aid='$signinApp->id' and verified_enroll_key='{$enrollKey}'",
						];
						$signinRecords = $modelSigninRec->query_objs_ss($q);
						if (count($signinRecords)) {
							foreach ($signinRecords as $signinRecord) {
								if (empty($signinRecord->data)) {
									continue;
								}
								$signinData = json_decode($signinRecord->data);
								if ($signinData === null) {
									$signinData = new \stdClass;
								}
								foreach ($enrollData as $k => $v) {
									$signinData->{$k} = $v;
								}
								// 更新数据
								$modelSigninRec->delete('xxt_signin_record_data', "enroll_key='$signinRecord->enroll_key'");
								foreach ($signinData as $k => $v) {
									$ic = [
										'aid' => $app->id,
										'enroll_key' => $signinRecord->enroll_key,
										'name' => $k,
										'value' => $model->toJson($v),
									];
									$modelSigninRec->insert('xxt_signin_record_data', $ic, false);
								}
								// 验证通过
								$modelSigninRec->update(
									'xxt_signin_record',
									[
										'data' => $modelSigninRec->toJson($signinData),
									],
									"enroll_key='$signinRecord->enroll_key'"
								);
							}
						}
					}
				}
			}
		}

		return false;
	}
	/**
	 * 给记录批量添加标签
	 */
	public function batchTag_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$posted = $this->getPostJson();
		$eks = $posted->eks;
		$tags = $posted->tags;

		/**
		 * 给记录打标签
		 */
		$modelRec = $this->model('matter\enroll\record');
		if (!empty($eks) && !empty($tags)) {
			foreach ($eks as $ek) {
				$record = $modelRec->byId($ek);
				$existent = $record->tags;
				if (empty($existent)) {
					$aNew = $tags;
				} else {
					$aExistent = explode(',', $existent);
					$aNew = array_unique(array_merge($aExistent, $tags));
				}
				$newTags = implode(',', $aNew);
				$modelRec->update('xxt_enroll_record', ['tags' => $newTags], "enroll_key='$ek'");
			}
		}
		/**
		 * 给应用打标签
		 */
		$this->model('matter\enroll')->updateTags($app, $posted->appTags);

		return new \ResponseData('ok');
	}
	/**
	 * 从关联的登记活动中查找匹配的记录
	 */
	public function matchEnroll_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$enrollRecord = $this->getPostJson();
		$result = [];

		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$enrollApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (empty($enrollApp->enroll_app_id) || empty($enrollApp->data_schemas)) {
			return new \ParameterError();
		}

		// 匹配规则
		$isEmpty = true;
		$matchCriteria = new \stdClass;
		$schemas = json_decode($enrollApp->data_schemas);
		foreach ($schemas as $schema) {
			if (isset($schema->requireCheck) && $schema->requireCheck === 'Y') {
				if (isset($schema->fromApp) && $schema->fromApp === $enrollApp->enroll_app_id) {
					if (!empty($enrollRecord->{$schema->id})) {
						$matchCriteria->{$schema->id} = $enrollRecord->{$schema->id};
						$isEmpty = false;
					}
				}
			}
		}

		if (!$isEmpty) {
			// 查找匹配的数据
			$matchApp = $modelApp->byId($enrollApp->enroll_app_id, ['cascaded' => 'N']);
			$modelEnlRec = $this->model('matter\enroll\record');
			$matchRecords = $modelEnlRec->byData($matchApp, $matchCriteria);
			foreach ($matchRecords as $matchRec) {
				$result[] = $matchRec->data;
			}
		}

		return new \ResponseData($result);
	}
	/**
	 * 从关联的分组活动中查找匹配的记录
	 */
	public function matchGroup_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$enrollRecord = $this->getPostJson();
		$result = [];

		// 签到应用
		$modelApp = $this->model('matter\enroll');
		$enrollApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (empty($enrollApp->group_app_id) || empty($enrollApp->data_schemas)) {
			return new \ParameterError();
		}

		// 匹配规则
		$isEmpty = true;
		$matchCriteria = new \stdClass;
		$schemas = json_decode($enrollApp->data_schemas);
		foreach ($schemas as $schema) {
			if (isset($schema->requireCheck) && $schema->requireCheck === 'Y') {
				if (isset($schema->fromApp) && $schema->fromApp === $enrollApp->group_app_id) {
					if (!empty($enrollRecord->{$schema->id})) {
						$matchCriteria->{$schema->id} = $enrollRecord->{$schema->id};
						$isEmpty = false;
					}
				}
			}
		}

		if (!$isEmpty) {
			// 查找匹配的数据
			$groupApp = $this->model('matter\group')->byId($enrollApp->group_app_id, ['cascaded' => 'N']);
			$modelGrpRec = $this->model('matter\group\player');
			$matchedRecords = $modelGrpRec->byData($groupApp, $matchCriteria);
			foreach ($matchedRecords as $matchedRec) {
				if (isset($matchedRec->round_id)) {
					$matchedRec->data->_round_id = $matchedRec->round_id;
				}
				$result[] = $matchedRec->data;
			}
		}

		return new \ResponseData($result);
	}
	/**
	 * 登记数据导出
	 */
	public function export_action($site, $app, $filter = '') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 登记活动
		$oApp = $this->model('matter\enroll')->byId($app, ['fields' => 'siteid,id,state,title,data_schemas,entry_rule,assigned_nickname,scenario,enroll_app_id,group_app_id,multi_rounds,mission_id,sync_mission_round,round_cron', 'cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			die('指定的对象不存在或者已经不可用');
		}
		$schemas = $oApp->dataSchemas;

		// 关联的登记活动
		if (!empty($oApp->enroll_app_id)) {
			$matchApp = $this->model('matter\enroll')->byId($oApp->enroll_app_id, ['fields' => 'id,title,data_schemas', 'cascaded' => 'N']);
			$enrollSchemas = $matchApp->dataSchemas;
			$mapOfAppSchemas = [];
			foreach ($schemas as $schema) {
				$mapOfAppSchemas[] = $schema->id;
			}
			foreach ($enrollSchemas as $schema) {
				if (!in_array($schema->id, $mapOfAppSchemas)) {
					$schemas[] = $schema;
				}
			}
		}
		// 关联的分组活动
		if (!empty($oApp->group_app_id)) {
			$matchApp = $this->model('matter\group')->byId($oApp->group_app_id, ['fields' => 'id,title,data_schemas', 'cascaded' => 'N']);
			$groupSchemas = json_decode($matchApp->data_schemas);
			$mapOfAppSchemas = [];
			foreach ($schemas as $schema) {
				$mapOfAppSchemas[] = $schema->id;
			}
			foreach ($groupSchemas as $schema) {
				if (!in_array($schema->id, $mapOfAppSchemas)) {
					$schemas[] = $schema;
				}
			}
		}

		// 获得所有有效的登记记录
		$modelRec = $this->model('matter\enroll\record');

		// 筛选条件
		$filter = $modelRec->unescape($filter);
		$oCriteria = empty($filter) ? new \stdClass : json_decode($filter);
		$rid = empty($oCriteria->record->rid) ? '' : $oCriteria->record->rid;
		if (!empty($oCriteria->record->group_id)) {
			$gid = $oCriteria->record->group_id;
		} else if (!empty($oCriteria->data->_round_id)) {
			$gid = $oCriteria->data->_round_id;
		} else {
			$gid = '';
		}

		$oResult = $modelRec->byApp($oApp, null, $oCriteria);
		if ($oResult->total === 0) {
			die('导出数据为空');
		}

		if (!empty($oResult->records)) {
			$remarkables = [];
			foreach ($oApp->dataSchemas as $oSchema) {
				if (isset($oSchema->remarkable) && $oSchema->remarkable === 'Y') {
					$remarkables[] = $oSchema->id;
				}
			}
			if (count($remarkables)) {
				foreach ($oResult->records as &$oRec) {
					$modelRem = $this->model('matter\enroll\data');
					$oRecordData = $modelRem->byRecord($oRec->enroll_key, ['schema' => $remarkables]);
					$oRec->verbose = new \stdClass;
					$oRec->verbose->data = $oRecordData;
				}
			}
		}

		// 是否需要分组信息
		$bRequireGroup = empty($oApp->group_app_id) && isset($oApp->entryRule->scope->group) && $oApp->entryRule->scope->group === 'Y' && !empty($oApp->entryRule->group->id);

		$records = $oResult->records;
		require_once TMS_APP_DIR . '/lib/PHPExcel.php';

		// Create new PHPExcel object
		$objPHPExcel = new \PHPExcel();
		// Set properties
		$objPHPExcel->getProperties()->setCreator(APP_TITLE)
			->setLastModifiedBy(APP_TITLE)
			->setTitle($oApp->title)
			->setSubject($oApp->title)
			->setDescription($oApp->title);

		$objActiveSheet = $objPHPExcel->getActiveSheet();
		$columnNum1 = 0; //列号
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '登记时间');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '审核通过');
		if ($oApp->multi_rounds === 'Y') {
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '登记轮次');
		}

		// 转换标题
		$aNumberSum = []; // 数值型题目的合计
		$aScoreSum = []; // 题目的分数合计
		$columnNum4 = $columnNum1; //列号
		$bRequireNickname = true;
		if ((isset($oApp->assignedNickname->valid) && $oApp->assignedNickname->valid !== 'Y') || isset($oApp->assignedNickname->schema->id)) {
			$bRequireNickname = false;
		}
		$bRequireSum = false; // 是否需要计算合计
		$bRequireScore = false; // 是否需要计算总分
		for ($a = 0, $ii = count($schemas); $a < $ii; $a++) {
			$oSchema = $schemas[$a];
			/* 跳过图片,描述说明和文件 */
			if (in_array($oSchema->type, ['html'])) {
				continue;
			}
			if ($oSchema->type === 'shorttext') {
				/* 数值型，需要计算合计 */
				if (isset($oSchema->format) && $oSchema->format === 'number') {
					$aNumberSum[$columnNum4] = $oSchema->id;
					$bRequireSum = true;
				}
			} else if ($oSchema->type === 'score') {
				/* 打分题，需要计算合计 */
				$aNumberSum[$columnNum4] = $oSchema->id;
				$bRequireSum = true;
			}
			$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, $oSchema->title);
			/* 需要计算得分 */
			if ((isset($oSchema->requireScore) && $oSchema->requireScore === 'Y')) {
				$aScoreSum[$columnNum4] = $oSchema->id;
				$bRequireScore = true;
				$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '得分');
			}
			if (isset($remarkables) && in_array($oSchema->id, $remarkables)) {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '留言数');
			}
		}
		if ($bRequireNickname) {
			$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '昵称');
		}
		if ($bRequireGroup) {
			$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '分组');
		}
		$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '备注');
		// $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '标签');
		// 记录分数
		if ($oApp->scenario === 'voting') {
			$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '总分数');
			$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '平均分数');
			$titles[] = '总分数';
			$titles[] = '平均分数';
		}
		if ($bRequireScore) {
			$aScoreSum[$columnNum4] = 'sum';
			$objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '总分');
			$titles[] = '总分';
		}
		// 转换数据
		for ($j = 0, $jj = count($records); $j < $jj; $j++) {
			$oRecord = $records[$j];
			$rowIndex = $j + 2;
			$columnNum2 = 0; //列号
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, date('y-m-j H:i', $oRecord->enroll_at));
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oRecord->verified);
			// 轮次名
			if (isset($oRecord->round)) {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oRecord->round->title);
			}
			// 处理登记项
			$data = $oRecord->data;
			$oRecScore = empty($oRecord->score) ? null : $oRecord->score;
			$supplement = $oRecord->supplement;
			$oVerbose = isset($oRecord->verbose) ? $oRecord->verbose->data : false;
			$i = 0; // 列序号
			for ($i2 = 0, $ii = count($schemas); $i2 < $ii; $i2++) {
				$columnNum3 = $columnNum2; //列号
				$oSchema = $schemas[$i2];
				if (isset($data->{$oSchema->id})) {
					$v = $data->{$oSchema->id};
				} else if ((strpos($oSchema->id, 'member.') === 0) && isset($data->member)) {
					$mbSchemaId = $oSchema->id;
					$mbSchemaIds = explode('.', $mbSchemaId);
					$mbSchemaId = $mbSchemaIds[1];
					if ($mbSchemaId === 'extattr' && count($mbSchemaIds) == 3) {
						$mbSchemaId = $mbSchemaIds[2];
						$v = isset($data->member->extattr->{$mbSchemaId}) ? $data->member->extattr->{$mbSchemaId} : '';
					} else {
						$v = isset($data->member->{$mbSchemaId}) ? $data->member->{$mbSchemaId} : '';
					}
				} else {
					$v = '';
				}

				if (in_array($oSchema->type, ['html'])) {
					continue;
				}
				switch ($oSchema->type) {
				case 'single':
					$cellValue = '';
					foreach ($oSchema->ops as $op) {
						if ($op->v === $v) {
							$cellValue = $op->l;
						}
					}
					if (isset($oSchema->supplement) && $oSchema->supplement === 'Y') {
						$cellValue .= " \n(补充说明：\n" . (isset($supplement) && isset($supplement->{$oSchema->id}) ? $supplement->{$oSchema->id} : '') . ")";
					}
					$cellValue = str_replace(['<br>', '</br>'], ["\n", ""], $cellValue);
					$cellValue = strip_tags($cellValue);
					$cellValue = str_replace(['&nbsp;', '&amp;'], [' ', '&'], $cellValue);
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $cellValue, \PHPExcel_Cell_DataType::TYPE_STRING);
					$objActiveSheet->getStyleByColumnAndRow($i + $columnNum3 - 1, $rowIndex)->getAlignment()->setWrapText(true);
					break;
				case 'multiple':
					$labels = [];
					$v = explode(',', $v);
					foreach ($v as $oneV) {
						foreach ($oSchema->ops as $op) {
							if ($op->v === $oneV) {
								$labels[] = $op->l;
								break;
							}
						}
					}
					$cellValue = implode(',', $labels);
					if (isset($oSchema->supplement) && $oSchema->supplement === 'Y') {
						$cellValue .= " \n(补充说明：\n" . (isset($supplement) && isset($supplement->{$oSchema->id}) ? $supplement->{$oSchema->id} : '') . ")";
					}
					$cellValue = str_replace(['<br>', '</br>'], ["\n", ""], $cellValue);
					$cellValue = strip_tags($cellValue);
					$cellValue = str_replace(['&nbsp;', '&amp;'], [' ', '&'], $cellValue);
					$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum3++, $rowIndex, $cellValue);
					$objActiveSheet->getStyleByColumnAndRow($i + $columnNum3 - 1, $rowIndex)->getAlignment()->setWrapText(true);
					break;
				case 'score':
					$labels = [];
					foreach ($oSchema->ops as $op) {
						if (isset($v->{$op->v})) {
							$labels[] = $op->l . ':' . $v->{$op->v};
						}
					}
					$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum3++, $rowIndex, implode(' / ', $labels));
					break;
				case 'image':
					$v0 = '';
					if (isset($oSchema->supplement) && $oSchema->supplement === 'Y') {
						$v0 .= " \n(补充说明：\n" . (isset($supplement) && isset($supplement->{$oSchema->id}) ? $supplement->{$oSchema->id} : '') . ")";
					}
					$v0 = str_replace(['<br>', '</br>'], ["\n", ""], $v0);
					$v0 = strip_tags($v0);
					$v0 = str_replace(['&nbsp;', '&amp;'], [' ', '&'], $v0);
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v0, \PHPExcel_Cell_DataType::TYPE_STRING);
					$objActiveSheet->getStyleByColumnAndRow($i + $columnNum3 - 1, $rowIndex)->getAlignment()->setWrapText(true);
					break;
				case 'file':
					$v0 = '';
					if (isset($oSchema->supplement) && $oSchema->supplement === 'Y') {
						$v0 .= " \n(补充说明：\n" . (isset($supplement) && isset($supplement->{$oSchema->id}) ? $supplement->{$oSchema->id} : '') . ")";
					}
					$v0 = str_replace(['<br>', '</br>'], ["\n", ""], $v0);
					$v0 = strip_tags($v0);
					$v0 = str_replace(['&nbsp;', '&amp;'], [' ', '&'], $v0);
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v0, \PHPExcel_Cell_DataType::TYPE_STRING);
					$objActiveSheet->getStyleByColumnAndRow($i + $columnNum3 - 1, $rowIndex)->getAlignment()->setWrapText(true);
					break;
				case 'date':
					!empty($v) && $v = date('y-m-j H:i', $v);
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
					break;
				case 'shorttext':
					if (isset($oSchema->format) && $oSchema->format === 'number') {
						$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
					} else {
						$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
					}
					break;
				case 'multitext':
					if (is_array($v)) {
						$values = [];
						foreach ($v as $val) {
							$values[] = strip_tags($val->value);
						}
						$v = implode("\n", $values);
					}
					$v = str_replace(['&nbsp;', '&amp;'], [' ', '&'], $v);
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
					$objActiveSheet->getStyleByColumnAndRow($i + $columnNum3 - 1, $rowIndex)->getAlignment()->setWrapText(true);
					break;
				case 'url':
					$v0 = '';
					!empty($v->title) && $v0 .= '【' . $v->title . '】';
					!empty($v->description) && $v0 .= $v->description;
					!empty($v->url) && $v0 .= $v->url;
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v0, \PHPExcel_Cell_DataType::TYPE_STRING);
					break;
				default:
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i + $columnNum3++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
					break;
				}
				$one = $i + $columnNum3;
				// 分数
				if ((isset($oSchema->requireScore) && $oSchema->requireScore === 'Y')) {
					$cellScore = empty($oRecScore->{$oSchema->id}) ? 0 : $oRecScore->{$oSchema->id};
					$objActiveSheet->setCellValueExplicitByColumnAndRow($i++ + $columnNum3++, $rowIndex, $cellScore, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
				}
				// 留言数
				if (isset($remarkables) && in_array($oSchema->id, $remarkables)) {
					if (isset($oVerbose->{$oSchema->id})) {
						$remark_num = $oVerbose->{$oSchema->id}->remark_num;
					} else {
						$remark_num = 0;
					}
					$two = $i + $columnNum3;
					$col = ($two - $one >= 2) ? ($two - 1) : $two;
					$objActiveSheet->setCellValueExplicitByColumnAndRow($col, $rowIndex, $remark_num, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
					$i++;
					$columnNum3++;
				}
				$i++;
			}
			// 昵称
			if ($bRequireNickname) {
				$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $oRecord->nickname);
			}
			// 分组
			if ($bRequireGroup) {
				$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, isset($oRecord->group->title) ? $oRecord->group->title : '');
			}
			// 备注
			$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $oRecord->comment);
			// 标签
			// $objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $oRecord->tags);
			// 记录投票分数
			if ($oApp->scenario === 'voting') {
				$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, $oRecord->_score);
				$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, sprintf('%.2f', $oRecord->_average));
			}
			// 记录测验分数
			if ($bRequireScore) {
				$objActiveSheet->setCellValueByColumnAndRow($i + $columnNum2++, $rowIndex, isset($oRecScore->sum) ? $oRecScore->sum : '');
			}
		}
		if (!empty($aNumberSum)) {
			// 数值型合计
			$rowIndex = count($records) + 2;
			$oSum4Schema = $modelRec->sum4Schema($oApp, $rid, $gid);
			$objActiveSheet->setCellValueByColumnAndRow(0, $rowIndex, '合计');
			foreach ($aNumberSum as $key => $val) {
				$objActiveSheet->setCellValueByColumnAndRow($key, $rowIndex, $oSum4Schema->$val);
			}
		}
		if (!empty($aScoreSum)) {
			// 分数合计
			$rowIndex = count($records) + 2;
			$oScore4Schema = $modelRec->score4Schema($oApp, $rid, $gid);
			$objActiveSheet->setCellValueByColumnAndRow(0, $rowIndex, '合计');
			foreach ($aScoreSum as $key => $val) {
				$objActiveSheet->setCellValueByColumnAndRow($key, $rowIndex, isset($oScore4Schema->$val) ? $oScore4Schema->$val : '');
			}
		}
		// 输出
		header('Content-Type: application/vnd.ms-excel');
		header('Cache-Control: max-age=0');

		$filename = $oApp->title . '.xlsx';
		$ua = $_SERVER["HTTP_USER_AGENT"];
		//if (preg_match("/MSIE/", $ua) || preg_match("/Trident\/7.0/", $ua)) {
		if (preg_match("/MSIE/", $ua)) {
			$encoded_filename = urlencode($filename);
			$encoded_filename = str_replace("+", "%20", $encoded_filename);
			$encoded_filename = iconv('UTF-8', 'GBK//IGNORE', $encoded_filename);
			header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
		} else if (preg_match("/Firefox/", $ua)) {
			header('Content-Disposition: attachment; filename*="utf8\'\'' . $filename . '"');
		} else {
			header('Content-Disposition: attachment; filename="' . $filename . '"');
		}

		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
		exit;
	}
	/**
	 * 导出登记数据中的图片
	 */
	public function exportImage_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			die('请先登录系统');
		}
		if (defined('SAE_TMP_PATH')) {
			die('部署环境不支持该功能');
		}

		$nameSchema = null;
		$imageSchemas = [];

		// 登记活动
		$enrollApp = $this->model('matter\enroll')->byId($app, ['fields' => 'id,title,data_schemas,scenario,enroll_app_id,group_app_id,sync_mission_round', 'cascaded' => 'N']);
		$schemas = json_decode($enrollApp->data_schemas);

		// 关联的登记活动
		if (!empty($enrollApp->enroll_app_id)) {
			$matchApp = $this->model('matter\enroll')->byId($enrollApp->enroll_app_id, ['fields' => 'id,title,data_schemas', 'cascaded' => 'N']);
			$enrollSchemas = json_decode($matchApp->data_schemas);
			$mapOfAppSchemas = [];
			foreach ($schemas as $schema) {
				$mapOfAppSchemas[] = $schema->id;
			}
			foreach ($enrollSchemas as $schema) {
				if (!in_array($schema->id, $mapOfAppSchemas)) {
					$schemas[] = $schema;
				}
			}
		}
		// 关联的分组活动
		if (!empty($enrollApp->group_app_id)) {
			$matchApp = $this->model('matter\group')->byId($enrollApp->group_app_id, ['fields' => 'id,title,data_schemas', 'cascaded' => 'N']);
			$groupSchemas = json_decode($matchApp->data_schemas);
			$mapOfAppSchemas = [];
			foreach ($schemas as $schema) {
				$mapOfAppSchemas[] = $schema->id;
			}
			foreach ($groupSchemas as $schema) {
				if (!in_array($schema->id, $mapOfAppSchemas)) {
					$schemas[] = $schema;
				}
			}
		}

		foreach ($schemas as $schema) {
			if ($schema->type === 'image') {
				$imageSchemas[] = $schema;
			} else if ($schema->id === 'name' || (in_array($schema->title, array('姓名', '名称')))) {
				$nameSchema = $schema;
			}
		}

		if (count($imageSchemas) === 0) {
			die('活动不包含图片数据');
		}

		// 获得所有有效的登记记录
		$records = $this->model('matter\enroll\record')->byApp($enrollApp);
		if ($records->total === 0) {
			die('record empty');
		}
		$records = $records->records;

		// 转换数据
		$aImages = [];
		for ($j = 0, $jj = count($records); $j < $jj; $j++) {
			$record = $records[$j];
			// 处理登记项
			$data = $record->data;
			for ($i = 0, $ii = count($imageSchemas); $i < $ii; $i++) {
				$schema = $imageSchemas[$i];
				if (!empty($data->{$schema->id})) {
					$aImages[] = ['url' => $data->{$schema->id}, 'schema' => $schema, 'data' => $data];
				}
			}
		}

		// 输出
		$usedRecordName = [];
		// 输出打包文件
		$zipFilename = tempnam('/tmp', $enrollApp->id);
		$zip = new \ZipArchive;
		if ($zip->open($zipFilename, \ZIPARCHIVE::CREATE) === false) {
			die('无法打开压缩文件，或者文件创建失败');
		}
		foreach ($aImages as $image) {
			$imageFilename = TMS_APP_DIR . '/' . $image['url'];
			if (file_exists($imageFilename)) {
				$imageName = basename($imageFilename);
				/**
				 * 图片文件名称替换
				 */
				if (isset($nameSchema)) {
					$data = $image['data'];
					if (!empty($data->{$nameSchema->id})) {
						$recordName = $data->{$nameSchema->id};
						if (isset($usedRecordName[$recordName])) {
							$usedRecordName[$recordName]++;
							$recordName = $recordName . '_' . $usedRecordName[$recordName];
						} else {
							$usedRecordName[$recordName] = 0;
						}
						$imageName = $recordName . '.' . explode('.', $imageName)[1];
					}
				}
				$zip->addFile($imageFilename, $image['schema']->title . '/' . $imageName);
			}
		}
		$zip->close();

		if (!file_exists($zipFilename)) {
			exit("无法找到压缩文件");
		}
		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		header('Content-disposition: attachment; filename=' . $enrollApp->title . '.zip');
		header("Content-Type: application/zip");
		header("Content-Transfer-Encoding: binary");
		header('Content-Length: ' . filesize($zipFilename));
		@readfile($zipFilename);

		exit;
	}
	/**
	 * 从其他的登记活动导入登记数据
	 *
	 * 导入的数据项定义必须兼容，兼容规则如下
	 * 从目标应用中导入和指定应用的数据定义中名称（title）和类型（type）一致的项
	 * 如果是单选题、多选题、打分题选项必须一致
	 * 如果是打分题，分值设置范围必须一致
	 * 项目阶段不支持导入
	 *
	 * @param string $app app'id
	 * @param string $fromApp 目标应用的id
	 * @param string $append 追加记录，否则清空现有记录
	 *
	 */
	public function importByOther_action($site, $app, $fromApp, $append = 'Y') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\enroll');
		$modelRec = $this->model('matter\enroll\record');

		if (false === ($app = $modelApp->byId($app))) {
			return new \ResponseError('指定的活动不存在（1）');
		}
		if (false === ($fromApp = $modelApp->byId($fromApp))) {
			return new \ResponseError('指定的活动不存在（2）');
		}

		$dataSchemas = json_decode($app->data_schemas);
		$fromDataSchemas = json_decode($fromApp->data_schemas);

		/* 获得兼容的登记项 */
		$compatibleSchemas = $modelRec->compatibleSchemas($dataSchemas, $fromDataSchemas);
		if (empty($compatibleSchemas)) {
			return new \ResponseData('没有匹配的数据项');
		}
		/* 获得数据 */
		$records = $modelRec->byApp($fromApp);
		$countOfImport = 0;
		if ($records->total > 0) {
			foreach ($records->records as $record) {
				// 新登记
				$user = new \stdClass;
				$user->uid = $record->userid;
				$user->nickname = $record->nickname;
				$aOptions = [];
				$aOptions['enrollAt'] = $record->enroll_at;
				$aOptions['nickname'] = $record->nickname;
				$ek = $modelRec->enroll($app, $user, $aOptions);
				// 登记数据
				$data = new \stdClass;
				foreach ($compatibleSchemas as $cs) {
					if (empty($record->data->{$cs[0]->id})) {
						continue;
					}
					$val = $record->data->{$cs[0]->id};
					if ($cs[0]->type === 'single') {
						foreach ($cs[0]->ops as $index => $op) {
							if ($op->v === $val) {
								$val = $cs[1]->ops[$index]->v;
								break;
							}
						}
					} else if ($cs[0]->type === 'multiple') {
						$val3 = new \stdClass;
						$val2 = explode(',', $val);
						foreach ($val2 as $v) {
							foreach ($cs[0]->ops as $index => $op) {
								if ($op->v === $v) {
									$val3->{$cs[1]->ops[$index]->v} = true;
									break;
								}
							}
						}
						$val = $val3;
					} else if ($cs[0]->type === 'score') {
						$val2 = new \stdClass;
						foreach ($val as $opv => $score) {
							foreach ($cs[0]->ops as $index => $op) {
								if ($op->v === $opv) {
									$val2->{$cs[1]->ops[$index]->v} = $score;
									break;
								}
							}
						}
						$val = $val2;
					}
					$data->{$cs[1]->id} = $val;
				}
				$modelRec->setData($user, $app, $ek, $data);
				$countOfImport++;
			}
		}

		return new \ResponseData($countOfImport);
	}
	/**
	 * 从指定的数据源同步数据
	 */
	public function syncWithDataSource_action($app, $round = null) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\enroll');
		if (false === ($oApp = $modelApp->byId($app, ['fields' => 'id,data_schemas,scenario,mission_id,sync_mission_round,round_cron', 'cascaded' => 'N']))) {
			return new \ObjectNotFoundError();
		}
		$modelRnd = $this->model('matter\enroll\round');
		if (empty($round)) {
			$oAssignedRnd = $modelRnd->getActive($oApp, ['fields' => 'id,rid,mission_rid']);
		} else {
			$oAssignedRnd = $modelRnd->byId($round, ['fields' => 'id,rid,mission_rid']);
		}
		$modelRec = $this->model('matter\enroll\record');
		if (!empty($oApp->dataSchemas)) {
			foreach ($oApp->dataSchemas as $oSchema) {
				if (!empty($oSchema->ds->app->id) && !empty($oSchema->ds->schema->id)) {
					$oDsApp = $modelApp->byId($oSchema->ds->app->id, ['fields' => 'id,data_schemas', 'cascaded' => 'N']);
					if ($oAssignedRnd) {
						$oDsAssignedRnd = $modelRnd->byMissionRid($oDsApp, $oAssignedRnd->mission_rid, ['fields' => 'rid,mission_rid']);
					}
					if (!empty($oDsApp->dataSchemas)) {
						/* 设置动态选项 */
						if (!isset($modelSch)) {
							$modelSch = $this->model('matter\enroll\schema');
						}
						$modelSch->setDynaOptions($oDsApp, isset($oDsAssignedRnd) ? $oDsAssignedRnd : null);
						foreach ($oDsApp->dataSchemas as $oDsSchema) {
							if ($oSchema->ds->schema->id !== $oDsSchema->id) {
								continue;
							}
							switch ($oDsSchema->type) {
							case 'single':
							case 'multiple':
								if (!empty($oDsSchema->dsOps->app->id) && !empty($oDsSchema->ops)) {
									$aDsOpDataByUser = [];
									foreach ($oDsSchema->ops as $oDsOp) {
										/* 获得数据源的值 */
										if (!empty($oDsOp->ds->user)) {
											if ('single' === $oDsSchema->type) {
												$q = [
													'count(*)',
													'xxt_enroll_record_data',
													"aid='{$oDsApp->id}' and state=1 and schema_id='{$oDsSchema->id}' and value='{$oDsOp->v}'",
												];
											} else {
												$q = [
													'count(*)',
													'xxt_enroll_record_data',
													"aid='{$oDsApp->id}' and state=1 and schema_id='{$oDsSchema->id}' and FIND_IN_SET('{$oDsOp->v}', value)",
												];
											}
											/* 限制数据源的轮次 */
											if (!empty($oDsAssignedRnd->rid)) {
												$q[2] .= " and rid='{$oDsAssignedRnd->rid}'";
											}
											$count = (int) $modelRec->query_val_ss($q);
											if (isset($aDsOpDataByUser[$oDsOp->ds->user])) {
												$aDsOpDataByUser[$oDsOp->ds->user] += $count;
											} else {
												$aDsOpDataByUser[$oDsOp->ds->user] = $count;
											}
										}
										/* 更新获得记录的数值 */
										$q = [
											'id,enroll_key,data,userid,group_id',
											'xxt_enroll_record',
											['aid' => $oApp->id, 'state' => 1],
										];
										/* 限制汇总数据的轮次 */
										if (!empty($oAssignedRnd->rid)) {
											$q[2]['rid'] = $oAssignedRnd;
										}
										$oUserRecords = $modelRec->query_objs_ss($q);
										if (!empty($oUserRecords)) {
											$oRecUser = new \stdClass;
											foreach ($oUserRecords as $oUserRec) {
												$oRecUser->uid = $oUserRec->userid;
												$oRecUser->group_id = $oUserRec->group_id;
												$oRecData = empty($oUserRec->data) ? new \stdClass : json_decode($oUserRec->data);
												if (isset($aDsOpDataByUser[$oUserRec->userid])) {
													$oRecData->{$oSchema->id} = (string) $aDsOpDataByUser[$oUserRec->userid];
												} else {
													unset($oRecData->{$oSchema->id});
												}
												$modelRec->setData($oRecUser, $oApp, $oUserRec->enroll_key, $oRecData);
											}
										}
									}
								}
								break;
							case 'shorttext':
								if (isset($oDsSchema->format) && $oDsSchema->format === 'number') {
									$q = [
										'id,enroll_key,data,userid,group_id',
										'xxt_enroll_record',
										['aid' => $oApp->id, 'state' => 1],
									];
									/* 限制汇总数据的轮次 */
									if (!empty($oAssignedRnd->rid)) {
										$q[2]['rid'] = $oAssignedRnd;
									}
									$oUserRecords = $modelRec->query_objs_ss($q);
									if (!empty($oUserRecords)) {
										$oRecUser = new \stdClass;
										foreach ($oUserRecords as $oUserRec) {
											$oRecUser->uid = $oUserRec->userid;
											$oRecUser->group_id = $oUserRec->group_id;
											$oRecData = empty($oUserRec->data) ? new \stdClass : json_decode($oUserRec->data);
											$q = [
												'sum(value)',
												'xxt_enroll_record_data',
												"aid='{$oDsApp->id}' and state=1 and schema_id='{$oDsSchema->id}' and userid='{$oUserRec->userid}'",
											];
											/* 限制数据源的轮次 */
											if (!empty($oDsAssignedRnd->rid)) {
												$q[2] .= " and rid='{$oDsAssignedRnd->rid}'";
											}
											$sum = $modelRec->query_val_ss($q);
											$oRecData->{$oSchema->id} = $sum;
											$modelRec->setData($oRecUser, $oApp, $oUserRec->enroll_key, $oRecData);
										}
									}
								}
								break;
							}
						}
					}
				}
			}
		}

		return new \ResponseData('ok');
	}
	/**
	 * 从活动所属项目同步用户记录
	 */
	public function syncMissionUser_action($app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['fields' => 'id,siteid,data_schemas,scenario,mission_id,sync_mission_round,round_cron', 'cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		if ('mis_user_score' !== $oApp->scenario) {
			return new \ParameterError('活动类型不正确，无法执行次操作');
		}
		$modelMis = $this->model('matter\mission');
		$oMission = $modelMis->byId($oApp->mission_id);
		if (false === $oMission) {
			return new \ObjectNotFoundError();
		}

		/* 获得项目用户 */
		if (isset($oMission->user_app_id) && isset($oMission->user_app_type)) {
			$oUserSource = new \stdClass;
			$oUserSource->id = $oMission->user_app_id;
			$oUserSource->type = $oMission->user_app_type;
			switch ($oUserSource->type) {
			case 'group':
				$oGrpApp = $this->model('matter\group')->byId($oUserSource->id, ['fields' => 'assigned_nickname', 'cascaded' => 'N']);
				$users = $this->model('matter\group\player')->byApp($oUserSource, (object) ['fields' => 'userid,nickname']);
				$misUsers = isset($users->players) ? $users->players : [];
				break;
			case 'enroll':
				$misUsers = $this->model('matter\enroll\record')->enrolleeByApp($oUserSource, ['fields' => 'distinct userid,nickname', 'rid' => 'all', 'userid' => 'all']);
				break;
			case 'signin':
				$misUsers = $this->model('matter\signin\record')->enrolleeByApp($oUserSource, ['fields' => 'distinct userid,nickname']);
				break;
			case 'mschema':
				$misUsers = $this->model('site\user\member')->byMschema($oUserSource->id, ['fields' => 'userid,name nickname']);
				break;
			}
		}
		if (empty($misUsers)) {
			return new \ParameterError('项目用户数据为空');
		}

		$oPosted = $this->getPostJson();

		$oAssignedRnds = [];
		$modelRnd = $this->model('matter\enroll\round');
		if (empty($oPosted->rid)) {
			$oAssignedRnd = $modelRnd->getActive($oApp, ['fields' => 'id,rid,mission_rid']);
			if ($oAssignedRnd) {
				$oAssignedRnds[] = $oAssignedRnd;
			}
		} else {
			foreach ($oPosted->rid as $rid) {
				$oAssignedRnd = $modelRnd->byId($rid, ['fields' => 'id,rid,mission_rid']);
				if ($oAssignedRnd) {
					$oAssignedRnds[] = $oAssignedRnd;
				}
			}
		}

		$newRecordCount = 0; // 新生成的记录数
		$modelRec = $this->model('matter\enroll\record');
		foreach ($oAssignedRnds as $oAssignedRnd) {
			foreach ($misUsers as $oMisUser) {
				if (empty($oMisUser->userid)) {
					continue;
				}
				$oMockUser = new \stdClass;
				$oMockUser->uid = $oMisUser->userid;
				$records = $modelRec->byUser($oApp, $oMockUser, ['rid' => $oAssignedRnd->rid]);
				if (empty($records)) {
					$oMockUser->nickname = $oMisUser->nickname;
					$modelRec->enroll($oApp, $oMockUser, ['nickname' => $oMockUser->nickname, 'assignRid' => $oAssignedRnd->rid]);
					$newRecordCount++;
				}
			}
		}

		return new \ResponseData($newRecordCount);
	}
}