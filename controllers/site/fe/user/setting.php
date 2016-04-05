<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 站点用户
 */
class setting extends \site\fe\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'] = array();
		$rule_action['actions'][] = 'index';
		$rule_action['actions'][] = 'update';

		return $rule_action;
	}
	/**
	 *
	 */
	public function index_action($code = null, $mocker = null) {
		\TPL::output('/site/fe/user/setting');
		exit;
	}
	/**
	 * update an account.
	 *
	 * @param string $siteid
	 */
	public function update_action() {
		$data = $this->getPostJson();

		$uid = $this->who->uid;

		$this->model()->update(
			'xxt_site_account',
			$data,
			"siteid='$this->siteId' and uid='$uid'"
		);

		if (isset($data->nickname)) {
			/*更新cookie状态*/
			/*user*/
			$modelWay = $this->model('site\fe\way');
			$cookieUser = $modelWay->getCookieUser($this->siteId);
			$cookieUser->nickname = $data->nickname;
			$cookieUser->loginExpire = time() + (86400 * TMS_COOKIE_SITE_LOGIN_EXPIRE);
			$modelWay->setCookieUser($this->siteId, $cookieUser);
			/*login*/
			$modelWay->setCookieLogin($this->siteId, $cookieUser);
		}

		return new \ResponseData('ok');
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