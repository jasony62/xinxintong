<?php
namespace api;

class base extends \TMS_CONTROLLER {
	/**
	 * 当前访问的站点ID
	 */
	protected $siteId;
	/**
	 * 对请求进行通用的处理
	 */
	public function __construct() {
		if (empty($_GET['site'])) {
			return new \ParameterError('参数不完整');
		}

		$this->siteId = $_GET['site'];
	}
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
		if (empty($accessToken)) {
			return new \ParameterError('参数不完整');
		}

		$rst = $this->model('api\token')->checkToken($this->siteId, $accessToken);
		if ($rst[0]) {
			$userIP = $this->client_ip();
			if (!in_array($userIp, $invoke->invokerIps)) {
				$rst = [false, 'ip地址未在白名单中'];
			}
		}

		/* 记录日志 */
		$this->model('api\log')->add($this->siteId, $invoke->id, $accessToken, $rst);

		return $rst;
	}
}