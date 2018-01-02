<?php
namespace pl\fe\matter\plan;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 计划任务活动主控制器
 */
class task extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/plan/frame');
		exit;
	}
	/**
	 *
	 */
	public function list_action($app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oApp = $this->model('matter\plan')->byId($app);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$modelTsk = $this->model('matter\plan\task');
		$oResult = $modelTsk->byApp($oApp);

		return new \ResponseData($oResult);
	}
	/**
	 *
	 */
	public function batchVerify_action($app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oApp = $this->model('matter\plan')->byId($app);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$updatedCount = 0;
		$taskIds = $this->getPostJson();
		if (!empty($taskIds)) {
			$modelTsk = $this->model('matter\plan\task');
			foreach ($taskIds as $taskId) {
				$rst = $modelTsk->update('xxt_plan_task', ['verified' => 'Y'], ['aid' => $oApp->id, 'id' => $taskId]);
				if ($rst === 1) {
					$updatedCount++;
				}
			}
		}

		return new \ResponseData($updatedCount);
	}
}