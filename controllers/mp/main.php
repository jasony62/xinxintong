<?php
namespace mp;

require_once dirname(__FILE__) . "/mp_controller.php";
/**
 *
 */
class main extends mp_controller {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';

		return $rule_action;
	}
	/**
	 *
	 */
	public function index_action() {
		$this->view_action('/mp/main');
	}
}