<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 任务控制器
 */
class activities extends base {
	/**
	 *
	 */
	private $modelApp;
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->modelApp = $this->model('matter\enroll');
	}
	/*
	 *
	 */
	public function index_action($app) {
		$page = 'task';

		$rst = $this->_getOutput($app, $page);
		if (!$rst->state) {
			$this->outputError($rst->msg);
		}

		\TPL::assign('title', $rst->outputTitle);
		\TPL::output($rst->outputUrl, ['customViewName' => $rst->customViewName]);
		exit;
	}
	/*
	 *
	 */
	public function task_action($app) {
		$page = 'task';

		$rst = $this->_getOutput($app, $page);
		if (!$rst->state) {
			$this->outputError($rst->msg);
		}

		\TPL::assign('title', $rst->outputTitle);
		\TPL::output($rst->outputUrl, ['customViewName' => $rst->customViewName]);
		exit;
	}
	/*
	 *
	 */
	public function kanban_action($app) {
		$page = 'kanban';

		$rst = $this->_getOutput($app, $page);
		if (!$rst->state) {
			$this->outputError($rst->msg);
		}

		\TPL::assign('title', $rst->outputTitle);
		\TPL::output($rst->outputUrl, ['customViewName' => $rst->customViewName]);
		exit;
	}
	/*
	 *
	 */
	public function event_action($app) {
		$page = 'event';

		$rst = $this->_getOutput($app, $page);
		if (!$rst->state) {
			$this->outputError($rst->msg);
		}

		\TPL::assign('title', $rst->outputTitle);
		\TPL::output($rst->outputUrl, ['customViewName' => $rst->customViewName]);
		exit;
	}
	/**
	 *
	 */
	private function _getOutput($app, $page){
		$oApp = $this->modelApp->byId($app, ['cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			return (object) ['state' => false, 'msg' => '指定的记录活动不存在，请检查参数是否正确'];
		}

		if (empty($oApp->appRound)) {
			return (object) ['state' => false, 'msg' => '【' . $oApp->title . '】没有可用的填写轮次，请检查'];
		}

		/* 检查是否需要第三方社交帐号OAuth */
		if (!$this->afterSnsOAuth()) {
			$this->requireSnsOAuth($oApp);
		}

		$bSkipEntryCheck = false;
		if (!empty($oApp->entryRule->exclude)) {
			if (in_array($page, $oApp->entryRule->exclude)) {
				$bSkipEntryCheck = true;
			}
		}
		// 检查进入活动规则
		if (!$bSkipEntryCheck) {
			$this->checkEntryRule($oApp, true);
		}

		/* 返回记录活动页面 */
		$outputTitle = $oApp->title;
		$outputUrl = '/site/fe/matter/enroll/activities/' . $page;
		$customViewName = TMS_APP_VIEW_NAME;

		$oUser = $this->who;
		if (isset($oUser->unionid)) {
			$oAccount = $this->model('account')->byId($oUser->unionid, ['cascaded' => ['group']]);
			if (isset($oAccount->group->view_name) && $oAccount->group->view_name !== TMS_APP_VIEW_NAME) {
				$customViewName = $oAccount->group->view_name;
			}
		}

		$data = (object) [
			'state' => true,
			'outputTitle' => $outputTitle,
			'outputUrl' => $outputUrl,
			'customViewName' => $customViewName
		];
		return $data;
	}
}