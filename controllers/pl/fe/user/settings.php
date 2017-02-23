<?php
namespace pl\fe\user;
/**
 * 平台管理端用户登录
 */
class settings extends \TMS_CONTROLLER {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'] = array();
		$rule_action['actions'][] = 'index';

		return $rule_action;
	}
	/**
	 * 进入平台管理页面用户身份验证页面
	 */
	public function index_action() {
		\TPL::output('/pl/fe/user/settings');
		exit;
	}
}