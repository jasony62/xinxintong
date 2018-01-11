<?php
namespace matter\plan;
/**
 * 用户任务
 */
class task_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function byId($taskId, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];
		$q = [
			$fields,
			'xxt_plan_task',
			['id' => $taskId],
		];

		$oUsrTask = $this->query_obj_ss($q);
		if ($oUsrTask) {
			if (property_exists($oUsrTask, 'data')) {
				$oUsrTask->data = empty($oUsrTask->data) ? new \stdClass : json_decode($oUsrTask->data);
			}
			if (property_exists($oUsrTask, 'supplement')) {
				$oUsrTask->supplement = empty($oUsrTask->supplement) ? new \stdClass : json_decode($oUsrTask->supplement);
			}
		}

		return $oUsrTask;
	}
	/**
	 *
	 */
	public function bySchema($oUser, $oTaskSchema, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];
		$q = [
			$fields,
			'xxt_plan_task',
			['userid' => $oUser->uid, 'task_schema_id' => $oTaskSchema->id, 'state' => 1],
		];

		$oUsrTask = $this->query_obj_ss($q);
		if ($oUsrTask) {
			if (property_exists($oUsrTask, 'data')) {
				$oUsrTask->data = empty($oUsrTask->data) ? new \stdClass : json_decode($oUsrTask->data);
			}
			if (property_exists($oUsrTask, 'supplement')) {
				$oUsrTask->supplement = empty($oUsrTask->supplement) ? new \stdClass : json_decode($oUsrTask->supplement);
			}
		}

		return $oUsrTask;
	}
	/**
	 * 指定用户在指定计划任务中执行的最后1条任务
	 */
	public function lastByApp($oUser, $oApp, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];

		$sql = 'select t.id,ts.task_seq from xxt_plan_task t,xxt_plan_task_schema ts';
		$sql .= " where t.state=1 and ts.state=1 and t.task_schema_id=ts.id and t.aid='{$oApp->id}' and t.userid='{$oUser->uid}'";
		$sql .= ' order by task_seq desc limit 0,1';

		$oLastUsrTask = $this->query_obj($sql);
		if (false === $oLastUsrTask) {
			return false;
		}

		$q = [
			$fields,
			'xxt_plan_task',
			['id' => $oLastUsrTask->id],
		];

		$oUsrTask = $this->query_obj_ss($q);
		$oUsrTask->task_seq = $oLastUsrTask->task_seq;
		if (isset($oUsrTask->data)) {
			$oUsrTask->data = empty($oUsrTask->data) ? new \stdClass : json_decode($oUsrTask->data);
		}

		return $oUsrTask;
	}
	/**
	 * 指定用户在指定计划任务中执行的最后1条任务
	 */
	public function lastBySchema($oUser, $oTaskSchema, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];

		$sql = 'select t.id,ts.task_seq from xxt_plan_task t,xxt_plan_task_schema ts';
		$sql .= ' where t.state=1 and ts.state=1 and t.task_schema_id=ts.id';
		$sql .= " and t.aid='{$oTaskSchema->aid}' and t.userid='{$oUser->uid}' and ts.task_seq<{$oTaskSchema->task_seq}";
		$sql .= ' order by task_seq desc limit 0,1';

		$oLastUsrTask = $this->query_obj($sql);
		if (false === $oLastUsrTask) {
			return false;
		}

		$q = [
			$fields,
			'xxt_plan_task',
			['id' => $oLastUsrTask->id],
		];

		$oUsrTask = $this->query_obj_ss($q);
		$oUsrTask->task_seq = $oLastUsrTask->task_seq;
		if (isset($oUsrTask->data)) {
			$oUsrTask->data = empty($oUsrTask->data) ? new \stdClass : json_decode($oUsrTask->data);
		}

		return $oUsrTask;
	}
	/**
	 * 获得指定用户当前应该执行的任务
	 */
	public function nowSchemaByApp($oUser, $oApp) {
		$modelSchTsk = $this->model('matter\plan\schema\task');
		$aSchemaOptions = ['fields' => 'id,title,task_seq,born_mode,born_offset,jump_delayed,can_patch'];

		$oLastUserTask = $this->lastByApp($oUser, $oApp, ['fields' => 'id,task_schema_id,verified,born_at']);
		if ($oLastUserTask) {
			if ($oLastUserTask->verified !== 'Y') {
				$oTaskSchema = $modelSchTsk->byId($oLastUserTask->task_schema_id, $aSchemaOptions);
				return $oTaskSchema;
			}
		} else {
			//$oTaskSchema = $modelSchTsk->bySeq($oApp, 1, $aSchemaOptions);
			//return $oTaskSchema;
			$startAt = $this->getStartAt($oApp, $oUser);
			$oLastUserTask = (object) ['task_seq' => 0, 'born_at' => $startAt];
		}
		$current = time();
		$oTaskSchema = $modelSchTsk->bySeq($oApp, (int) $oLastUserTask->task_seq + 1, $aSchemaOptions);
		if ($oTaskSchema) {
			$bornAt = $modelSchTsk->getBornAt($oApp, $oUser, $oTaskSchema, $oLastUserTask->born_at);
			$oTaskSchema->born_at = $bornAt;
			$bCanJump = empty($oTaskSchema->actions) || $oTaskSchema->jump_delayed === 'Y' || ($oTaskSchema->jump_delayed === 'U' && $oApp->jump_delayed === 'Y');
			if ($bCanJump) {
				/* 根据计划时间跳过延迟任务 */
				while (true) {
					$oNextTaskSchema = $modelSchTsk->bySeq($oApp, (int) $oTaskSchema->task_seq + 1, $aSchemaOptions);
					if (false === $oNextTaskSchema) {
						break;
					}
					$nextBornAt = $modelSchTsk->getBornAt($oApp, $oUser, $oNextTaskSchema, $oTaskSchema->born_at);
					if ($nextBornAt > $current) {
						break;
					}
					$oNextTaskSchema->born_at = $nextBornAt;
					$oTaskSchema = $oNextTaskSchema;
					/* 任务是否必须要执行 */
					if (!empty($oTaskSchema->actions)) {
						if ($oTaskSchema->jump_delayed === 'N' || ($oTaskSchema->jump_delayed === 'U' && $oApp->jump_delayed === 'N')) {
							break;
						}
					}
				}
			}
		}

		return $oTaskSchema;
	}
	/**
	 * 创建用户任务
	 */
	public function create($oUser, $oApp, $oTaskSchema, $oProto = null) {
		// 检查是否可以创建任务
		$aResult = $this->_canBorn($oUser, $oApp, $oTaskSchema);
		if (false === $aResult[0]) {
			return $aResult;
		}

		$bornAt = $aResult[1];
		if (0 === $bornAt) {
			return [false, '无法获得有效的任务生成时间'];
		}
		$bDelayed = $aResult[2];

		$current = time();
		$oNewTask = new \stdClass;

		$oNewTask->siteid = $oTaskSchema->siteid;
		$oNewTask->aid = $oTaskSchema->aid;
		$oNewTask->task_schema_id = $oTaskSchema->id;
		$oNewTask->task_seq = $oTaskSchema->task_seq;
		$oNewTask->born_at = $bornAt;
		$oNewTask->patch_at = $bDelayed ? $current : 0;
		$oNewTask->userid = $oUser->uid;
		$oNewTask->group_id = '';
		$oNewTask->nickname = $oUser->nickname;
		$oNewTask->first_enroll_at = 0;
		$oNewTask->last_enroll_at = 0;
		$oNewTask->state = 1;
		$oNewTask->data = '';
		$oNewTask->comment = '';
		$oNewTask->verified = 'P';
		$oNewTask->submit_log = '';

		$oNewTask->id = $this->insert('xxt_plan_task', $oNewTask, true);

		return [true, $oNewTask];
	}
	/**
	 * 是否可以生成用户任务，如果可以，返回计划生成时间
	 */
	private function _canBorn($oUser, $oApp, $oTaskSchema) {
		if ($this->bySchema($oUser, $oTaskSchema, ['fields' => 'id'])) {
			return [false, '不能重复生成任务'];
		}
		/* 按计划当前应该执行的任务 */
		$oNowTaskSchema = $this->nowSchemaByApp($oUser, $oApp);
		/* 生成当前任务 */
		if ($oTaskSchema->id === $oNowTaskSchema->id) {
			return [true, $oNowTaskSchema->born_at, false];
		}
		/* 不允许生成当前任务的后续任务 */
		if ($oTaskSchema->task_seq > $oNowTaskSchema->task_seq) {
			return [false, '不能执行任务【' . $oTaskSchema->title . '】，只能按计划执行任务【' . $oNowTaskSchema->title . '】'];
		}
		/* 处理延期任务 */
		if ($oTaskSchema->can_patch === 'N' || ($oTaskSchema->can_patch === 'U' && $oApp->can_patch === 'N')) {
			return [false, '不能执行延期任务【' . $oTaskSchema->title . '】'];
		}
		/* 计算延期任务的计划生成时间 */
		$bornAt = 0;
		$oLastUserTask = $this->lastBySchema($oUser, $oTaskSchema);
		if ($oLastUserTask) {
			$modelSchTsk = $this->model('matter\plan\schema\task');
			if ($oTaskSchema->task_seq > $oLastUserTask->task_seq) {
				$mocks = $modelSchTsk->bornMock($oApp, $oUser, (int) $oLastUserTask->task_seq + 1, (int) $oTaskSchema->task_seq, $oLastUserTask->born_at);
				if (!empty($mocks)) {
					$oMock = $mocks[count($mocks) - 1];
					if ($oMock->id === $oTaskSchema->id) {
						$bornAt = $oMock->born_at;
					}
				}
			}
		}

		return [true, $bornAt, true];
	}
	/**
	 *
	 */
	public function byApp($oApp, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];
		$q = [
			$fields,
			'xxt_plan_task',
			['aid' => $oApp->id, 'state' => 1],
		];
		$q2 = ['o' => 'first_enroll_at desc'];

		$tasks = $this->query_objs_ss($q, $q2);
		if (count($tasks)) {
			$modelSchAct = $this->model('matter\plan\schema\action');
			$aActOptions = ['fields' => 'id,action_desc,action_seq,check_schemas'];
			foreach ($tasks as $oTask) {
				/* 行动项 */
				if (isset($oTask->task_schema_id)) {
					$oTask->actions = $modelSchAct->byTask($oTask->task_schema_id, $aActOptions);
				}
				/* 处理数据 */
				if (!empty($oTask->data)) {
					$oTask->data = json_decode($oTask->data);
				}
				if (!empty($oTask->score)) {
					$oTask->score = json_decode($oTask->score);
				}
				if (!empty($oTask->supplement)) {
					$oTask->supplement = json_decode($oTask->supplement);
				}
			}
		}

		$result = new \stdClass;
		$result->tasks = $tasks;
		$result->total = count($tasks);

		return $result;
	}
	/**
	 * 指定用户的任务
	 */
	public function byUser($oApp, $oUser, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];
		$q = [
			$fields,
			'xxt_plan_task',
			['aid' => $oApp->id, 'userid' => $oUser->uid, 'state' => 1],
		];
		$q2 = ['o' => 'task_seq'];

		$tasks = $this->query_objs_ss($q, $q2);

		$result = new \stdClass;
		$result->tasks = $tasks;
		$result->total = count($tasks);

		return $result;
	}
	/**
	 * 获得首个任务的开始时间
	 */
	public function getStartAt($oApp, $oUser) {
		$modelSchTsk = $this->model('matter\plan\schema\task');
		$oFirst = $modelSchTsk->bySeq($oApp, 1, ['fields' => 'id,born_mode,born_offset']);
		if ($oFirst && $oFirst->born_mode === 'A' && $oFirst->born_offset > 0) {
			$startAt = $oFirst->born_offset;
		} else {
			if ($oFirst->born_mode === 'U') {
				$modelUsr = $this->model('matter\plan\user');
				$oAppUser = $modelUsr->byUser($oApp, $oUser);
				if ($oAppUser && !empty($oAppUser->start_at)) {
					$startAt = $oAppUser->start_at;
				} else {
					$startAt = 0;
				}
			} else {
				$startAt = time();
			}
		}

		return $startAt;
	}
}