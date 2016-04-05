<?php
namespace site\fe\matter\lottery;

include_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 抽奖活动
 */
class result extends base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 *
	 */
	public function get_action($id) {
		$log = $this->model('app\lottery\log')->byId($id);

		return new \ResponseData($log);
	}
}