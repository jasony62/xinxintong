<?php
namespace pl\fe\matter\ylylisten;

require_once dirname(dirname(__FILE__)) . '/main_base.php';
/*
 * 云录音调听
 */
class main extends \pl\fe\matter\main_base {
	/**
	 *
	 */
	public function index_action() {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 查询用户在能力开放平台的id
		$q = [
			'openid',
			'xxt_account_third_user',
			['unionid' => $oUser->id]
		];
		$thirdUser = $this->model()->query_obj_ss($q);
		if ($thirdUser) {
$thirdUser->openid = '3983f28ad03adb69fdf3-test';
			$url = APP_PROTOCOL . APP_HTTP_HOST . "/ylyfinder/browse.php?lang=zh-cn&type=ylylisten&mpid=" . $thirdUser->openid . "&act=ylylisten";

			\TPL::assign('APP_TITLE', '云录音调听');
			\TPL::assign('ylyfinderURL', $url);
			\TPL::output('/pl/fe/matter/ylylisten/frame');
			die;
		} else {
			// 将返回地址存在cookie中
			$callbackUrl = APP_PROTOCOL . APP_HTTP_HOST . '/rest/pl/fe';
			$this->mySetcookie("_user_access_referer", $callbackUrl);

			echo '<p>仅支持用能力开放平台账号登录的用户查看,请退出后选择"其他方式登录-电信能力开放平台"登录</p><a href="' . APP_PROTOCOL . APP_HTTP_HOST . '/rest/site/fe/user?site=platform">点击跳转</a>';
			die;
		}
	}
}