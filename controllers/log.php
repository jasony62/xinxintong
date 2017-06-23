<?php
/**
 * 记录日志
 */
class log extends TMS_CONTROLLER {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'index';

		return $rule_action;
	}
	/**
	 * 通过日志记录
	 */
	public function add_action() {
		$data = $this->getPostJson();

		$agent = $_SERVER['HTTP_USER_AGENT'];
		$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

		if (preg_match('/[?&]site=([^&]*)/', $referer, $matches)) {
			$siteid = $matches[1];
		} else {
			$siteid = '';
		}

		$src = isset($data->src) ? $data->src : '';
		$msg = isset($data->msg) ? $data->msg : '';

		$this->model('log')->log($siteid, $src, $msg, $agent, $referer);

		return new \ResponseData('ok');
	}
}