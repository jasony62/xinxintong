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
		$rule_action['actions'][] = 'getCaptcha';

		return $rule_action;
	}
	/**
	 * 执行登录
	 */
	public function do_action() {
		$data = $this->getPostJson();
		if (empty($data->uname) || empty($data->password) || empty($data->pin)) {
			return new \ResponseError("登录信息不完整");
		}

		$codeSession = \TMS_APP::S('auth_code');
		if (empty($codeSession) ||  $codeSession !== $data->pin) {
			\TMS_APP::setS('auth_code', '');
			return new \ResponseError("验证码错误请重新输入");
		}

		\TMS_APP::setS('auth_code', '');
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
		if ($referer = $this->myGetCookie('_auth_referer')) {
			$cookieUser->_loginReferer = $referer;
			$this->mySetCookie('_auth_referer', null);
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
	/*
	* 获取验证码
	* $codelen  验证码的个数
	* $width  验证码的宽度
	* $height  验证码的高度
	* $fontsize  验证码的字体大小
	*/
	public function getCaptcha_action($codelen = 4, $width = 130, $height = 50, $fontsize = 20) {
		require_once TMS_APP_DIR . '/lib/validatecode.php';

		$captcha = new \ValidateCode($codelen, $width, $height, $fontsize);
		$captcha->doImg();

		$code = $captcha->getCode();
		\TMS_APP::setS('auth_code', $code);
	}
}