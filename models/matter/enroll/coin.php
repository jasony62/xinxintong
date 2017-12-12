<?php
namespace matter\enroll;
/**
 *
 */
class coin_model extends \TMS_MODEL {
	/**
	 * 返回登记活动对应的积分规则
	 *
	 * @param $act
	 * @param $oApp
	 * @param $aOptions
	 *
	 */
	public function rulesByMatter($act, $oApp, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
		$q = [
			$fields,
			'xxt_coin_rule',
			"matter_type='enroll' and act='$act' and ",
		];
		$w = "(";
		$w .= "matter_filter='ID:{$oApp->id}'";
		if (!empty($oApp->mission_id)) {
			$w .= " or matter_filter='MISSION:{$oApp->mission_id}'";
		}
		$w .= ")";

		$q[2] .= $w;

		$rules = $this->query_objs_ss($q);
		if (count($rules) === 2) {
			foreach ($rules as $oRule) {
				if ($oRule->actor_overlap === 'R') {
					/* 覆盖项目的定义 */
					return [$oRule];
				}
			}
		}

		return $rules;
	}
	/**
	 * 返回用于记录积分的活动创建人
	 *
	 * @param object $oApp
	 */
	public function getCreator($oApp) {
		return false;
	}
}