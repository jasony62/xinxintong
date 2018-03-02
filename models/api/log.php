<?php
namespace api;
/**
 * 用户邀请令牌
 */
class log_model extends \TMS_MODEL {
	/*
	 * token使用日志
	 */
	public function add($accessToken, $data, $user) {
		$log = new \stdClass;
		$log->create_at = time();
		$log->access_token = $accessToken;
		$log->access_status = $this->toJson($data);
		$log->user_agent = isset($_SERVER['HTTP_USER_AGENT'])? $_SERVER['HTTP_USER_AGENT'] : '';
		$log->user_ip = $user->ip;

		$log->id = $this->insert('xxt_site_invoke_log', $log, true);

		return $log;
	}
}