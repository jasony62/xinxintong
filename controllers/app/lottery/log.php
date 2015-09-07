<?php
namespace app\lottery;

require_once dirname(dirname(__FILE__)) . '/member_base.php';
/**
 *
 */
class log extends \member_base {
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