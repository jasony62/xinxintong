<?php
namespace matter\enroll\record;
/**
 * 和记录复制相关的
 */
class copy_model extends \TMS_MODEL {
	/**
	 * 复制记录到指定活动
	 */
	public function toApp($oApp, $oTargetApp, $eks, $mappings) {
		$modelRec = $this->model('matter\enroll\record');
		$modelDat = $this->model('matter\enroll\data');
		$modelUsr = $this->model('matter\enroll\user');
		$modelSch = $this->model('matter\enroll\schema');
		$modelEvt = $this->model('matter\enroll\event');
		$modelAss = $this->model('matter\enroll\assoc');

		// 源活动中的协作填写题
		$aSourceCoworkSchemas = $modelSch->asAssoc($oApp->dynaDataSchemas, ['filter' => function ($oSchema) {return $oSchema->type === 'multitext' && isset($oSchema->cowork) && $oSchema->cowork === 'Y';}]);

		// 目标活动中的协作填写题
		$aTargerCoworkSchemas = $modelSch->asAssoc($oTargetApp->dynaDataSchemas, ['filter' => function ($oSchema) {return $oSchema->type === 'multitext' && isset($oSchema->cowork) && $oSchema->cowork === 'Y';}]);

		// 预处理
		$oProtoRecData = new \stdClass;
		$aPlainPairs = [];
		$aCoworkPairs = [];
		foreach ($mappings as $targetSchemaId => $oMapping) {
			if (!empty($oMapping->value)) {
				$oProtoRecData->{$targetSchemaId} = $oMapping->value;
			} else if (!empty($oMapping->from)) {
				if (!isset($aTargerCoworkSchemas[$targetSchemaId]) && !isset($aSourceCoworkSchemas[$oMapping->from])) {
					$aPlainPairs[$targetSchemaId] = $oMapping->from;
				}
				if (isset($aTargerCoworkSchemas[$targetSchemaId]) && isset($aSourceCoworkSchemas[$oMapping->from])) {
					$aCoworkPairs[$targetSchemaId] = $oMapping->from;
				}
			}
		}
		/* 默认协作填写的表态 */
		$agreed = $this->getDeepValue($oTargetApp, 'actionRule.cowork.default.agreed');

		$oNewRecs = [];
		foreach ($eks as $ek) {
			$oRecord = $modelRec->byId($ek, ['fields' => 'id,userid,nickname,data,agreed']);
			if (!$oRecord) {
				continue;
			}
			if (empty($agreed) && $oRecord->agreed === 'D') {
				$agreed = 'D';
			}
			/* 复制的数据 */
			$oNewRecData = clone $oProtoRecData;
			foreach ($aPlainPairs as $targetSchemaId => $sourceSchemaId) {
				if (!empty($oRecord->data->{$sourceSchemaId})) {
					$oNewRecData->{$targetSchemaId} = $oRecord->data->{$sourceSchemaId};
				}
			}
			/* 模拟用户 */
			$oMockUser = $modelUsr->byId($oTargetApp, $oRecord->userid, ['fields' => 'id,userid,group_id,nickname']);
			if (false === $oMockUser) {
				$oMockUser = $modelUsr->detail($oTargetApp, (object) ['uid' => $oRecord->userid], $oNewRecData);
			} else {
				$oMockUser->uid = $oMockUser->userid;
			}
			// 记录是否有关联关系
			$oRecordAcs = new \stdClass;// 问题的关联
			$oCoworkAcs = new \stdClass;// 答案的关联
			$oAssocs = $modelAss->byRecord($oRecord, $oMockUser);
			if (count($oAssocs)) {
				foreach ($oAssocs as $oAssoc) {
					if ($oAssoc->entity_a_type == 1) {
						if (!isset($oRecordAcs->{$oAssoc->entity_a_id})) {
							$oRecordAcs->{$oAssoc->entity_a_id} = [];
						}
						$oRecordAcs->{$oAssoc->entity_a_id}[] = $oAssoc;
					} else if ($oAssoc->entity_a_type == 2) {
						if (!isset($oCoworkAcs->{$oAssoc->entity_a_id})) {
							$oCoworkAcs->{$oAssoc->entity_a_id} = [];
						}
						$oCoworkAcs->{$oAssoc->entity_a_id}[] = $oAssoc;
					}
				}
			}
			/* 在目标活动中创建新记录 */
			$oNewRec = $modelRec->enroll($oTargetApp, $oMockUser);
			$modelRec->setData($oMockUser, $oTargetApp, $oNewRec->enroll_key, $oNewRecData, '', true);
			$oNewRecs[] = $oNewRec;
			/* 协作填写数据 */
			foreach ($aCoworkPairs as $targetSchemaId => $sourceSchemaId) {
				/* 补充创建新的题目数据 */
				$oRecData = new \stdClass;
				$oRecData->aid = $oNewRec->aid;
				$oRecData->rid = $oNewRec->rid;
				$oRecData->record_id = $oNewRec->id;
				$oRecData->enroll_key = $oNewRec->enroll_key;
				$oRecData->submit_at = time();
				$oRecData->userid = $oNewRec->userid;
				$oRecData->nickname = $oNewRec->nickname;
				$oRecData->group_id = $this->escape($oNewRec->group_id);
				$oRecData->schema_id = $targetSchemaId;
				$oRecData->is_multitext_root = 'Y';
				$oRecData->multitext_seq = 0;
				$oRecData->value = '[]';
				$oRecData->id = $this->insert('xxt_enroll_record_data', $oRecData, true);
				$oRecData->nickname = $oNewRec->nickname;
				$oRecData->value = [];

				$items = $modelDat->getCowork($ek, $sourceSchemaId, ['excludeRoot' => true]);
				foreach ($items as $oItem) {
					$oMockUser2 = new \stdClass;
					$oMockUser2->uid = $oItem->userid;
					$oMockUser2->nickname = $oItem->nickname;
					$oMockUser2->group_id = $oItem->group_id;
					$oNewItem = $modelDat->addCowork($oMockUser2, $oTargetApp, $oRecData, $oItem->value, $agreed);
					/* 更新用户汇总信息及积分 */
					$coworkResult = $modelEvt->submitCowork($oTargetApp, $oRecData, $oNewItem, $oMockUser2);
					/* 更新关联关系 */
					if (isset($oCoworkAcs->{$oItem->id})) {
						$current = time();
						foreach ($oCoworkAcs->{$oItem->id} as $oldCoworkAc) {
							$oAssoc = new \stdClass;
							$oAssoc->siteid = $oNewRec->siteid;
							$oAssoc->aid = $oNewRec->aid;
							$oAssoc->record_id = $oNewRec->id;
							$oAssoc->entity_a_id = $oNewItem->id;
							$oAssoc->entity_a_type = $oldCoworkAc->entity_a_type;
							$oAssoc->entity_b_id = $oldCoworkAc->entity_b_id;
							$oAssoc->entity_b_type = $oldCoworkAc->entity_b_type;
							$oAssoc->first_assoc_at = $oAssoc->last_assoc_at = $current;
							$oAssoc->public = $oldCoworkAc->public;
							$oAssoc->assoc_text = $oldCoworkAc->assoc_text;
							$oAssoc->assoc_reason = $oldCoworkAc->assoc_reason;
							$oAssoc->assoc_mode = $oldCoworkAc->assoc_mode;
							$oAssoc->assoc_num = 1;
							$oAssoc->id = $this->insert('xxt_enroll_assoc', $oAssoc, true);
							/* 记录用户日志 */
							$oLog = new \stdClass;
							$oLog->siteid = $oAssoc->siteid;
							$oLog->aid = $oAssoc->aid;
							$oLog->record_id = $oAssoc->record_id;
							$oLog->assoc_id = $oAssoc->id;
							$oLog->assoc_text = $oAssoc->assoc_text;
							$oLog->assoc_reason = $oAssoc->assoc_reason;
							$oLog->userid = $oItem->userid;
							$oLog->link_at = $current;
							$this->insert('xxt_enroll_assoc_log', $oLog, false);
						}
					}
				}
			}
			// 记录的关联
			if (isset($oRecordAcs->{$oRecord->id})) {
				$current = time();
				foreach ($oRecordAcs->{$oRecord->id} as $oldRecAc) {
					$oAssoc = new \stdClass;
					$oAssoc->siteid = $oNewRec->siteid;
					$oAssoc->aid = $oNewRec->aid;
					$oAssoc->record_id = $oNewRec->id;
					$oAssoc->entity_a_id = $oNewRec->id;
					$oAssoc->entity_a_type = $oldRecAc->entity_a_type;
					$oAssoc->entity_b_id = $oldRecAc->entity_b_id;
					$oAssoc->entity_b_type = $oldRecAc->entity_b_type;
					$oAssoc->first_assoc_at = $oAssoc->last_assoc_at = $current;
					$oAssoc->public = $oldRecAc->public;
					$oAssoc->assoc_text = $oldRecAc->assoc_text;
					$oAssoc->assoc_reason = $oldRecAc->assoc_reason;
					$oAssoc->assoc_mode = $oldRecAc->assoc_mode;
					$oAssoc->assoc_num = 1;
					$oAssoc->id = $this->insert('xxt_enroll_assoc', $oAssoc, true);
					/* 记录用户日志 */
					$oLog = new \stdClass;
					$oLog->siteid = $oAssoc->siteid;
					$oLog->aid = $oAssoc->aid;
					$oLog->record_id = $oAssoc->record_id;
					$oLog->assoc_id = $oAssoc->id;
					$oLog->assoc_text = $oAssoc->assoc_text;
					$oLog->assoc_reason = $oAssoc->assoc_reason;
					$oLog->userid = $oNewRec->userid;
					$oLog->link_at = $current;
					$this->insert('xxt_enroll_assoc_log', $oLog, false);
				}
			}
		}

		return [true, $oNewRecs];
	}
}