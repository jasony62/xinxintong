<?php
namespace pl\fe\matter\joinwall;

require_once dirname(dirname(__FILE__)) . '/base.php';

class main extends \pl\fe\matter\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';
		return $rule_action;
	}
	/**
	 *
	 */
	public function list_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$p = array(
			'id,title',
			'xxt_wall',
			"siteid='$site' and active = 'Y'",
		);
		$walls = $this->model()->query_objs_ss($p);

		return new \ResponseData($walls);
	}
}