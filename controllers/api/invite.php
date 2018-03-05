<?php
namespace api;

require_once dirname(__FILE__) . '/base.php';
/**
 * 外部系统调用控制器
 */
class invite extends base {
	/*
	 * 通过inviteToken获取访问用户信息
	 * @param string $accessToken 外部系统访问令牌
	 * @param string $inviteToken 用户标识
	 */
	public function user_action($accessToken, $inviteToken) {
		if (empty($accessToken) || empty($inviteToken)) {
			return new \ParameterError('参数不完整');
		}

		//校验accessToken
		$checkRes = $this->checkToken($accessToken);
		if (!$checkRes[0]) {
			return new \ParameterError($checkRes[1]);
		}

		$model = $this->model();
		$q = [
			'il.userid,il.nickname',
			'xxt_invite_access ia,xxt_invite_log il',
			"ia.token = '" . $model->escape($inviteToken) . "' and ia.invite_log_id = il.id"
		];

		$user = $model->query_obj_ss($q);
		
		if ($user === false) {
			return new \ResultEmptyError('用户不存在');
		} else {
			return new \ResponseData($user);
		}
	}
}