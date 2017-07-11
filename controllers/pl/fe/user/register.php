<?php
namespace pl\fe\user;
/**
 * 平台管理端用户注册
 */
class register extends \TMS_CONTROLLER {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'] = array();
		$rule_action['actions'][] = 'index';
		$rule_action['actions'][] = 'do';

		return $rule_action;
	}
	/**
	 * 进入平台管理页面用户身份验证页面
	 */
	public function index_action() {
		\TPL::output('/pl/fe/user/register');
		exit;
	}
	/**
	 * 用户注册
	 */
	public function do_action() {
		$data = $this->getPostJson();
		$modelAcnt = $this->model('account');

		if (empty($data->uname)) {
			return new \ParameterError("登录账号不允许为空");
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
		if (empty($data->password)) {
			return new \ParameterError("登录密码不允许为空");
		}
		if (empty($data->nickname)) {
			return new \ParameterError("账号昵称不允许为空");
		}

		$nickname = $data->nickname;
		$password = $data->password;

		// uname existed?
		if ($modelAcnt->checkUname($uname)) {
			return new \DataExistedError('注册失败，注册账号已经存在。');
		}
		//
		$fromip = $this->client_ip();
		$oAccount = $modelAcnt->register($uname, $password, $nickname, $fromip);

		/* cookie中保留注册信息 */
		$modelWay = $this->model('site\fe\way');
		$oRegistration = new \stdClass;
		$oRegistration->unionid = $oAccount->uid;
		$oRegistration->uname = $uname;
		$oRegistration->nickname = $oAccount->nickname;
		$cookieRegUser = $modelWay->shiftRegUser($oRegistration, false);

		return new \ResponseData($oAccount);
	}
}