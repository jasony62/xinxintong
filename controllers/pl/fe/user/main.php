<?php
namespace pl\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 平台用户管理
 */
class main extends \pl\fe\base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'get';

		return $rule_action;
	}
	/**
	 * 获得当前用户信息
	 */
	public function get_action() {
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseData(false);
		}

		$account = $this->model('account')->byId($loginUser->id, ['fields' => 'email,nickname']);

		return new \ResponseData($account);
	}
	/**
	 * 修改当前用户的口令
	 */
	public function changePwd_action() {
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$data = $this->getPostJson();
		$modelAcnt = $this->model('account');
		$account = $modelAcnt->byId($loginUser->id);
		/**
		 * check old password
		 */
		$old_pwd = $data->opwd;
		$result = $modelAcnt->validate($account->email, $old_pwd);
		if ($result->err_code != 0) {
			return $result;
		}
		/**
		 * set new password
		 */
		$new_pwd = $data->npwd;
		$modelAcnt->change_password($account->email, $new_pwd, $account->salt);

		return new \ResponseData($account->uid);
	}
	/**
	 * 修改当前用户的昵称
	 */
	public function changeNickname_action() {
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$data = $this->getPostJson();
		$modelWay = $this->model('site\fe\way');

		$rst = $modelWay->update(
			'account',
			['nickname' => $modelWay->escape($data->nickname)],
			['uid' => $loginUser->id]
		);

		$cookieRegUser = $modelWay->getCookieRegUser();
		if ($cookieRegUser) {
			$cookieRegUser->nickname = $data->nickname;
			$modelWay->setCookieRegUser($cookieRegUser);
		}

		return new \ResponseData($rst);
	}
}