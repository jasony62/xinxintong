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
		$this->_output($app, $page);
	}
	/*
	 *
	 */
	public function stat_action($app, $rid = '') {
		$page = 'stat';
		$this->_output($app, $page, $rid);
	}
	/*
	 *
	 */
	public function rank_action($app) {
		$page = 'rank';
		$this->_output($app, $page);
	}
	/*
	 *
	 */
	public function votes_action($app) {
		$page = 'votes';
		$this->_output($app, $page);
	}
	/*
	 *
	 */
	public function marks_action($app) {
		$page = 'marks';
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