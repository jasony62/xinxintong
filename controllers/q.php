<?php
/**
 * 快速任务
 */
class q extends TMS_CONTROLLER {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'index';

		return $rule_action;
	}
	/**
	 *
	 */
	public function index_action($code = null) {
		if (empty($code)) {
			TPL::output('quick-entry');
			exit;
		} else {
			$task = $this->model('task')->getTask($code);
			if (false === $task) {
				$this->outputError('任务不存在');
			}
			$this->redirect($task->url);
		}
	}
	/**
	 *
	 */
	protected function outputError($err, $title = '程序错误') {
		TPL::assign('title', $title);
		TPL::assign('body', $err);
		TPL::output('error');
		exit;
	}
}