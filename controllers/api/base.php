<?php
namespace api;

class base extends \TMS_CONTROLLER {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/*
	 * 校验token
	 * object $invoke
	 */
	public function checkToken($invoke, $accessToken) {
		if (empty(get_object_vars($invoke)) || empty($accessToken)) {
			return [false, '参数不完整'];
		}

		$userIP = $this->client_ip();
		$rst = $this->model('api\token')->checkToken($invoke->siteid, $accessToken);
		if ($rst[0]) {
			if (!in_array($userIP, $invoke->invokerIps)) {
				$rst = [false, 'ip地址未在白名单中'];
			}
		}

		/* 记录日志 */
		$user = new \stdClass;
		$user->ip = $userIP;
		$this->model('api\log')->add($invoke->siteid, $invoke->id, $accessToken, $rst, $user);

		return $rst;
	}
}