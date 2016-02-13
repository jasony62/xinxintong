<?php
namespace pl\fe\user;
/**
 * 平台管理端用户登录
 */
class login extends \TMS_CONTROLLER {

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
		$this->view_action('/pl/fe/user/login');
	}
	/**
	 * login
	 *
	 * $param string $email
	 * $param string $password
	 */
	public function do_action() {
		$data = $this->getPostJson();

		$result = $this->model('account')->validate($data->email, $data->password);
		if ($result->err_code != 0) {
			return $result;
		}
		$account = $result->data;

		$fromip = $this->client_ip();
		$this->model('account')->update_last_login($account->uid, $fromip);

		return new \ResponseData($account->uid);
	}
}