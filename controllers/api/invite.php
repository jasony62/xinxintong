<?php
namespace api;

require_once dirname(__FILE__) . '/base.php';
/**
 * 外部系统调用控制器
 */
class invite extends base {
	/*
	 * 通过inviteToken获取访问用户信息
	 * @param string $site 团队id
	 * @param string $accessToken 外部系统访问令牌
	 * @param string $inviteToken 用户标识
	 */
	public function user_action($accessToken, $inviteToken) {
		if (empty($this->accessToken) || empty($inviteToken)) {
			return new \ParameterError('参数不完整');
		}

		$modelInv = $this->model('site\invoke')->setOnlyWriteDbConn(true);
		if (false === ($invoke = $modelInv->bySite($this->siteId))) {
			return new \ObjectNotFoundError();
		}

		//校验accessToken
		$checkRes = $this->checkToken($invoke, $accessToken);
		if (!$checkRes[0]) {
			return new \ResponseError($checkRes[1]);
		}

		$q = [
			'a.token,l.matter_id,l.matter_type,l.userid,l.lickname,l.use_at,c.code',
			'xxt_invite_access a,xxt_invite_log l,xxt_invite_code c',
			"a.token = '" . $modelInv->escape($inviteToken) . "' and a.invite_log_id = l.id and l.invite_code_id = c.id"
		];

		$user = $modelInv->query_obj_ss($q);

		return new \ResponseData($user);
	}
}