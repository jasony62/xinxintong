<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 站点用户注册
 */
class register extends \site\fe\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 执行注册
	 */
	public function do_action() {
		$user = $this->who;
		$data = $this->getPostJson();
		if (empty($data->uname)) {
			return new \ResponseError("登录账号不允许为空");
		}
		$uname = $data->uname;
		$isValidUname = false;
		if (1 === preg_match('/^\S+@(\S+[-.])+\S{2,4}$/', $uname)) {
			$isValidUname = true;
		} else if (1 === preg_match('/^1(3[0-9]|4[57]|5[0-35-9]|7[0135678]|8[0-9])\\d{8}$/', $uname)) {
			$isValidUname = true;
		}
		if (false === $isValidUname) {
			return new \ResponseError("请使用手机号或邮箱作为登录账号");
		}
		if (empty($data->nickname)) {
			return new \ParameterError("账号昵称不允许为空");
		}
		if (empty($data->password)) {
			return new \ResponseError("登录密码不允许为空");
		}

		$modelWay = $this->model('site\fe\way');
		$cookieRegUser = $modelWay->getCookieRegUser();
		if ($cookieRegUser) {
			return new \ResponseError("请退出当前账号再注册");
		}

		$modelReg = $this->model('site\user\registration');
		/* uname */
		/* password */
		$password = $data->password;

		$options = [];
		/* nickname */
		if (isset($data->nickname)) {
			$options['nickname'] = $data->nickname;
		} else if (isset($user->nickname)) {
			$options['nickname'] = $user->nickname;
		}
		/* other options */
		$options['from_ip'] = $this->client_ip();

		/* create registration */
		$registration = $modelReg->create($this->siteId, $uname, $password, $options);
		if ($registration[0] === false) {
			return new \ResponseError($registration[1]);
		}
		$registration = $registration[1];

		/* cookie中保留注册信息 */
		$cookieRegUser = $modelWay->shiftRegUser($registration, false);
		$cookieRegUser->login = (object) ['uname' => $uname, 'nickname' => $options['nickname']];

		if ($referer = $this->myGetCookie('_user_access_referer')) {
			$cookieRegUser->_loginReferer = $referer;
			$this->mySetCookie('_user_access_referer', null);
		}

		return new \ResponseData($cookieRegUser);
	}
}