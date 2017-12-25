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
	 * 执行登录
	 */
	public function do_action() {
		$data = $this->getPostJson();
		if (empty($data->uname) || empty($data->password)) {
			return new \ResponseError("登录信息不完整");
		}

		$modelWay = $this->model('site\fe\way');
		$modelReg = $this->model('site\user\registration');

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

		$cookieUser = $modelWay->who($this->siteId);
		if ($referer = $this->myGetCookie('_user_access_referer')) {
			$cookieUser->_loginReferer = $referer;
			$this->mySetCookie('_user_access_referer', null);
		}
		/**
		 * 支持自动登录
		 */
		if (isset($data->autologin) && $data->autologin === 'Y') {
			$expire = time() + (86400 * 365 * 10);
			$ua = $_SERVER['HTTP_USER_AGENT'];
			$token = [
				'uid' => $registration->unionid,
				'email' => $registration->uname,
				'password' => $registration->password,
			];
			$cookiekey = md5($ua);
			$cookieToken = json_encode($token);
			$encoded = $modelWay->encrypt($cookieToken, 'ENCODE', $cookiekey);

			$this->mySetCookie('_login_auto', 'Y', $expire);
			$this->mySetCookie('_login_token', $encoded, $expire);
		}

		return new \ResponseData($cookieUser);
	}
}