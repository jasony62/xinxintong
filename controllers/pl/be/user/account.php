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
	 * 修改当前用户的口令
	 */
	public function resetPwd_action() {
		$data = $this->getPostJson();
		$modelAcnt = $this->model('account');
		$account = $modelAcnt->byId($data->uid);
		/**
		 * set new password
		 */
		$pwd = $data->password;
		$modelAcnt->change_password($account->email, $pwd, $account->salt);

		return new \ResponseData($account->uid);
	}
}