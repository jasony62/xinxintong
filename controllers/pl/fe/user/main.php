<?php
namespace pl\fe\user;
/**
 * 平台管理端用户管理
 */
class main extends \TMS_CONTROLLER {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'] = array();
		$rule_action['actions'][] = 'hello';
		$rule_action['actions'][] = 'view';
		$rule_action['actions'][] = 'register';
		$rule_action['actions'][] = 'login';

		return $rule_action;
	}
	/**
	 * 结束登录状态
	 */
	public function logout_action() {
		\TMS_CLIENT::logout();
		$this->redirect('');
	}
	/**
	 * 修改当前用户的口令
	 */
	public function changePwd_action() {
		$account = \TMS_CLIENT::account();
		if ($account === false) {
			return new \ResponseTimeout();
		}

		$data = $this->getPostJson();
		/**
		 * check old password
		 */
		$old_pwd = $data->opwd;
		$result = $this->model('account')->validate($account->email, $old_pwd);
		if ($result->err_code != 0) {
			return $result;
		}

		/**
		 * set new password
		 */
		$new_pwd = $data->npwd;
		$this->model('account')->change_password($account->email, $new_pwd, $account->salt);

		return new \ResponseData($account->uid);
	}
}