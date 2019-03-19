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
		$this->_outputPage($app, $page);
	}
	/*
	 *
	 */
	public function task_action($app) {
		$page = 'task';
		$this->_outputPage($app, $page);
	}
	/*
	 *
	 */
	public function kanban_action($app) {
		$page = 'kanban';
		$this->_outputPage($app, $page);
	}
	/*
	 *
	 */
	public function event_action($app) {
		$page = 'event';
		$this->_outputPage($app, $page);
	}
}