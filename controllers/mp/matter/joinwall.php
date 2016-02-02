<?php
namespace mp\matter;

require_once dirname(__FILE__) . '/matter_ctrl.php';

class joinwall extends \mp\mp_controller {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';
		return $rule_action;
	}
	/**
	 *
	 */
	public function index_action() {
		$p = array(
			'id,title',
			'xxt_wall',
			"mpid='$this->mpid'",
		);
		$walls = $this->model()->query_objs_ss($p);

		return new \ResponseData($walls);
	}
	/**
	 *
	 */
	public function list_action() {
		return $this->index_action();
	}
}