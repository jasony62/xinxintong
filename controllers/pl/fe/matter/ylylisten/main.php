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

		$model = $this->model('account');
		// 查询用户在能力开放平台的id
		$q = [
			'openid',
			'xxt_account_third_user',
			['unionid' => $oUser->id]
		];
		$thirdUser = $model->query_obj_ss($q);
		// 仅限能力平台第三方登录用户
		if ($thirdUser === false) {
			$callbackUrl = $this->getRequestUrl();
			$this->mySetcookie("_user_access_referer", $callbackUrl);
			echo '<div align="center"> <p>仅支持用能力开放平台账号登录的用户查看, 请退出登录后选择"其他方式登录-电信能力开放平台"登录</p><a href="' . APP_PROTOCOL . APP_HTTP_HOST . '/rest/site/fe/user?site=platform">点击跳转</a> <div/>';
			die;
		}

		// 查询用户所在组
		$group = $model->getGroupByUser($oUser->id);
		if ($group === false) {
			die('缺少用户组');
		}
		if ($group->p_dev189_service != 1) {
			die('当前用户组没有此权限');
		}

		\TPL::output('/pl/fe/matter/ylylisten/frame');
		die;
	}
	/**
	 *
	 */
	public function get_action($site) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model('account');
		// 查询用户在能力开放平台的id
		$q = [
			'openid',
			'xxt_account_third_user',
			['unionid' => $oUser->id]
		];
		$thirdUser = $this->model()->query_obj_ss($q);
		if ($thirdUser === false) {
			return new \ResponseError('仅支持第三方登录用户');
		}

		// 查询用户所在组
		$group = $model->getGroupByUser($oUser->id);
		if ($group === false) {
			return new \ResponseError('缺少用户组');
		}
		if ($group->p_dev189_service != 1) {
			return new \ResponseError('用户没有此权限');
		}

		$data = new \stdClass;
		// 测试openid
		$thirdUser->openid = '3983f28ad03adb69fdf3-test';

		$url = APP_PROTOCOL . APP_HTTP_HOST . "/ylyfinder/browse.php?lang=zh-cn&type=tyyoos&mpid=" . $thirdUser->openid;
		$data->entryUrl = $url;

		return new \ResponseData($data);
	}
}