<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动打分结果
 */
class marks extends base {
	/**
	 * 打分题汇总信息
	 */
	public function get_action($app, $rid = '', $gid = '') {
		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$rid = empty($rid) ? [] : explode(',', $rid);

		// 查询结果
		$modelRec = $this->model('matter\enroll\record');
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
					'score,value',
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

				$datas = $modelRec->query_objs_ss($q);
				$oStat = new \stdClass;
				$oStat->count = count($datas);
				$sum = 0;
				foreach ($datas as $oRecData) {
					$sum += (int) $oRecData->score;
					if (!empty($oRecData->value)) {
						$oValue = json_decode($oRecData->value);
						foreach ($oValue as $key => $score) {
							if (!isset($oStat->{$key})) {
								$oStat->{$key} = (int) $score;
							} else {
								$oStat->{$key} += (int) $score;
							}
						}
					}
				}
				$oStat->sum = $sum;
				$oResult->{$oSchema->id} = $oStat;
			}
		}

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

		$oStat = $modelRec->query_obj_ss($q);
		$oStat->sum = (float) number_format($oStat->sum, 2, '.', '');
		$oStat->count = (int) $oStat->count;
		$oResult->total = $oStat;

		return new \ResponseData($oResult);
	}
}