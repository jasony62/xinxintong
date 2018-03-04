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
			return new \ResponseError($checkRes[1]);
		}

		$model = $this->model();
		$q = [
			'ia.userid,sa.nickname',
			'xxt_invite_access ia,xxt_site_account sa',
			"ia.token = '" . $model->escape($inviteToken) . "' and ia.userid = sa.uid"
		];

		$user = $model->query_obj_ss($q);

		return new \ResponseData($user);
	}
}