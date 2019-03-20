<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/main_base.php';
/**
 * 任务控制器
 */
class people extends main_base {
	/*
	 *
	 */
	public function index_action($app) {
		$page = 'favor';
		$this->_outputPage($app, $page);
	}
	/*
	 *
	 */
	public function user_action($app) {
		$page = 'user';
		$this->redirect("/rest/site/fe/user?site={$this->siteId}");
	}
	/*
	 *
	 */
	public function favor_action($app) {
		$page = 'favor';
		$this->_outputPage($app, $page);
	}
}