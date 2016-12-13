<?php
namespace matter\enroll;
/**
 *
 */
class coin_model extends \TMS_MODEL {
	/**
	 * 返回登记活动对应的积分规则
	 *
	 * @param object $article
	 */
	public function &rulesByMatter($act, $app) {
		$q = ['*', 'xxt_coin_rule', "matter_type='enroll' and act='$act' and "];

		$w = "(";
		$w .= "matter_filter='*'";
		$w .= "or matter_filter='ID:{$app->id}'";
		$w .= ")";

		$q[2] .= $w;

		$rules = $this->query_objs_ss($q);

		return $rules;
	}
	/**
	 * 返回用于记录积分的活动创建人
	 *
	 * @param object $app
	 */
	public function getCreator($app) {
		return false;
	}
}