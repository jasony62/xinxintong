<?php
namespace api;

require_once dirname(__FILE__) . '/base.php';
/**
 * 外部系统调用控制器
 */
class main extends base {
	/*
	 * 通过站点id和sitesecret获取access_token
	 * @param string $site 团队id
	 * @param string $secret 外部系统调用凭证
	 */
	public function token_action($site, $secret) {
		if (empty($site) || empty($secret)) {
			return new \ParameterError('参数不完整');
		}

		$modelInv = $this->model('site\invoke')->setOnlyWriteDbConn(true);
		if (false === ($invoke = $modelInv->bySite($site))) {
			return new \ObjectNotFoundError();
		}
		if ($secret !== $invoke->secret) {
			return new \ParameterError('siteSecret参数错误');
		}
		$userIp = $this->client_ip();
		if (!in_array($userIp, $invoke->invokerIps)) {
			return new \ParameterError('请从指定ip地址访问');
		}

		$modelInvToken = $this->model('api\token');
		$token = $modelInvToken->token($secret, $invoke);
		
		$data = new \stdClass;
		$data->access_token = $token->access_token;
		$data->expires_in = $token->expires_in;
		return new \ResponseData($data);
	}
}