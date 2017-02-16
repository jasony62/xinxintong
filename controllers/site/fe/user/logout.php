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
		/*更新cookie状态*/
		$modelWay = $this->model('site\fe\way');
		$modelWay->quitRegUser($this->siteId);
		if ($redirect === 'Y') {
			$referer = $_SERVER['HTTP_REFERER'];
			$this->redirect($referer);
		} else {
			return new \ResponseData('ok');
		}
	}
}