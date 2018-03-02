<?php
namespace api;
/**
 * 用户邀请令牌
 */
class log_model extends \TMS_MODEL {
	/*
	 * token使用日志
	 */
	public function add($site, $invokeId, $accessToken, $data, $user) {
		$log = new \stdClass;
		$log->siteid = $site;
		$log->invoke_id = $invokeId;
		$log->create_at = time();
		$log->access_token = $accessToken;
		$log->access_status = json_encode($data);
		$log->user_agent = isset($_SERVER['HTTP_USER_AGENT'])? $_SERVER['HTTP_USER_AGENT'] : '';
		$log->user_ip = $user->ip;

		$log->id = $this->insert('xxt_site_invoke_log', $log, true);

		return $log;
	}
}