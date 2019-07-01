<?php
namespace pl\be\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 平台注册用户
 */
class account extends \pl\be\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/be/user/main');
		exit;
	}
	/**
	 * 返回现有注册用户的列表
	 */
	public function list_action($page, $size) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$oFilter = $this->getPostJson();
		$rst = $this->model('account')->getAccount($page, $size, $oFilter);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function changeGroup_action($uid) {
		$posted = $this->getPostJson();

		$gid = $posted->gid;

		$ret = $this->model()->update(
			'account_in_group',
			['group_id' => $gid],
			["account_uid" => $uid]
		);

		return new \ResponseData($ret);
	}
	/**
	 *
	 */
	public function remove_action($uid) {
		$rst = $this->model('account')->remove($uid);

		if ($rst) {
			return new \ResponseData('success');
		} else {
			return new \ResponseError('fail');
		}
	}
	/**
	 * 生成随机密码
	 */
	public function getRandomPwd_action() {
		$pwd = tms_pwd_create_random();

		return new \ResponseData($pwd);
	}
	/**
	 * 修改当前用户的口令
	 */
	public function resetPwd_action() {
		$data = $this->getPostJson(false);
		$modelAcnt = $this->model('account');
		$account = $modelAcnt->byId($data->uid);
		/**
		 * set new password
		 */
		$pwd = $data->password;
		$rst = tms_pwd_check($pwd, ['account' => $account->email]);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}
		$email = $this->escape($account->email);
		$modelAcnt->change_password($email, $pwd, $account->salt);

		return new \ResponseData($account->uid);
	}
	/**
	 * 禁用站点用户注册帐号
	 */
	public function forbide_action() {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$user = $this->getPostJson();

		$this->model()->update('account', ['forbidden' => '1'], ['uid' => $user->uid]);

		return new \ResponseData('ok');
	}
	/**
	 * 激活被禁用的站点用户注册帐号
	 */
	public function active_action() {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$user = $this->getPostJson();

		$this->model()->update('account', ['forbidden' => '0'], ['uid' => $user->uid]);

		return new \ResponseData('ok');
	}
}