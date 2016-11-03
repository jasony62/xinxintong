<?php
/**
 * 模版库
 */
class template extends TMS_CONTROLLER {
	/**
	 *
	 */
	public function get_access_rule() {
		$ruleAction = [
			'rule_type' => 'black',
		];

		return $ruleAction;
	}
	/**
	 *
	 */
	public function index_action() {
		TPL::output('/template');
		exit;
	}
}