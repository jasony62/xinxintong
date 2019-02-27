<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 记录活动
 */
class main_base extends base {
	/**
	 *
	 */
	protected $modelApp;
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->modelApp = $this->model('matter\enroll');
	}
	/**
	 *
	 */
	protected function _outputPage($app, $page) {
		$oApp = $this->modelApp->byId($app, ['cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			$this->outputError('指定的记录活动不存在，请检查参数是否正确');
		}
		if (empty($oApp->appRound)) {
			$this->outputError('【' . $oApp->title . '】没有可用的填写轮次，请检查');
		}

		$rst = $this->_getOutput($oApp, $page);
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
	private function _getOutput($oApp, $page){
		// 页面是否存在
		if (in_array($page, ['task', 'kanban', 'event'])) {
			$module = 'activities';
		} else if (in_array($page, ['rank', 'votes', 'marks', 'stat'])) {
			$module = 'summary';
		} else if (in_array($page, ['user', 'favor'])) {
			$module = 'people';
		} else {
			return (object) ['state' => false, 'msg' => '未找到指定页面'];
		}
		// 页面是否开放
		if (!$this->_checkOpenRule($oApp, $page)) {
			return (object) ['state' => false, 'msg' => '页面未开放'];
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
		$outputUrl = '/site/fe/matter/enroll/' . $module;

		$oUser = $this->who;
		$customViewName = TMS_APP_VIEW_NAME;
		if (isset($oUser->unionid)) {
			$oAccount = $this->model('account')->byId($oUser->unionid, ['cascaded' => ['group']]);
			if (isset($oAccount->group->view_name) && $oAccount->group->view_name !== TMS_APP_VIEW_NAME) {
				$customViewName = $oAccount->group->view_name;
			}
		}

		$data = (object) [
			'state' => true,
			'outputTitle' => $oApp->title,
			'outputUrl' => $outputUrl,
			'customViewName' => $customViewName
		];
		return $data;
	}
	/**
	 *
	 */
	private function _checkOpenRule($oApp, $page) {
		switch ($page) {
			case 'kanban':
				if (empty($oApp->scenarioConfig->can_kanban) || $oApp->scenarioConfig->can_kanban !== 'Y') {
					return false;
				}
				break;
			case 'event':
				if (empty($oApp->scenarioConfig->can_action) || $oApp->scenarioConfig->can_action !== 'Y') {
					return false;
				}
				break;
			case 'stat':
				if (empty($oApp->scenarioConfig->can_stat) || $oApp->scenarioConfig->can_stat !== 'Y') {
					return false;
				}
				break;
			case 'rank':
				if (empty($oApp->scenarioConfig->can_rank) || $oApp->scenarioConfig->can_rank !== 'Y') {
					return false;
				}
				break;
		}

		return true;
	}
}