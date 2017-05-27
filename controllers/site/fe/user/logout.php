<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 站点注册用户退出登录状态
 */
class logout extends \site\fe\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'] = array();
		$rule_action['actions'][] = 'do';

		return $rule_action;
	}
	/**
	 * 执行退出登录状态
	 */
	public function do_action($redirect = 'N') {
		/* 退出登录状态 */
		\TMS_CLIENT::logout();

		/* 清除自动登录状态 */
		$this->mySetCookie('_login_auto', '');
		$this->mySetCookie('_login_token', '');

		$modelWay = $this->model('site\fe\way');
		$modelWay->quitRegUser();

		if ($redirect === 'Y') {
			$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
			$this->redirect($referer);
		} else {
			return new \ResponseData('ok');
		}
	}
}