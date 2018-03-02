<?php
namespace api;
/**
 * 用户邀请令牌
 */
class token_model extends \TMS_MODEL {
	/*
	 * 访问令牌有效时间
	 */
	private $expires_in = 7200;
	/*
	 * 获取access_token
	 * object $invoke
	 */
	public function token($secret, &$invoke) {
		$this->setOnlyWriteDbConn(true);
		$q = [
			'*',
			'xxt_site_invoke_token',
			['siteid' => $invoke->siteid, 'secret' => $secret, 'state' => 1],
		];
		$invToken = $this->query_obj_ss($q);
		if ($invToken === false) {
			//没有就创建
			$invToken = $this->createToken($secret, $invoke);
		} else {
			// 如果token过期，需要重新创建
			if ($invToken->expire_at < time()) {
				$this->update('xxt_site_invoke_token', ['state' => 0], ['id' => $invToken->id]);
				$invToken = $this->createToken($secret, $invoke);
			}
		}

		$invToken->expires_in = $this->expires_in;
		return $invToken;
	}
	/*
	 * 创建sccess_token
	 */
	private function createToken($secret, $invoke) {
		$this->setOnlyWriteDbConn(true);
		$current = time();
		$token = md5($invoke->siteid . $secret . $current);
		
		$data = new \stdClass;
		$data->siteid = $invoke->siteid;
		$data->invoke_id = $invoke->id;
		$data->secret = $secret;
		$data->access_token = $token;
		$data->create_at = $current;
		$data->expire_at = $current + $this->expires_in;
		$data->user_agent = isset($_SERVER['HTTP_USER_AGENT'])? $_SERVER['HTTP_USER_AGENT'] : '';

		$data->id = $this->insert('xxt_site_invoke_token', $data, true);

		return $data;
	}
	/*
	 * 验证access_token
	 */
	public function checkToken($accessToken) {
		$this->setOnlyWriteDbConn(true);
		$q = [
			'*',
			'xxt_site_invoke_token',
			['access_token' => $accessToken, 'state' => 1],
		];
		$token = $this->query_obj_ss($q);
		if ($token === false) {
			return [false, '邀请验证令牌未找到'];
		} else {
			if ($token->expire_at < time()) {
				return [false, '邀请验证令牌已经超过有效期'];
			}
		}

		return [true, $token];
	}
}