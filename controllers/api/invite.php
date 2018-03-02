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

		$q = [
			'a.token,l.matter_id,l.matter_type,l.userid,l.nickname,l.use_at',
			'xxt_invite_access a,xxt_invite_log l',
			"a.token = '" . $modelInv->escape($inviteToken) . "' and a.invite_log_id = l.id"
		];

		$user = $modelInv->query_obj_ss($q);

		return new \ResponseData($user);
	}
}