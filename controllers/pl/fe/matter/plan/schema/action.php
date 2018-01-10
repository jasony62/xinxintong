<?php
namespace pl\fe\matter\plan\schema;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/*
 * 计划任务活动主控制器
 */
class action extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function get_action($action) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelAct = $this->model('matter\plan\schema\action');
		$oAction = $modelAct->byId($action, ['fields' => 'id,action_desc,action_seq,check_schemas']);

		return new \ResponseData($oAction);
	}

	/**
	 *
	 */
	public function add_action($task) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$oTask = $this->model('matter\plan\schema\task')->byId($task);
		if (false === $oTask) {
			return new \ObjectNotFoundError();
		}

		$oProto = new \stdClass;
		$oProto->aid = $oTask->aid;
		$oProto->siteid = $oTask->siteid;
		$oProto->task_schema_id = $oTask->id;

		$oNewAction = $this->model('matter\plan\schema\action')->add($oProto);

		return new \ResponseData($oNewAction);
	}
	/**
	 *
	 */
	public function list_action($task) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelAct = $this->model('matter\plan\schema\action');
		$oActions = $modelAct->byTask($task, ['fields' => 'id,action_desc,action_seq,check_schemas']);

		return new \ResponseData($oActions);
	}
	/**
	 *
	 */
	public function move_action($action, $step) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$step = (int) $step;
		if (empty($step)) {
			return new \ParameterError();
		}

		$modelAct = $this->model('matter\plan\schema\action');
		$oAction = $modelAct->byId($action, ['fields' => 'id,task_schema_id,action_seq,state']);
		if (false === $oAction || $oAction->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$newSeq = $oAction->action_seq + $step;
		if ($step > 0) {
			$lastSeq = $modelAct->lastSeq($oAction->task_schema_id);
			if ($lastSeq == $oAction->action_seq) {
				return new \ResponseData($lastSeq);
			}
			if ($newSeq > $lastSeq) {
				return new \ParameterError('移动位置【' . $newSeq . '】超出范围');
			}
			// 调整其他任务的序号
			$modelAct->update('update xxt_plan_action_schema set action_seq=action_seq-1 where state=1 and action_seq>' . $oAction->action_seq . ' and action_seq<=' . $newSeq);
		} else {
			if ($newSeq < 1) {
				return new \ParameterError('移动位置【' . $newSeq . '】超出范围');
			}
			// 调整其他任务的序号
			$modelAct->update('update xxt_plan_action_schema set action_seq=action_seq+1 where state=1 and action_seq>=' . $newSeq . ' and action_seq<' . $oAction->action_seq);
		}

		$modelAct->update('xxt_plan_action_schema', ['action_seq' => $newSeq], ['id' => $oAction->id]);
		$oAction->action_seq = $newSeq;

		return new \ResponseData($oAction);
	}
	/**
	 *
	 */
	public function update_action($action) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelAct = $this->model('matter\plan\schema\action');
		$oAction = $modelAct->byId($action, ['fields' => 'id,state']);
		if (false === $oAction || $oAction->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oPosted = $this->getPostJson();
		/* 处理数据 */
		$aUpdated = [];
		foreach ($oPosted as $prop => $val) {
			switch ($prop) {
			case 'action_desc':
				$aUpdated['action_desc'] = $modelAct->escape($val);
				break;
			case 'checkSchemas':
				$aUpdated['check_schemas'] = $modelAct->escape($modelAct->toJson($val));
				break;
			}
		}
		if (!empty($aUpdated)) {
			$rst = $modelAct->update('xxt_plan_action_schema', $aUpdated, ['id' => $oAction->id]);
		} else {
			$rst = 0;
		}

		return new \ResponseData($rst);
	}

	/**
	 *
	 */
	public function remove_action($action) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelAct = $this->model('matter\plan\schema\action');
		$oAction = $modelAct->byId($action, ['fields' => 'id,action_seq,state']);
		if (false === $oAction || $oAction->state !== '1') {
			return new \ObjectNotFoundError();
		}

		// 删除任务
		$modelAct->update('xxt_plan_action_schema', ['state' => 0], ['id' => $oAction->id]);

		// 调整其他任务的序号
		$modelAct->update('update xxt_plan_action_schema set action_seq=action_seq-1 where state=1 and action_seq>' . $oAction->action_seq);

		return new \ResponseData($oAction);
	}
}