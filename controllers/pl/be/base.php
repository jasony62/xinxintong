<?php
namespace pl\be;
/**
 *
 */
class base extends \TMS_CONTROLLER {
	/**
	 * 检查用户权限
	 */
	public function __construct() {
		if ($account = \TMS_CLIENT::account()) {
			$model = $this->model('account');
			if (!$model->canManagePlatform($account->uid)) {
				die('没有访问权限');
			}
		} else {
			die('没有访问权限');
		}
	}
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';

		return $rule_action;
	}
	/**
	 * 获得当前登录账号的用户信息
	 */
	protected function &accountUser() {
		$account = \TMS_CLIENT::account();
		if ($account) {
			$user = new \stdClass;
			$user->id = $account->uid;
			$user->name = $account->nickname;
			$user->src = 'A';
		} else {
			$user = false;
		}
		return $user;
	}
}