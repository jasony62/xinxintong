<?php
namespace pl\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 平台用户管理
 */
class logout extends \pl\fe\base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'] = ['index'];

		return $rule_action;
	}
	/**
	 * 结束登录状态
	 */
	public function index_action() {
		/* 退出登录状态 */
		\TMS_CLIENT::logout();

		/* 清除自动登录状态 */
		$this->mySetCookie('_login_auto', '');
		$this->mySetCookie('_login_token', '');

		/* 返回发起操作的页面 */
		if (isset($_SERVER['HTTP_REFERER'])) {
			$referer = $_SERVER['HTTP_REFERER'];
			if (!empty($referer) && !in_array($referer, ['/'])) {
				$this->redirect($referer);
			}
		}

		$this->redirect('');
	}
}