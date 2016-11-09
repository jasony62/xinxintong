<?php
namespace pl\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 平台管理端用户认证
 */
class auth extends \pl\fe\base {
	/**
	 *
	 */
	public function get_access_rule() {
		$ruleAction = [
			'rule_type' => 'black',
		];

		return $ruleAction;
	}
	/**
	 * 进入平台管理页面用户身份验证页面
	 */
	public function index_action() {
		/**
		 * 记录发起登录的页面，登录成功后，跳转到该页面
		 */
		$ruri = $_SERVER['REQUEST_URI'];
		if (!empty($ruri) && !in_array($ruri, array('/'))) {
			$this->mySetCookie('_login_referer', $ruri);
		}
		/**
		 * 跳转到登录页面
		 */
		$path = TMS_APP_API_PREFIX . '/pl/fe/user/login';
		$this->redirect($path);
	}
	/**
	 * 判断当前用户是否已经登录
	 */
	public function isLogin_action() {
		if ($loginUser = $this->accountUser()) {
			return new \ResponseData('Y');
		} else {
			return new \ResponseData('N');
		}
	}
	/**
	 * 验证通过后的回调页面
	 */
	public function passed_action($uid) {
		$modelAct = $this->model('account');
		$fromip = $this->client_ip();
		$modelAct->update_last_login($uid, $fromip);
		/**
		 * record account into session and cookie.
		 */
		$act = $modelAct->byId($uid);
		/**
		 * 记录客户端登陆状态
		 */
		\TMS_CLIENT::account($act);
		/**
		 * 跳转到缺省页
		 */
		if ($ruri = $this->myGetCookie('_login_referer')) {
			$this->mySetCookie('_login_referer', '', time() - 3600);
			$this->redirect($ruri);
		} else {
			$this->redirect(TMS_APP_API_PREFIX . TMS_APP_AUTHED);
		}
	}
}