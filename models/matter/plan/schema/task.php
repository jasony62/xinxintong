<?php
namespace matter\plan\schema;
/**
 *
 */
class task_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function byId($taskId, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];
		$q = [
			$fields,
			'xxt_plan_task_schema',
			['id' => $taskId],
		];
		$oTask = $this->query_obj_ss($q);
		if ($oTask) {
			$modelSchAct = $this->model('matter\plan\schema\action');
			$actions = $modelSchAct->byTask($oTask->id, ['fields' => 'id,action_desc,action_seq,check_schemas']);
			$oTask->actions = $actions;
		}

		return $oTask;
	}
	/**
	 *
	 */
	public function bySeq($oApp, $seq, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];
		$cascaded = empty($aOptions['cascaded']) ? 'Y' : $aOptions['cascaded'];
		$q = [
			$fields,
			'xxt_plan_task_schema',
			['aid' => $oApp->id, 'task_seq' => $seq, 'state' => 1],
		];
		$oTask = $this->query_obj_ss($q);
		if ($oTask) {
			if ($cascaded === 'Y') {
				$modelSchAct = $this->model('matter\plan\schema\action');
				$actions = $modelSchAct->byTask($oTask->id, ['fields' => 'id,action_desc,action_seq,check_schemas']);
				foreach ($actions as $oAction) {
					$oAction->checkSchemas = empty($oAction->check_schemas) ? [] : json_decode($oAction->check_schemas);
					unset($oAction->check_schemas);
				}
				$oTask->actions = $actions;
			}
		}

		return $oTask;
	}
	/**
	 * 添加1个模板任务
	 */
	public function add($oNewTask) {
		$oNewTask->state = 1;
		$oNewTask->task_seq = $this->lastSeq($oNewTask->aid) + 1;
		$oNewTask->title = '任务-' . $oNewTask->task_seq;
		if ($oNewTask->task_seq === 1) {
			$oNewTask->born_mode = 'U'; // 用户首次执行生成
			$oNewTask->born_offset = ''; // 0点开始
		} else {
			$oNewTask->born_mode = 'P'; // 上一个任务之后
			$oNewTask->born_offset = 'P1D'; // 1天
		}

		$oNewTask->jump_delayed = 'U';
		$oNewTask->auto_verify = 'U';

		$oNewTask->id = $this->insert('xxt_plan_task_schema', $oNewTask, true);

		return $oNewTask;
	}
	/**
	 * 模板任务的最大序号
	 */
	public function lastSeq($planId) {
		$q = [
			'max(task_seq)',
			'xxt_plan_task_schema',
			['aid' => $planId, 'state' => 1],
		];
		$lastSeq = (int) $this->query_val_ss($q);

		return $lastSeq;
	}
	/**
	 *
	 */
	public function byApp($planId, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];
		$q = [
			$fields,
			'xxt_plan_task_schema',
			['aid' => $planId, 'state' => 1],
		];
		$q2 = ['o' => 'task_seq'];

		$tasks = $this->query_objs_ss($q, $q2);

		return $tasks;
	}
	/**
	 * 获得指定模板任务的生成时间
	 */
	public function getBornAt($oTaskSchema, $prveBornAt = false) {
		switch ($oTaskSchema->born_mode) {
		case 'U':
			$today = new \DateTime();
			if ($prveBornAt) {
				$today->setTimestamp($prveBornAt);
			}
			$today->setTime(0, 0); // 设置为0点
			if (!empty($oTaskSchema->born_offset)) {
				$today->add(new \DateInterval($oTaskSchema->born_offset));
			}
			$bornAt = $today->getTimestamp();
			break;
		case 'A':
			$today = new \DateTime();
			if (empty($oTaskSchema->born_offset)) {
				$bornAt = 0;
			} else {
				$today->setTimestamp($oTaskSchema->born_offset);
				$today->setTime(0, 0); // 设置为0点
				$bornAt = $today->getTimestamp();
			}
			break;
		case 'F':
			$bornAt = 0;
			break;
		case 'P':
			$prevBornAt = new \DateTime();
			$prevBornAt->setTimestamp($prveBornAt);
			if (!empty($oTaskSchema->born_offset)) {
				$prevBornAt->add(new \DateInterval($oTaskSchema->born_offset));
			}
			$bornAt = $prevBornAt->getTimestamp();
			break;
		}

		return $bornAt;
	}
	/**
	 *
	 */
	public function bornMock($oApp, $beginSeq, $endSeq, $prevBornAt) {
		$mocks = [];
		$q = [
			'id,born_mode,born_offset,task_seq',
			'xxt_plan_task_schema',
			['aid' => $oApp->id, 'task_seq' => (object) ['op' => 'between', 'pat' => [$beginSeq, $endSeq]]],
		];
		$q2 = ['o' => 'task_seq'];
		$tasks = $this->query_objs_ss($q, $q2);
		foreach ($tasks as $oTask) {
			$bornAt = $this->getBornAt($oTask, $prevBornAt);
			if ($bornAt == 0) {
				return false;
			}
			$prevBornAt = $oTask->born_at = $bornAt;
			$mocks[] = $oTask;
		}

		return $mocks;
	}
}