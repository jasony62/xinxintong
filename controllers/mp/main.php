<?php
namespace mp;

require_once dirname(__FILE__) . "/mp_controller.php";
/**
 *
 */
class main extends mp_controller {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';

		return $rule_action;
	}
	/**
	 *
	 */
	public function index_action() {
		$this->view_action('/mp/main');
	}
	/**
	 *
	 */
	public function recent_action() {
		$this->view_action('/mp/main');
	}
	/**
	 * 列出最近操作的素材
	 */
	public function recentMatters_action() {
		$modelLog = $this->model('log');
		$matters = $modelLog->recentMatters($this->mpid);

		return new \ResponseData($matters);
	}
}