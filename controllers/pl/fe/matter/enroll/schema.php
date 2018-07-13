<?php
namespace pl\fe\matter\enroll;

require_once dirname(__FILE__) . '/main_base.php';
/**
 * 登记活动题目
 */
class schema extends main_base {
	/**
	 * 返回登记活动题目定义
	 *
	 * @param string $app
	 * @param string $rid
	 *
	 */
	public function get_action($app, $rid = '') {
		$modelApp = $this->model('matter\enroll');
		$aOptions = [
			'cascaded' => 'N',
		];
		if (!empty($rid)) {
			$aOptions['appRid'] = $rid;
		}
		$oApp = $modelApp->byId($app, $aOptions);
		if ($oApp === false || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$dataSchemas = $oApp->dynaDataSchemas;

		return new \ResponseData($dataSchemas);
	}
	/**
	 * 由目标活动的选择题创建填写题
	 *
	 * @param string $app 需要创建题目的登记活动
	 * @param string $targetApp 数据来源的登记活动
	 *
	 */
	public function inputByOption_action($app, $targetApp, $round = '') {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$oPosted = $this->getPostJson();
		if (empty($oPosted->schemas)) {
			return new \ParameterError('没有指定题目');
		}

		$modelEnl = $this->model('matter\enroll');

		$oApp = $modelEnl->byId($app, ['fields' => 'siteid,state,mission_id,sync_mission_round', 'appRid' => $round]);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oTargetApp = $modelEnl->byId($targetApp, ['fields' => 'siteid,state,mission_id,data_schemas,sync_mission_round']);
		if (false === $oTargetApp || $oTargetApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		if ($oApp->mission_id !== $oTargetApp->mission_id) {
			return new \ParameterError('仅支持在同一个项目的活动间通过记录生成题目');
		}

		$targetSchemas = []; // 目标应用中选择的题目
		foreach ($oPosted->schemas as $oSchema) {
			foreach ($oTargetApp->dataSchemas as $oSchema2) {
				if ($oSchema->id === $oSchema2->id) {
					$targetSchemas[] = $oSchema2;
					break;
				}
			}
		}
		if (empty($targetSchemas)) {
			return new \ParameterError('指定的题目无效');
		}

		/* 匹配的轮次 */
		$oAssignedRnd = $oApp->appRound;
		if ($oAssignedRnd) {
			$modelRnd = $this->model('matter\enroll\round');
			$oTargetAppRnd = $modelRnd->byMissionRid($oTargetApp, $oAssignedRnd->mission_rid, ['fields' => 'rid,mission_rid']);
		}

		/* 目标活动的统计结果 */
		$modelRec = $this->model('matter\enroll\record');
		$aTargetData = $modelRec->getStat($oTargetApp, !empty($oTargetAppRnd) ? $oTargetAppRnd->rid : '', 'N');
		$newSchemas = []; // 根据记录创建的题目
		$modelDat = $this->model('matter\enroll\data');
		$modelSch = $this->model('matter\enroll\schema');
		foreach ($targetSchemas as $oTargetSchema) {
			switch ($oTargetSchema->type) {
			case 'single':
			case 'multiple':
				if (!empty($aTargetData[$oTargetSchema->id]->ops)) {
					$targetOptions = $aTargetData[$oTargetSchema->id]->ops;
					usort($targetOptions, function ($a, $b) {
						return $a->c < $b->c;
					});
					$bGenerated = false;
					if (!empty($oTargetSchema->limit->scope) && !empty($oTargetSchema->limit->num) && (int) $oTargetSchema->limit->num) {
						if ($oTargetSchema->limit->scope === 'top') {
							$modelSch->genSchemaByTopOptions($oTargetSchema, $targetOptions, $oTargetSchema->limit->num, $newSchemas);
							$bGenerated = true;
						} else if ($oTargetSchema->limit->scope === 'checked') {
							$modelSch->genSchemaByCheckedOptions($oTargetSchema, $targetOptions, $oTargetSchema->limit->num, $newSchemas);
							$bGenerated = true;
						}
					}
					if (!$bGenerated) {
						if (!empty($oPosted->limit->scope) && !empty($oPosted->limit->num) && (int) $oPosted->limit->num) {
							if ($oPosted->limit->scope === 'top') {
								$modelSch->genSchemaByTopOptions($oTargetSchema, $targetOptions, $oPosted->limit->num, $newSchemas);
								$bGenerated = true;
							} else if ($oPosted->limit->scope === 'checked') {
								$modelSch->genSchemaByCheckedOptions($oTargetSchema, $targetOptions, $oPosted->limit->num, $newSchemas);
								$bGenerated = true;
							}
						}
					}
					if (!$bGenerated) {
						$modelSch->genSchemaByTopOptions($oTargetSchema, $targetOptions, count($targetOptions), $newSchemas);
					}
				}
				break;
			}
		}

		return new \ResponseData($newSchemas);
	}
	/**
	 * 由目标活动的填写题创建打分题
	 *
	 * @param string $app 需要创建题目的登记活动
	 * @param string $targetApp 数据来源的登记活动
	 *
	 */
	public function scoreByInput_action($app, $targetApp, $round = '') {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$oPosted = $this->getPostJson();
		if (empty($oPosted->schemas)) {
			return new \ParameterError('没有指定题目');
		}
		if (empty($oPosted->proto->range) || empty($oPosted->proto->ops) || !is_array($oPosted->proto->ops)) {
			return new \ParameterError('没有指定题目的原型，或原型不完整');
		}
		if (count($oPosted->proto->range) !== 2 || !is_int((int) $oPosted->proto->range[0]) || !is_int((int) $oPosted->proto->range[1])) {
			return new \ParameterError('题目原型中的【range】参数格式不正确');
		}
		foreach ($oPosted->proto->ops as $oScoreOption) {
			if (empty($oScoreOption->v) || empty($oScoreOption->l)) {
				return new \ParameterError('题目原型中的【ops】参数格式不正确');
			}
		}

		$modelEnl = $this->model('matter\enroll');

		$oApp = $modelEnl->byId($app, ['fields' => 'siteid,state,mission_id,sync_mission_round', 'appRid' => $round]);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oTargetApp = $modelEnl->byId($targetApp, ['fields' => 'siteid,state,mission_id,data_schemas,sync_mission_round']);
		if (false === $oTargetApp || $oTargetApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		if ($oApp->mission_id !== $oTargetApp->mission_id) {
			return new \ParameterError('仅支持在同一个项目的活动间通过记录生成题目');
		}

		$targetSchemas = []; // 目标应用中选择的题目
		foreach ($oPosted->schemas as $oSchema) {
			foreach ($oTargetApp->dataSchemas as $oSchema2) {
				if ($oSchema->id === $oSchema2->id && in_array($oSchema2->type, ['shorttext', 'longtext'])) {
					$targetSchemas[] = $oSchema2;
					break;
				}
			}
		}
		if (empty($targetSchemas)) {
			return new \ParameterError('目标活动中指定的题目无效');
		}
		$oAssignedRnd = $oApp->appRound;
		if ($oAssignedRnd) {
			$modelRnd = $this->model('matter\enroll\round');
			$oTargetAppRnd = $modelRnd->byMissionRid($oTargetApp, $oAssignedRnd->mission_rid, ['fields' => 'rid,mission_rid']);
		}

		/* 目标活动的统计结果 */
		$modelRec = $this->model('matter\enroll\record');
		$oOptions = [];
		if (isset($oTargetAppRnd->rid)) {
			$oOptions['record'] = (object) ['rid' => $oTargetAppRnd->rid];
		}
		$aSearchResult = $modelRec->byApp($oTargetApp, $oOptions);
		$aRecords = $aSearchResult->records;
		if (empty($aRecords)) {
			return new \ParameterError('目标活动中没有填写记录');
		}
		$newSchemas = []; // 根据记录创建的题目
		foreach ($targetSchemas as $oTargetSchema) {
			foreach ($aRecords as $oTargetRecord) {
				if (empty($oTargetRecord->data->{$oTargetSchema->id})) {
					continue;
				}
				/* 新题目 */
				$oNewSchema = new \stdClass;
				$oNewSchema->id = 's' . $oTargetRecord->id;
				$oNewSchema->title = $oTargetRecord->data->{$oTargetSchema->id};
				$oNewSchema->type = 'score';
				$oNewSchema->required = 'Y';
				$oNewSchema->range = $oPosted->proto->range;
				$oNewSchema->ops = $oPosted->proto->ops;
				if (!empty($oPosted->proto->requireScore)) {
					$oNewSchema->requireScore = 'Y';
					$oNewSchema->scoreMode = 'evaluation';
				}
				/* 记录数据来源 */
				$oNewSchema->dsSchema = (object) ['ek' => $oTargetRecord->enroll_key, 'userid' => $oTargetRecord->userid, 'nickname' => $oTargetRecord->nickname];
				$newSchemas[] = $oNewSchema;
			}
		}

		return new \ResponseData($newSchemas);
	}
	/**
	 * 由目标活动的打分题创建填写题
	 *
	 * @param string $app 需要创建题目的登记活动
	 * @param string $targetApp 数据来源的登记活动
	 *
	 */
	public function inputByScore_action($app, $targetApp, $round = '') {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$oPosted = $this->getPostJson();
		if (empty($oPosted->schemas)) {
			return new \ParameterError('没有指定题目');
		}

		$modelEnl = $this->model('matter\enroll');

		$oApp = $modelEnl->byId($app, ['fields' => 'siteid,state,mission_id,sync_mission_round', 'appRid' => $round]);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oTargetApp = $modelEnl->byId($targetApp, ['fields' => 'siteid,state,mission_id,data_schemas,sync_mission_round']);
		if (false === $oTargetApp || $oTargetApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		if ($oApp->mission_id !== $oTargetApp->mission_id) {
			return new \ParameterError('仅支持在同一个项目的活动间通过记录生成题目');
		}

		$targetSchemas = []; // 目标应用中选择的题目
		foreach ($oPosted->schemas as $oSchema) {
			foreach ($oTargetApp->dynaDataSchemas as $oSchema2) {
				if (empty($oSchema2->dynamic) || $oSchema2->dynamic !== 'Y' || empty($oSchema2->model->schema->id)) {
					continue;
				}
				if ($oSchema->id === $oSchema2->model->schema->id) {
					$targetSchemas[$oSchema2->id] = $oSchema2;
				}
			}
		}
		if (empty($targetSchemas)) {
			return new \ParameterError('指定的题目无效');
		}

		/* 匹配的轮次 */
		$oAssignedRnd = $oApp->appRound;
		$modelRnd = $this->model('matter\enroll\round');
		$oTargetAppRnd = $modelRnd->byMissionRid($oTargetApp, $oAssignedRnd->mission_rid, ['fields' => 'rid,mission_rid']);

		// 查询结果
		$modelRec = $this->model('matter\enroll\record');
		$oResult = $modelRec->score4Schema($oTargetApp, isset($oTargetAppRnd->rid) ? $oTargetAppRnd->rid : '');
		unset($oResult->sum);
		$aResult = (array) $oResult;
		uasort($aResult, function ($a, $b) {
			return (int) $b - (int) $a;
		});

		$newSchemas = [];
		$i = 0;
		foreach ($aResult as $schemaId => $score) {
			if ($i >= $oPosted->limit->num) {
				break;
			}
			$oProtoSchema = $targetSchemas[$schemaId];
			$oNewSchema = new \stdClass;
			$oNewSchema->id = $oProtoSchema->id;
			$oNewSchema->title = $oProtoSchema->title;
			$oNewSchema->type = 'longtext';
			$newSchemas[] = $oNewSchema;
			$i++;
		}

		return new \ResponseData($newSchemas);
	}
}