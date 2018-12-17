<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 记录活动打分结果
 */
class marks extends base {
	/**
	 * 打分题汇总信息
	 */
	public function get_action($app, $rid = '', $gid = '') {
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1' || empty($oApp->dynaDataSchemas)) {
			return new \ObjectNotFoundError();
		}

		if (!empty($rid)) {
			$rid = explode(',', $rid);
		}

		$oResult = $this->_getMarks($oApp, $rid, $gid);

		/*所有题的得分合计*/
		$q = [
			'sum(score) sum,count(*) count',
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

		$oStat = $modelApp->query_obj_ss($q);
		$oStat->sum = (float) number_format($oStat->sum, 2, '.', '');
		$oStat->count = (int) $oStat->count;
		$oResult->total = $oStat;

		return new \ResponseData($oResult);
	}
	/**
	 * 更新引用记录的得分
	 */
	public function renewReferScore_action($app) {
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1' || empty($oApp->dynaDataSchemas)) {
			return new \ObjectNotFoundError();
		}
		$modelSch = $this->model('matter\enroll\schema');
		$aSchemasById = $modelSch->asAssoc($oApp->dynaDataSchemas, ['filter' => function ($oSchema) {return $oSchema->type === 'score' && $this->getDeepValue($oSchema, 'requireScore') === 'Y' && isset($oSchema->referRecord);}]);

		$oResult = $this->_getMarks($oApp);
		foreach ($oResult as $schemaId => $oStat) {
			$oReferRecord = $aSchemasById[$schemaId]->referRecord;
			if (isset($oReferRecord->ds->data_id)) {
				$modelApp->update('xxt_enroll_record_data', ['score' => $oStat->sum], ['id' => $oReferRecord->ds->data_id]);
			}
		}

		return new \ResponseData($oResult);
	}
	/**
	 * 打分题汇总信息
	 */
	private function _getMarks($oApp, $rid = '', $gid = '') {
		$modelRec = $this->model('matter\enroll\record');
		$oResult = new \stdClass;
		$dataSchemas = $oApp->dynaDataSchemas;
		if (empty($rid)) {
			$rid = $oApp->appRound->rid;
		} else {
			if (is_string($rid) && in_array($rid, ['all', 'ALL'])) {
				$rid = '';
			} else if (is_array($rid)) {
				if (!empty(array_intersect(['all', 'ALL'], $rid))) {
					$rid = '';
				}
			}
		}

		/* 每道题目的得分 */
		foreach ($dataSchemas as $oSchema) {
			if ($oSchema->type === 'score' && $this->getDeepValue($oSchema, 'requireScore') === 'Y') {
				$q = [
					'score,value',
					'xxt_enroll_record_data',
					['aid' => $oApp->id, 'schema_id' => $oSchema->id, 'state' => 1],
				];
				if (!empty($rid)) {
					$q[2]['rid'] = $rid;
				}

				if (!empty($gid)) {
					$q[2]['group_id'] = $gid;
				}

				$datas = $modelRec->query_objs_ss($q);
				$oStat = new \stdClass;
				$oStat->count = count($datas);
				$sum = 0;
				foreach ($datas as $oRecData) {
					$sum += (int) $oRecData->score;
					if (!empty($oRecData->value)) {
						$oValue = json_decode($oRecData->value);
						if ($oValue && is_object($oValue)) {
							foreach ($oValue as $key => $score) {
								if (!isset($oStat->{$key})) {
									$oStat->{$key} = (int) $score;
								} else {
									$oStat->{$key} += (int) $score;
								}
							}
						}
					}
				}
				$oStat->sum = $sum;
				$oResult->{$oSchema->id} = $oStat;
			}
		}

		return $oResult;
	}
}