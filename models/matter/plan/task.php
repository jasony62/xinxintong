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
		$oNewTask->group_id = isset($oUser->group_id) ? $oUser->group_id : '';
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
	public function byApp($oApp, $aOptions = [], $oCriteria = null) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];
		$q = [
			$fields,
			'xxt_plan_task',
			"aid = '{$oApp->id}' and state = 1",
		];

		if (!empty($oCriteria->byComment)) {
			$q[2] .= " and comment like '%" . $this->escape($oCriteria->byComment) . "%'";
		}
		if (!empty($oCriteria->byTaskSchema)) {
			$q[2] .= " and task_schema_id = " . $this->escape($oCriteria->byTaskSchema);
		}
		if (!empty($oCriteria->record->verified)) {
			$q[2] .= " and verified = '" . $this->escape($oCriteria->record->verified) . "'";
		}
		if (isset($oCriteria->data)) {
			$oAppSchemasById = new \stdClass;
			foreach ($oApp->checkSchemas as $oSchema) {
				$oAppSchemasById->{$oSchema->id} = $oSchema;
			}

			$where = '';
			foreach ($oCriteria->data as $k => $v) {
				if (!empty($v) && isset($oAppSchemasById->{$k})) {
					$oAppSchema = $oAppSchemasById->{$k};
					$where .= ' and (';
					if ($oAppSchema->type === 'multiple') {
						// 选项ID是否互斥，不存在，例如：v1和v11
						$bOpExclusive = true;
						$strOpVals = '';
						foreach ($oAppSchema->ops as $op) {
							$strOpVals .= ',' . $op->v;
						}
						foreach ($oAppSchema->ops as $op) {
							if (false !== strpos($strOpVals, $op->v)) {
								$bOpExclusive = false;
								break;
							}
						}
						// 拼写sql
						$v2 = explode(',', $v);
						foreach ($v2 as $index => $v2v) {
							if ($index > 0) {
								$where .= ' and ';
							}
							// 获得和题目匹配的子字符串
							$dataBySchema = 'substr(substr(data,locate(\'"' . $k . '":"\',data)),1,locate(\'"\',substr(data,locate(\'"' . $k . '":"\',data)),' . (strlen($k) + 5) . '))';
							$where .= '(';
							if ($bOpExclusive) {
								$where .= $dataBySchema . ' like \'%' . $v2v . '%\'';
							} else {
								$where .= $dataBySchema . ' like \'%"' . $v2v . '"%\'';
								$where .= ' or ' . $dataBySchema . ' like \'%"' . $v2v . ',%\'';
								$where .= ' or ' . $dataBySchema . ' like \'%,' . $v2v . ',%\'';
								$where .= ' or ' . $dataBySchema . ' like \'%,' . $v2v . '"%\'';
							}
							$where .= ')';
						}
					} else {
						$where .= 'data like \'%"' . $k . '":"' . $v . '%\'';
					}
					$where .= ')';
				}
			}
			$q[2] .= $where;
		}

		$q2 = ['o' => 'first_enroll_at desc'];
		if (isset($aOptions['paging'])) {
			$q2['r'] = [];
			$q2['r']['o'] = ($aOptions['paging']['page'] - 1) * $aOptions['paging']['size'];
			$q2['r']['l'] = $aOptions['paging']['size'];
		}
		$tasks = $this->query_objs_ss($q, $q2);
		if (count($tasks)) {
			$modelSchAct = $this->model('matter\plan\schema\action');
			$aActOptions = ['fields' => 'id,action_desc,action_seq,check_schemas'];
			foreach ($tasks as $oTask) {
				/* 行动项 */
				if (isset($oTask->task_schema_id)) {
					$oTask->actions = $modelSchAct->byTask($oTask->task_schema_id, $aActOptions);
					/* 获取对应的任务名称 */
					if (!isset($tskSchmTitle)) {
						$taskSchemas = $this->model('matter\plan\schema\task')->byApp($oApp->id, ['fields' => 'id,title']);
						$tskSchmTitle = new \stdClass;
						foreach ($taskSchemas as $taskSchema) {
							$tskSchmTitle->{$taskSchema->id} = $taskSchema->title;
						}
					}
					$oTask->taskSchemaTitle = $tskSchmTitle->{$oTask->task_schema_id};
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
		$q[0] = 'count(id)';
		$total = (int) $this->query_val_ss($q);
		$result->total = $total;

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
		$startAt = 0;
		$modelSchTsk = $this->model('matter\plan\schema\task');
		$oFirst = $modelSchTsk->bySeq($oApp, 1, ['fields' => 'id,born_mode,born_offset']);
		if ($oFirst) {
			if ($oFirst->born_mode === 'A' && $oFirst->born_offset > 0) {
				$startAt = $oFirst->born_offset;
			} else {
				if ($oFirst->born_mode === 'U') {
					$modelUsr = $this->model('matter\plan\user');
					$oAppUser = $modelUsr->byUser($oApp, $oUser);
					if ($oAppUser && !empty($oAppUser->start_at)) {
						$startAt = $oAppUser->start_at;
					}
				} else {
					$startAt = time();
				}
			}
		}

		return $startAt;
	}
	/*
	*
	*/
	public function listSchema($oApp, $checkSchmId, $taskSchmId = '', $actSchmId = '', $options = []) {
		$checkSchmId = $this->escape($checkSchmId);
		foreach ($oApp->checkSchemas as $oSchema) {
			if ($oSchema->id === $checkSchmId) {
				$oDataSchema = $oSchema;
				break;
			}
		}
		if (!isset($oDataSchema)) {
			return false;
		}

		if (strpos($checkSchmId, 'member.') === 0) {
			$checkSchmId = 'member';
		}

		if ($options) {
			is_array($options) && $options = (object) $options;
			if (isset($options->paging)) {
				$page = isset($options->paging['page']) ? $options->paging['page'] : null;
				$size = isset($options->paging['size']) ? $options->paging['size'] : null;
			}
		}

		$result = new \stdClass; // 返回的结果
		$result->records = [];
		$result->total = 0;

		// 查询参数
		$q = [
			'task_id,value',
			"xxt_plan_task_action",
			"state=1 and aid='{$oApp->id}' and check_schema_id='{$checkSchmId}' and value<>''",
		];
		if ($oDataSchema->type === 'date') {

		}
		/* 指定用户 */
		if (!empty($options->owner)) {
			$q[2] .= " and userid='" . $options->owner . "'";
		}
		/* 指定任务和行动项 */
		if (!empty($taskSchmId) && !empty($actSchmId)) {
			$q[2] .= " and task_schema_id = '{$taskSchmId}' and action_schema_id = '{$actSchmId}'";
		}

		$q2 = [];
		// 查询结果分页
		if (!empty($page) && !empty($size)) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}

		$recordsBySchema = [];
		// 处理获得的数据
		if ($records = $this->query_objs_ss($q, $q2)) {
			//如果是数值型计算合计值
			if (isset($oDataSchema->number) && $oDataSchema->number === 'Y') {
				$p = [
					'sum(value)',
					'xxt_enroll_record_data',
					['aid' => $oApp->id, 'check_schema_id' => $checkSchmId, 'state' => 1],
				];
				if (!empty($taskSchmId) && !empty($actSchmId)) {
					$q[2] .= " and task_schema_id = '{$taskSchmId}' and action_schema_id = '{$actSchmId}'";
				}

				$sum = (int) $this->query_val_ss($p);
				$result->sum = $sum;
			}
			/* 补充记录标识 */
			if (!isset($oApp->rpConfig) || empty($oApp->rpConfig->marks)) {
				$defaultMark = new \stdClass;
				$defaultMark->id = 'nickname';
				$defaultMark->name = 'nickname';
				$marks = [$defaultMark];
			} else {
				$marks = $oApp->rpConfig->marks;
			}
			foreach ($records as &$record) {
				$rec = $this->byId($record->task_id, ['fields' => 'id,aid,task_schema_id,task_seq,nickname,data,last_enroll_at']);
				if (empty($actSchmId)) {
					$rec->data = reset($rec->data);
				} else {
					$rec->data = $rec->data->$actSchmId;
				}
				$record->task = $rec;
			}
			$result->records = $records;
		}

		// 符合条件的数据总数
		$q[0] = 'count(*)';
		$total = (int) $this->query_val_ss($q, $q2);
		$result->total = $total;

		return $result;
	}
	/**
	 * 统计选择题、记分题汇总信息
	 */
	public function &getStat($appIdOrObj, $taskSchmId, $actSchmId, $renewCache = 'Y') {
		if (is_string($appIdOrObj)) {
			$oApp = $this->model('matter\plan')->byId($appIdOrObj);
		} else {
			$oApp = $appIdOrObj;
		}

		$current = time();
		if ($renewCache === 'Y') {
			/* 上一次保留统计结果的时间，每条记录的时间都一样 */
			$q = [
				'create_at',
				'xxt_plan_task_stat',
				['aid' => $oApp->id],
			];
			if (!empty($taskSchmId) && strcasecmp($taskSchmId, 'all') != 0 && !empty($actSchmId) && strcasecmp($actSchmId, 'all') != 0 ) {
				$q[2]['task_schema_id'] = $taskSchmId;
				$q[2]['action_schema_id'] = $actSchmId;
			}

			$q2 = ['r' => ['o' => 0, 'l' => 1]];
			$last = $this->query_objs_ss($q, $q2);
			/* 上次统计后的新登记记录数 */
			if (count($last) === 1) {
				$last = $last[0];
				$q = [
					'count(id)',
					'xxt_plan_task_action',
					"aid='$oApp->id' and state=1 and enroll_at>={$last->create_at}",
				];
				if (!empty($taskSchmId) && strcasecmp($taskSchmId, 'all') != 0  && !empty($actSchmId) && strcasecmp($actSchmId, 'all') != 0) {
					$q[2] .= " and task_schema_id = $taskSchmId";
					$q[2] .= " and action_schema_id = $actSchmId";
				}
				$q2['g'] = 'userid';

				$newCnt = (int) $this->query_val_ss($q, $q2);
			} else {
				$newCnt = 999;
			}
			// 如果更新的登记数据，重新计算统计结果
			if ($newCnt > 0) {
				$result = $this->_calcStat($oApp,$taskSchmId, $actSchmId);
				// 保存统计结果
				$this->delete(
					'xxt_plan_task_stat',
					['aid' => $oApp->id]
				);
				if (!empty($taskSchmId) && strcasecmp($taskSchmId, 'all') != 0 && !empty($actSchmId) && strcasecmp($actSchmId, 'all') != 0 ) {
					$q[2]['task_schema_id'] = $taskSchmId;
					$q[2]['action_schema_id'] = $actSchmId;
				}
				foreach ($result as $id => $oDataBySchema) {
					foreach ($oDataBySchema->ops as $op) {
						$r = [
							'siteid' => $oApp->siteid,
							'aid' => $oApp->id,
							'create_at' => $current,
							'id' => $id,
							'title' => $oDataBySchema->title,
							'v' => $op->v,
							'l' => $op->l,
							'c' => $op->c
						];
						if (!empty($taskSchmId) && strcasecmp($taskSchmId, 'all') != 0 && !empty($actSchmId) && strcasecmp($actSchmId, 'all') != 0 ) {
							$q[2]['task_schema_id'] = $taskSchmId;
							$q[2]['action_schema_id'] = $actSchmId;
						} else {
							$q[2]['task_schema_id'] = 0;
							$q[2]['action_schema_id'] = 0;
						}
						$this->insert('xxt_plan_task_stat', $r);
					}
				}
			} else {
				/* 从缓存中获取统计数据 */
				$result = [];
				$q = [
					'id,title,v,l,c',
					'xxt_plan_task_stat',
					['aid' => $oApp->id],
				];
				if (!empty($taskSchmId) && strcasecmp($taskSchmId, 'all') != 0 && !empty($actSchmId) && strcasecmp($actSchmId, 'all') != 0 ) {
					$q[2]['task_schema_id'] = $taskSchmId;
					$q[2]['action_schema_id'] = $actSchmId;
				}
				$aCached = $this->query_objs_ss($q);
				foreach ($aCached as $oDataByOp) {
					if (empty($result[$oDataByOp->id])) {
						$oDataBySchema = (object) [
							'id' => $oDataByOp->id,
							'title' => $oDataByOp->title,
							'ops' => [],
							'sum' => 0,
						];
						$result[$oDataByOp->id] = $oDataBySchema;
					} else {
						$oDataBySchema = $result[$oDataByOp->id];
					}
					$op = (object) [
						'v' => $oDataByOp->v,
						'l' => $oDataByOp->l,
						'c' => $oDataByOp->c,
					];
					$oDataBySchema->ops[] = $op;
					$oDataBySchema->sum += $op->c;
				}
			}
		} else {
			$result = $this->_calcStat($oApp,$taskSchmId, $actSchmId);
		}

		return $result;
	}
	/**
	 * 统计选择题、记分题汇总信息
	 */
	private function &_calcStat($oApp,$taskSchmId = '', $actSchmId = '') {
		$result = [];

		$checkSchemas = $oApp->checkSchemas;
		foreach ($checkSchemas as $oSchema) {
			if (!in_array($oSchema->type, ['single', 'multiple', 'phase', 'score', 'multitext'])) {
				continue;
			}
			$result[$oSchema->id] = $oDataBySchema = (object) [
				'title' => isset($oSchema->title) ? $oSchema->title : '',
				'id' => $oSchema->id,
				'ops' => [],
			];
			$oDataBySchema->sum = 0;
			if (in_array($oSchema->type, ['single', 'phase'])) {
				foreach ($oSchema->ops as $op) {
					/**
					 * 获取数据
					 */
					$q = [
						'count(*)',
						'xxt_plan_task_action',
						['aid' => $oApp->id, 'state' => 1, 'check_schema_id' => $oSchema->id, 'value' => $op->v],
					];
					if (!empty($taskSchmId) && strcasecmp($taskSchmId, 'all') != 0 && !empty($actSchmId) && strcasecmp($actSchmId, 'all') != 0 ) {
						$q[2]['task_schema_id'] = $taskSchmId;
						$q[2]['action_schema_id'] = $actSchmId;
					}
					$op->c = (int) $this->query_val_ss($q);
					$oDataBySchema->ops[] = $op;
					$oDataBySchema->sum += $op->c;
				}
			} else if ($oSchema->type === 'multiple') {
				foreach ($oSchema->ops as $op) {
					/**
					 * 获取数据
					 */
					$q = [
						'count(*)',
						'xxt_plan_task_action',
						"aid='$oApp->id' and state=1 and check_schema_id='{$oSchema->id}' and FIND_IN_SET('{$op->v}', value)",
					];
					if (!empty($taskSchmId) && strcasecmp($taskSchmId, 'all') != 0 && !empty($actSchmId) && strcasecmp($actSchmId, 'all') != 0 ) {
						$q[2]['task_schema_id'] = $taskSchmId;
						$q[2]['action_schema_id'] = $actSchmId;
					}
					$op->c = (int) $this->query_val_ss($q);
					$oDataBySchema->ops[] = $op;
					$oDataBySchema->sum += $op->c;
				}
			} else if ($oSchema->type === 'score') {
				$scoreByOp = [];
				foreach ($oSchema->ops as &$op) {
					$op->c = 0;
					$oDataBySchema->ops[] = $op;
					$scoreByOp[$op->v] = $op;
				}
				// 计算总分数
				$q = [
					'value',
					'xxt_plan_task_action',
					['aid' => $oApp->id, 'state' => 1, 'check_schema_id' => $oSchema->id],
				];
				if (!empty($taskSchmId) && strcasecmp($taskSchmId, 'all') != 0 && !empty($actSchmId) && strcasecmp($actSchmId, 'all') != 0 ) {
					$q[2]['task_schema_id'] = $taskSchmId;
					$q[2]['action_schema_id'] = $actSchmId;
				}

				$values = $this->query_objs_ss($q);
				foreach ($values as $oValue) {
					if (!empty($oValue->value)) {
						$oValue = json_decode($oValue->value);
						if (!empty($oValue) && is_object($oValue)) {
							foreach ($oValue as $opKey => $opValue) {
								if (isset($scoreByOp[$opKey]->c)) {
									$scoreByOp[$opKey]->c += (int) $opValue;
								}
							}
						}
					}
				}
				// 计算平均分
				if ($rowNumber = count($values)) {
					foreach ($oSchema->ops as &$op) {
						$op->c = $op->c / $rowNumber;
					}
				} else {
					$op->c = 0;
				}
				$oDataBySchema->sum += $op->c;
			}
		}

		return $result;
	}
}