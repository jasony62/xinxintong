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
		$this->view_action('/pl/fe/user/register');
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
		// record account into session and cookie.
		\TMS_CLIENT::account($account);

		/* 用户注册获得积分 */
		$user = new \stdClass;
		$user->id = $account->uid;
		$user->name = $account->nickname;
		$modelCoin = $this->model('pl\coin\log');
		$modelCoin->award($user, 'pl.user.register');

		return new \ResponseData($account);
	}
}