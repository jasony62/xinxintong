<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 站点用户
 */
class login extends \site\fe\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'] = array();
		$rule_action['actions'][] = 'index';
		$rule_action['actions'][] = 'do';

		return $rule_action;
	}
	/**
	 * 打开登录页面
	 */
	public function index_action() {
		\TPL::output('/site/fe/user/login');
		exit;
	}
	/**
	 * 执行帐号注册
	 */
	public function do_action() {
		$data = $this->getPostJson();

		$modelAct = $this->model('site\user\account');
		$account = $modelAct->validate($this->siteId, $data->uname, $data->password);
		if (is_string($account)) {
			return new \ResponseError($account);
		}
		/*记录登录状态*/
		$fromip = $this->client_ip();
		$modelAct->updateLastLogin($account->uid, $fromip);

		/*更新cookie状态*/
		/*user*/
		$modelWay = $this->model('site\fe\way');
		$cookieUser = $modelWay->getCookieUser($this->siteId);
		$cookieUser->uname = $data->uname;
		$cookieUser->loginExpire = time() + (86400 * TMS_COOKIE_SITE_LOGIN_EXPIRE);
		$modelWay->setCookieUser($this->siteId, $cookieUser);
		/*login*/
		$modelWay->setCookieLogin($this->siteId, $cookieUser);

		return new \ResponseData('ok');
	}
}