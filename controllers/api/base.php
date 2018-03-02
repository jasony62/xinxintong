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
	public function checkToken($accessToken) {
		if (empty($accessToken)) {
			return [false, '参数不完整'];
		}
		if (!is_string($accessToken) || strlen($accessToken) <> 32) {
			return [false, 'accessToken格式错误'];
		}

		$userIP = $this->client_ip();
		$modelToken = $this->model('api\token');

		$rst = $modelToken->checkToken($accessToken);
		if ($rst[0]) {
			$appToken = $rst[1];
			$modelInv = $this->model('site\invoke')->setOnlyWriteDbConn(true);
			if (false === ($invoke = $modelInv->bySecret($appToken->secret))) {
				$rst = [false, '数据错误或者访问令牌已失效'];
			} else {
				if (!in_array($userIP, $invoke->invokerIps)) {
					$rst = [false, 'ip地址未在白名单中'];
				}
			}
		}

		/* 记录日志 */
		$user = new \stdClass;
		$user->ip = $userIP;
		$this->model('api\log')->add($accessToken, $rst, $user);

		return $rst;
	}
}