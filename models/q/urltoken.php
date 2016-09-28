<?php
namespace q;
/**
 * 短地址任务链接
 */
class urltoken_model extends \TMS_MODEL {
	/**
	 * 生成任务
	 *
	 * @param string $siteId
	 * @param string $userId
	 * @param string $url
	 *
	 * @return string code
	 */
	public function add($code, $userAgent, $expire) {
		$current = time();
		$accessToken = md5($code . uniqid() . $userAgent);

		$item = [
			'code' => $code,
			'access_token' => $accessToken,
			'create_at' => $current,
			'expire_at' => $current + $expire,
			'user_agent' => $userAgent,
		];

		$this->insert('xxt_short_url_token', $item, false);

		return $accessToken;
	}
	/**
	 * 检查令牌是否有效
	 */
	public function checkAccessToken($accessToken) {
		$q = [
			'expire_at',
			'xxt_short_url_token',
			["access_token" => $accessToken],
		];
		if ($token = $this->query_obj_ss($q)) {
			if ($token->expire_at >= time()) {
				return true;
			}
			$this->delete('xxt_short_url_token', ["access_token" => $accessToken]);
		}

		return false;
	}
}