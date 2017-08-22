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
		/* 站点用户信息 */
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
		if (empty($data->nickname)) {
			return new \ResponseError('新昵称不能为空');
		}

		$user = $this->who;

		/* 更新注册用户信息 */
		$modelWay = $this->model('site\fe\way');
		$cookieRegUser = $modelWay->getCookieRegUser();
		if ($cookieRegUser) {
			$rst = $modelWay->update(
				'account',
				['nickname' => $data->nickname],
				['uid' => $cookieRegUser->unionid]
			);
			$cookieRegUser->nickname = $data->nickname;
			$modelWay->setCookieRegUser($cookieRegUser);
		}

		/* 更新站点用户信息 */
		$modelUsr = $this->model('site\user\account');
		if ($account = $modelUsr->byId($user->uid)) {
			$modelUsr->changeNickname($this->siteId, $account->uid, $data->nickname);
		}
		$cookieUser = $modelWay->getCookieUser($this->siteId);
		$cookieUser->nickname = $data->nickname;
		$modelWay->setCookieUser($this->siteId, $cookieUser);

		return new \ResponseData('ok');
	}
	/**
	 * 修改用户口令
	 * 只有注册用户才能修改
	 */
	public function changePwd_action() {
		$data = $this->getPostJson();
		if (empty($data->password)) {
			return new \ResponseError('新口令不能为空');
		}

		$user = $this->who;

		$modelUsr = $this->model('site\user\account');
		if ($account = $modelUsr->byId($user->uid)) {
			$modelReg = $this->model('site\user\registration');
			if ($registration = $modelReg->byId($account->unionid)) {
				$rst = $modelReg->changePwd($registration->uname, $data->password, $registration->salt);
				return new \ResponseData($rst);
			}
		}

		return new \ResponseError('你不是注册用户，无法修改口令');
	}
	/**
	 * 用户访问过的所有站点
	 */
	public function siteList_action() {
		$modelWay = $this->model('site\fe\way');
		$sites = $modelWay->siteList();

		return new \ResponseData($sites);
	}
}