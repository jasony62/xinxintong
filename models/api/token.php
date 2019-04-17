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
		$invTokens = $this->query_objs_ss($q);
		$count = count($invTokens);
		if ($count > 1) { // access_token数据异常存在多条数据，需要清理，并重新生成
			foreach ($invTokens as $invToken) {
				$this->update('xxt_site_invoke_token', ['state' => 0], ['id' => $invToken->id]);
			}
			$invToken = $this->createToken($secret, $invoke);

			/* 记录日志 */
			$method = 'access_token数据异常存在多条数据';
			$messge = json_encode($invTokens);
			$agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
			$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
			$this->model('log')->log('error', $method, $messge, $agent, $referer);
		} else if ($count === 0) {
			//没有就创建
			$invToken = $this->createToken($secret, $invoke);
		} else {
			$invToken = $invTokens[0];
			// 如果token过期，需要重新创建
			$current = time();
			if ($invToken->expire_at <= $current) {
				$this->update('xxt_site_invoke_token', ['state' => 0], ['id' => $invToken->id]);
				$invToken = $this->createToken($secret, $invoke);
			} else {
				$invToken->expires_in = (int) $invToken->expire_at - $current;
			}
		}

		empty($invToken->expires_in) && $invToken->expires_in = $this->expires_in;
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
		$data->user_agent = tms_get_server('HTTP_USER_AGENT') ? tms_get_server('HTTP_USER_AGENT') : '';

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
			return [false, '访问令牌未找到'];
		} else {
			if ($token->expire_at < time()) {
				return [false, '访问令牌已经超过有效期'];
			}
		}

		return [true, $token];
	}
}