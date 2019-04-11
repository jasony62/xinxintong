<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/main_base.php';
/**
 * 任务控制器
 */
class activities extends main_base {
	/*
	 *
	 */
	public function index_action($app) {
		$page = 'task';
		$this->_output($app, $page);
	}
	/*
	 *
	 */
	public function task_action($app) {
		$page = 'task';
		$this->_output($app, $page);
	}
	/*
	 *
	 */
	public function kanban_action($app) {
		$page = 'kanban';
		$this->_output($app, $page);
	}
	/*
	 *
	 */
	public function event_action($app) {
		$page = 'event';
		$this->_output($app, $page);
	}
	/**
	 *
	 */
	private function _output($app, $page) {
		$oApp = $this->modelApp->byId($app, ['cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			$this->outputError('指定的记录活动不存在，请检查参数是否正确');
		}

		/* 检查是否需要第三方社交帐号OAuth */
		if (!$this->afterSnsOAuth()) {
			$this->requireSnsOAuth($oApp);
		}

		$this->_outputPage($oApp, $page);
	}
}