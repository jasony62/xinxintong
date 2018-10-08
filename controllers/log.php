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
		$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		if (preg_match('/[?&]site=([^&]*)/', $referer, $matches)) {
			$siteid = $matches[1];
		} else {
			$siteid = 'platform';
		}

		$modelWay = $this->model('site\fe\way');
		$this->who = $modelWay->who($siteid);

		$data = $this->getPostJson();

		$agent = $_SERVER['HTTP_USER_AGENT'];

		$src = isset($data->src) ? $data->src : '';
		$msg = isset($data->msg) ? $data->msg : '';

		$this->model('log')->log($this->who->uid, $src, $msg, $agent, $referer);

		return new \ResponseData('ok');
	}
}