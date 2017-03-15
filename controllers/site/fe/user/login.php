<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 站点注册用户登录
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
		/* 整理cookie中的数据，便于后续处理 */
		$modelWay = $this->model('site\fe\way');
		$modelWay->resetAllCookieUser();

		/* 保存页面来源 */
		if (isset($_SERVER['HTTP_REFERER'])) {
			$referer = $_SERVER['HTTP_REFERER'];
			if (!empty($referer) && !in_array($referer, array('/'))) {
				if (false === strpos($referer, '/fe/user')) {
					$this->mySetCookie('_auth_referer', $referer);
				}
			}
		}

		\TPL::output('/site/fe/user/login');
		exit;
	}
	/**
	 * 执行登录
	 */
	public function do_action() {
		$data = $this->getPostJson();
		if (empty($data->uname) || empty($data->password)) {
			return new \ResponseError("登录信息不完整");
		}

		$modelWay = $this->model('site\fe\way');
		$modelReg = $this->model('site\user\registration');
		$modelAct = $this->model('site\user\account');

		$cookieRegUser = $modelWay->getCookieRegUser();
		if ($cookieRegUser) {
			if (isset($cookieRegUser->loginExpire)) {
				return new \ResponseError("请退出当前账号再登录");
			}
			$modelWay->quitRegUser();
		}

		$registration = $modelReg->validate($data->uname, $data->password);
		if (is_string($registration)) {
			return new \ResponseError($registration);
		}
		/* 记录登录状态 */
		$fromip = $this->client_ip();
		$modelReg->updateLastLogin($registration->unionid, $fromip);

		/* cookie中保留注册信息 */
		$cookieRegUser = $modelWay->shiftRegUser($registration);

		$cookieUser = $modelWay->getCookieUser($this->siteId);

		if ($referer = $this->myGetCookie('_auth_referer')) {
			$cookieUser->_loginReferer = $referer;
			$this->mySetCookie('_auth_referer', null);
		}

		return new \ResponseData($cookieUser);
	}
}