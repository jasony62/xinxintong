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
		/* uname */
		$uname = $data->uname;
		$isValidUname = false;
		if (1 === preg_match('/^\S+@(\S+[-.])+\S{2,4}$/', $uname)) {
			$isValidUname = true;
		} else if (1 === preg_match('/^1(3[0-9]|4[57]|5[0-35-9]|7[0135678]|8[0-9]|9[0-9])\\d{8}$/', $uname)) {
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
	
		/* password */
		$password = \TMS_MODEL::unescape($data->password);
		$rst = tms_pwd_check($password, ['account' => $uname]);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}

		$modelWay = $this->model('site\fe\way');
		$cookieRegUser = $modelWay->getCookieRegUser();
		if ($cookieRegUser) {
			if (isset($cookieRegUser->loginExpire)) {
				return new \ResponseError("请退出当前账号再登录");
			}
			$modelWay->quitRegUser();
		}

		$modelReg = $this->model('site\user\registration');

		$aOptions = [];
		/* nickname */
		if (isset($data->nickname)) {
			$aOptions['nickname'] = $data->nickname;
		} else if (isset($user->nickname)) {
			$aOptions['nickname'] = $user->nickname;
		}
		/* other options */
		$aOptions['from_ip'] = $this->client_ip();

		/* create registration */
		$oRegUser = $modelReg->create($this->siteId, $uname, $password, $aOptions);
		if ($oRegUser[0] === false) {
			return new \ResponseError($oRegUser[1]);
		}
		$oRegUser = $oRegUser[1];

		/* cookie中保留注册信息 */
		$aResult = $modelWay->shiftRegUser($oRegUser, false);
		if (false === $aResult[0]) {
			return new \ResponseError($aResult[1]);
		}
		$oCookieRegUser = $aResult[1];

		$oCookieRegUser->login = (object) ['uname' => $uname, 'nickname' => $aOptions['nickname']];

		if ($referer = $this->myGetCookie('_user_access_referer')) {
			$oCookieRegUser->_loginReferer = $referer;
			$this->mySetCookie('_user_access_referer', null);
		}

		return new \ResponseData($oCookieRegUser);
	}
}