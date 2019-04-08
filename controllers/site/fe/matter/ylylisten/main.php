<?php
namespace site\fe\matter\ylylisten;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 用户邀请
 */
class main extends \site\fe\matter\base {
	/**
	 *
	 */
	public function index_action() {
		$oUser = $this->who;
		$checkRegister = $this->checkRegisterEntryRule($oUser);
		if ($checkRegister[0] === false) {
			$callbackUrl = $this->getRequestUrl();
			// 将返回地址存在cookie中
			$this->mySetcookie("_thirdlogin_oauth_callbackURL", $callbackUrl);
			// $authUrl = 'http://' . APP_HTTP_HOST . '/rest/site/fe/user/access?site=platform';
			$authUrl = 'http://' . APP_HTTP_HOST . '/rest/site/fe/user/login/byRegAndThird?thirdId=2';
			$this->redirect($authUrl);
		}

		// 查询用户在能力开放平台的
		$q = [
			'openid',
			'xxt_account_third_user',
			['unionid' => $oUser->unionid]
		];
		$thirdUser = $this->model()->query_obj_ss($q);

		// 返回前端页面，跳转到什么页面由前端控制
		if ($thirdUser) {
$thirdUser->openid = '3983f28ad03adb69fdf3-test';
			$url = APP_PROTOCOL . APP_HTTP_HOST . "/kcfinder/browse.php?lang=zh-cn&type=ylylisten&mpid=" . $thirdUser->openid . "&act=ylylisten";
			$this->redirect($url);
		} else {
			echo '<p>仅支持用能力开放平台账号登录的用户查看,请退出后选择"其他方式登录-电信能力开放平台"登录</p><a href="http://' . APP_HTTP_HOST . '/rest/site/fe/user?site=platform">点击跳转</a>';
			die;
		}
	}
}