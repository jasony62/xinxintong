<?php
namespace pl\fe\matter\plan\schema;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/*
 * 计划任务活动主控制器
 */
class task extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function add_action($plan) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$oPlan = $this->model('matter\plan')->byId($plan);
		if (false === $oPlan) {
			return new \ObjectNotFoundError();
		}

		$oProto = new \stdClass;
		$oProto->aid = $oPlan->id;
		$oProto->siteid = $oPlan->siteid;

		$oNewTask = $this->model('matter\plan\schema\task')->add($oProto);

		return new \ResponseData($oNewTask);
	}
	/**
	 *
	 */
	public function copy_action($task) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$task = $this->escape($task);
		$modelTsk = $this->model('matter\plan\schema\task');

		$oCopyedTask = $modelTsk->byId($task, ['fields' => 'id,state,aid,siteid,title,task_seq,born_mode,born_offset,as_placeholder,jump_delayed,auto_verify,can_patch']);
		if (false === $oCopyedTask || $oCopyedTask->state !== '1') {
			return new \ObjectNotFoundError();
		}

		/* 复制任务 */
		$oNewTask = clone $oCopyedTask;
		$oNewTask->title = $oCopyedTask->title . '(1)';
		$oNewTask->task_seq = $modelTsk->lastSeq($oNewTask->aid) + 1;
		$oNewTask->state = 1;
		unset($oNewTask->id);
		unset($oNewTask->actions);
		$oNewTask->id = $modelTsk->insert('xxt_plan_task_schema', $oNewTask, true);

		/* 复制行动 */
		$modelAct = $this->model('matter\plan\schema\action');
		foreach ($oCopyedTask->actions as $oCopyedAction) {
			$oNewAction = new \stdClass;
			$oNewAction->aid = $oNewTask->aid;
			$oNewAction->siteid = $oNewTask->siteid;
			$oNewAction->task_schema_id = $oNewTask->id;
			$modelAct->add($oNewAction, $oCopyedAction);
		}

		/* 移动顺序 */
		if ($oNewTask->task_seq - $oCopyedTask->task_seq > 1) {
			$modelTsk->moveSeq($oNewTask, $oCopyedTask->task_seq - $oNewTask->task_seq + 1);
		}

		return new \ResponseData($oNewTask);
	}
	/**
	 *
	 */
	public function update_action($task) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$task = $this->escape($task);
		$modelTsk = $this->model('matter\plan\schema\task');

		$oTask = $modelTsk->byId($task, ['fields' => 'id,state']);
		if (false === $oTask || $oTask->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oPosted = $this->getPostJson();
		$aUpdate = [];
		foreach ($oPosted as $prop => $val) {
			switch ($prop) {
			case 'title':
				$aUpdate[$prop] = $modelTsk->escape($val);
				break;
			default:
				$aUpdate[$prop] = $modelTsk->escape($val);
			}
		}

		if (!empty($aUpdate)) {
			$rst = $modelTsk->update('xxt_plan_task_schema', $aUpdate, ['id' => $oTask->id]);
		} else {
			$rst = 0;
		}

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function list_action($plan) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$oPlan = $this->model('matter\plan')->byId($plan);
		if (false === $oPlan) {
			return new \ObjectNotFoundError();
		}

		$tasks = $this->model('matter\plan\schema\task')->byApp($oPlan->id, ['fields' => 'id,title,task_seq,born_mode,born_offset,jump_delayed,auto_verify,can_patch,as_placeholder']);

		return new \ResponseData(['tasks' => $tasks]);
	}
	/**
	 *
	 */
	public function move_action($task, $step) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$step = (int) $step;
		if (empty($step)) {
			return new \ParameterError();
		}

		$modelTsk = $this->model('matter\plan\schema\task');
		$oTask = $modelTsk->byId($task, ['fields' => 'id,aid,task_seq,state']);
		if (false === $oTask || $oTask->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$modelTsk->moveSeq($oTask, $step);

		return new \ResponseData($oTask);
	}
	/**
	 *
	 */
	public function remove_action($task) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTsk = $this->model('matter\plan\schema\task');
		$oTask = $modelTsk->byId($task, ['fields' => 'id,task_seq,state']);
		if (false === $oTask || $oTask->state !== '1') {
			return new \ObjectNotFoundError();
		}

		// 删除任务
		$modelTsk->update('xxt_plan_task_schema', ['state' => 0], ['id' => $oTask->id]);

		// 调整其他任务的序号
		$modelTsk->update('update xxt_plan_task_schema set task_seq=task_seq-1 where state=1 and task_seq>' . $oTask->task_seq);

		return new \ResponseData($oTask);
	}
}