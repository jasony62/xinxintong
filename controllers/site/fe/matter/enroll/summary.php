<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/main_base.php';
/**
 * 任务控制器
 */
class summary extends main_base {
	/*
	 *
	 */
	public function index_action($app) {
		$page = 'rank';
		$this->_outputPage($app, $page);
	}
	/*
	 *
	 */
	public function stat_action($app) {
		$page = 'stat';
		$this->_outputPage($app, $page);
	}
	/*
	 *
	 */
	public function rank_action($app) {
		$page = 'rank';
		$this->_outputPage($app, $page);
	}
	/*
	 *
	 */
	public function votes_action($app) {
		$page = 'votes';
		$this->_outputPage($app, $page);
	}
	/*
	 *
	 */
	public function marks_action($app) {
		$page = 'marks';
		$this->_outputPage($app, $page);
	}
}