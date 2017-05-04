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
	 *
	 * @param string $email
	 * @param string $password
	 */
	public function do_action() {
		$data = $this->getPostJson();
		$modelAcnt = $this->model('account');

		$email = $data->email;
		$nickname = empty($data->nickname) ? str_replace(strstr($email, '@'), '', $email) : $data->nickname;
		$password = $data->password;
		// check
		if (strlen($email) == 0 || strlen($nickname) == 0 || strlen($password) == 0) {
			return new \ParameterError("注册失败，参数不完整。");
		}
		// email existed?
		if ($modelAcnt->check_email($email)) {
			return new \DataExistedError('注册失败，注册账号已经存在。');
		}
		//
		$fromip = $this->client_ip();
		$account = $modelAcnt->register($email, $password, $nickname, $fromip);

		/* cookie中保留注册信息 */
		$modelWay = $this->model('site\fe\way');
		$registration = new \stdClass;
		$registration->unionid = $account->uid;
		$registration->uname = $email;
		$registration->nickname = $account->nickname;
		$cookieRegUser = $modelWay->shiftRegUser($registration, false);

		return new \ResponseData($account);
	}
}