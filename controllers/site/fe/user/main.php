<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 站点用户
 */
class main extends \site\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/site/fe/user/main');
		exit;
	}
	/**
	 *
	 */
	public function get_action() {
		if ($account = $this->model('site\user\account')->byId($this->who->uid, ['fields' => 'coin,headimgurl'])) {
			$this->who->coin = $account->coin;
			$this->who->headimgurl = $account->headimgurl;
		}

		return new \ResponseData($this->who);
	}
	/**
	 * 修改用户昵称
	 * 只有注册过用户才能修改？？？
	 */
	public function changeNickname_action() {
		$data = $this->getPostJson();
		$user = $this->who;

		$modelUsr = $this->model('site\user\account');
		$user = $modelUsr->byId($user->uid);
		if (empty($user->salt)) {
			return new \ResponseError('你不是注册用户，无法修改昵称');
		}

		$rst = $modelUsr->changeNickname($this->siteId, $user->uname, $data->nickname);

		/*缓存用户信息*/
		$modelWay = $this->model('site\fe\way');
		$cookieUser = $modelWay->getCookieUser($this->siteId);
		$cookieUser->nickname = $data->nickname;
		$modelWay->setCookieUser($this->siteId, $cookieUser);

		return new \ResponseData($rst);
	}
	/**
	 * 修改用户口令
	 * 只有注册用户才能修改
	 */
	public function changePwd_action() {
		$data = $this->getPostJson();
		$user = $this->who;

		$modelUsr = $this->model('site\user\account');
		$user = $modelUsr->byId($user->uid);
		if (empty($user->salt)) {
			return new \ResponseError('你不是注册用户，无法修改口令');
		}

		$rst = $modelUsr->changePwd($this->siteId, $user->uname, $data->password, $user->salt);

		return new \ResponseData($rst);
	}
	/**
	 * 用户访问过的所有站点
	 */
	public function siteList_action() {
		$sites = [];
		foreach ($_COOKIE as $key => $val) {
			if (preg_match('/xxt_site_(.*?)_fe_user/', $key, $matches)) {
				$sites[] = $matches[1];
			}
		}

		return new \ResponseData($sites);
	}
}