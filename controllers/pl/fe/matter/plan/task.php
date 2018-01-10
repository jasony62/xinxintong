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
	public function get_action($task) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTsk = $this->model('matter\plan\task');
		$task = $modelTsk->escape($task);

		$oTask = $modelTsk->byId($task, ['fields' => 'id,state,task_schema_id,userid,group_id,nickname,verified,born_at,patch_at,first_enroll_at,last_enroll_at,data,supplement']);
		if (false === $oTask && $oTask->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$modelSchTsk = $this->model('matter\plan\schema\task');
		$oTask->taskSchema = $modelSchTsk->byId($oTask->task_schema_id, ['fields' => 'id,state,title,task_seq,born_mode,born_offset,jump_delayed,can_patch,as_placeholder,auto_verify']);

		return new \ResponseData($oTask);
	}
	/**
	 *
	 */
	public function list_action($app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\plan');
		$app = $modelApp->escape($app);

		$oApp = $modelApp->byId($app, ['fields' => 'id,state']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$modelTsk = $this->model('matter\plan\task');
		$oResult = $modelTsk->byApp($oApp);

		return new \ResponseData($oResult);
	}
	/**
	 * 更新任务
	 */
	public function update_action($task) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTsk = $this->model('matter\plan\task');
		$task = $modelTsk->escape($task);

		$oTask = $modelTsk->byId($task, ['fields' => 'id,state,aid']);
		if (false === $oTask && $oTask->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($oTask->aid, ['fields' => 'id,state,siteid,title,summary,pic']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oPosted = $this->getPostJson();
		$aUpdated = [];
		if (isset($oPosted)) {
			foreach ($oPosted as $prop => $val) {
				switch ($prop) {
				case 'verified':
					if (in_array($val, ['Y', 'N', 'P'])) {
						$aUpdated['verified'] = $val;
					}
					break;
				case 'comment':
					$aUpdated['comment'] = $modelApp->escape($val);
				}
			}
		}

		$rst = 0;
		if (count($aUpdated)) {
			$rst = $modelApp->update('xxt_plan_task', $aUpdated, ['id' => $oTask->id]);
			$this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'updateTask', $oPosted);
		}

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function batchVerify_action($app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\plan');
		$app = $modelApp->escape($app);

		$oApp = $modelApp->byId($app, ['fields' => 'id,state,siteid,title,summary,pic']);
		if (false === $oApp || $oApp->state !== '1') {
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

		$this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'verify.batch', $taskIds);

		return new \ResponseData($updatedCount);
	}
}