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
		return new \ResponseData($this->who);
	}
	/**
	 *
	 */
	public function changeNickname_action() {
		$data = $this->getPostJson();
		$user = $this->who;

		$modelUsr = $this->model('site\user\account');
		$user = $modelUsr->byId($user->uid);
		if (empty($user->salt)) {
			return new \ResponseError('用户没有设置过口令，不允许重置口令');
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
	 *
	 */
	public function changePwd_action() {
		$data = $this->getPostJson();
		$user = $this->who;

		$modelUsr = $this->model('site\user\account');
		$user = $modelUsr->byId($user->uid);
		if (empty($user->salt)) {
			return new \ResponseError('用户没有设置过口令，不允许重置口令');
		}

		$rst = $modelUsr->changePwd($this->siteId, $user->uname, $data->password, $user->salt);

		return new \ResponseData($rst);
	}
}