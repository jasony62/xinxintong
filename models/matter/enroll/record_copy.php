<?php
namespace matter\enroll;
/**
 * 和记录复制相关的
 */
class record_copy_model extends \TMS_MODEL {
	/**
	 * 复制记录到指定活动
	 */
	public function exportToApp($oApp, $oTargetApp, $eks, $mappings) {
		$modelRec = $this->model('matter\enroll\record');
		$modelDat = $this->model('matter\enroll\data');
		$modelUsr = $this->model('matter\enroll\user');
		$modelSch = $this->model('matter\enroll\schema');
		$modelEvt = $this->model('matter\enroll\event');

		// 源活动中的协作填写题
		$aSourceCoworkSchemas = $modelSch->asAssoc($oApp->dynaDataSchemas, ['filter' => function ($oSchema) {return $oSchema->type = 'multitext' && isset($oSchema->cowork) && $oSchema->cowork === 'Y';}]);

		// 目标活动中的协作填写题
		$aTargerCoworkSchemas = $modelSch->asAssoc($oApp->dynaDataSchemas, ['filter' => function ($oSchema) {return $oSchema->type = 'multitext' && isset($oSchema->cowork) && $oSchema->cowork === 'Y';}]);

		// 预处理
		$oProtoRecData = new \stdClass;
		$aPlainPairs = [];
		$aCoworkPairs = [];
		foreach ($mappings as $targetSchemaId => $oMapping) {
			if (isset($oMapping->value)) {
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
		if (empty($agreed) && $oRecord->agreed === 'D') {
			$agreed = 'D';
		}

		$count = 0;
		foreach ($eks as $ek) {
			$oRecord = $modelRec->byId($ek, ['fields' => 'userid,nickname,data']);
			if (!$oRecord) {
				continue;
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

			/* 在目标活动中创建新记录 */
			$oNewRec = $modelRec->enroll($oTargetApp, $oMockUser);
			$modelRec->setData($oMockUser, $oTargetApp, $oNewRec->enroll_key, $oNewRecData, '', true);
			/* 协作填写数据 */
			foreach ($aCoworkPairs as $targetSchemaId => $sourceSchemaId) {
				/* 补充创建新的题目数据 */
				$oRecData = new \stdClass;
				$oRecData->aid = $oNewRec->aid;
				$oRecData->rid = $oNewRec->rid;
				$oRecData->enroll_key = $oNewRec->enroll_key;
				$oRecData->submit_at = time();
				$oRecData->userid = $oNewRec->userid;
				$oRecData->nickname = $oNewRec->nickname;
				$oRecData->group_id = $this->escape($oNewRec->group_id);
				$oRecData->schema_id = $targetSchemaId;
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
				}
			}

			$count++;
		}

		return $count;
	}
}