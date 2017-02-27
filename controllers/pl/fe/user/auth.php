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
		// 登录页面
		$path = TMS_APP_API_PREFIX . '/pl/fe/user/login';
		// 记录发起登录的页面，登录成功后，跳转到该页面
		if (isset($_SERVER['HTTP_REFERER'])) {
			$referer = $_SERVER['HTTP_REFERER'];
			if (!empty($referer) && !in_array($referer, array('/'))) {
				$this->mySetCookie('_login_referer', $referer);
			}
		}
		// 跳转到登录页
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
	 *
	 * 验证通过后的回调页面
	 * 有安全漏洞，只要知道了uid就可以直接登录？？？
	 *
	 * @param string $uid
	 *
	 */
	public function passed_action($uid = null) {
		if ($uid === null) {
			return new \ResponseError('参数错误');
		}

		$modelAct = $this->model('account');
		$act = $modelAct->byId($uid);
		if ($act === false) {
			return new \ResponseError('指定的对象不存在');
		}

		$fromip = $this->client_ip();
		$modelAct->update_last_login($uid, $fromip);

		// 记录客户端登录状态
		\TMS_CLIENT::account($act);

		// 页面跳转
		if ($referer = $this->myGetCookie('_login_referer')) {
			// 跳转到前一页
			$this->mySetCookie('_login_referer', '', time() - 3600);
			$this->redirect($referer);
		} else {
			// 跳转到缺省页
			$this->redirect(TMS_APP_API_PREFIX . TMS_APP_AUTHED);
		}
	}
}