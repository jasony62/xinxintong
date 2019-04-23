<?php
namespace matter\enroll;
/**
 * 记录活动任务
 */
class task_model extends \TMS_MODEL {
	/**
	 * 任务所在的记录活动
	 */
	private $_oApp;
	/**
	 * 任务类型的中文名称
	 */
	const TypeNameZh = ['baseline' => '目标', 'question' => '提问', 'answer' => '回答', 'vote' => '投票', 'score' => '打分'];

	public function __construct($oApp = null) {
		$this->_oApp = $oApp;
	}
	/**
	 *
	 */
	protected function table() {
		return 'xxt_enroll_task';
	}
	/**
	 * 去掉无效的内容
	 * offset.matter.id
	 * offset.matter.type RC round_cron
	 */
	private function _purifyTime(&$oTime) {
		foreach ($oTime as $prop => $val) {
			if (!in_array($prop, ['offset'])) {
				unset($oTime->{$prop});
			}
			switch ($prop) {
			case 'offset':
				if (empty($oTime->offset->matter->id) || $this->getDeepValue($oTime, 'offset.matter.type') !== 'RC') {
					return false;
				}
				break;
			}
		}
		return $oTime;
	}
	/**
	 * 去掉无效的内容
	 */
	private function _purifyLimit(&$oLimit) {
		foreach ($oLimit as $prop => $val) {
			if (!in_array($prop, ['min', 'max'])) {
				unset($oLimit->{$prop});
			}
		}
		return $oLimit;
	}
	/**
	 * 去掉无效的内容
	 */
	private function _purifySource(&$oSource) {
		foreach ($oSource as $prop => $val) {
			if (!in_array($prop, ['scope', 'config', 'limit'])) {
				unset($oSource->{$prop});
			}
		}
		return $oSource;
	}
	/**
	 * 去掉无效的内容
	 */
	public function purifyBaseline($oConfig) {
		$validProps = ['id', 'time', 'start', 'end', 'enabled'];
		foreach ($oConfig as $prop => $val) {
			if (!in_array($prop, $validProps)) {
				unset($oConfig->{$prop});
			}
			switch ($prop) {
			case 'time':
				if (is_object($val)) {
					if (false === $this->_purifyTime($val)) {
						unset($oConfig->time);
					}
				} else {
					unset($oConfig->time);
				}
				break;
			}
		}

		return $oConfig;
	}
	/**
	 * 去掉无效的内容
	 */
	public function purifyQuestion($oConfig) {
		$validProps = ['id', 'time', 'start', 'end', 'role', 'limit', 'enabled'];
		foreach ($oConfig as $prop => $val) {
			if (!in_array($prop, $validProps)) {
				unset($oConfig->{$prop});
			}
			switch ($prop) {
			case 'time':
				if (is_object($val)) {
					if (false === $this->_purifyTime($val)) {
						unset($oConfig->time);
					}
				} else {
					unset($oConfig->time);
				}
				break;
			case 'limit':
				if (is_object($val)) {
					$this->_purifyLimit($val);
				} else {
					unset($oConfig->limit);
				}
				break;
			}
		}

		return $oConfig;
	}
	/**
	 * 去掉无效的内容
	 */
	public function purifyAnswer($oConfig) {
		$validProps = ['id', 'time', 'start', 'end', 'role', 'limit', 'schemas', 'source', 'enabled'];
		foreach ($oConfig as $prop => $val) {
			if (!in_array($prop, $validProps)) {
				unset($oConfig->{$prop});
			}
			switch ($prop) {
			case 'time':
				if (is_object($val)) {
					if (false === $this->_purifyTime($val)) {
						unset($oConfig->time);
					}
				} else {
					unset($oConfig->time);
				}
				break;
			case 'limit':
				if (is_object($val)) {
					$this->_purifyLimit($val);
				} else {
					unset($oConfig->limit);
				}
				break;
			case 'source':
				if (is_object($val)) {
					if (isset($val->scope)) {
						$this->_purifySource($val);
					} else {
						unset($oConfig->source);
					}
				} else {
					unset($oConfig->source);
				}
				break;
			}
		}

		return $oConfig;
	}
	/**
	 * 去掉无效的内容
	 */
	public function purifyVote($oConfig) {
		$validProps = ['id', 'time', 'start', 'end', 'role', 'limit', 'schemas', 'target', 'enabled', 'source'];
		foreach ($oConfig as $prop => $val) {
			if (!in_array($prop, $validProps)) {
				unset($oConfig->{$prop});
			}
			switch ($prop) {
			case 'time':
				if (is_object($val)) {
					if (false === $this->_purifyTime($val)) {
						unset($oConfig->time);
					}
				} else {
					unset($oConfig->time);
				}
				break;
			case 'limit':
				if (is_object($val)) {
					$this->_purifyLimit($val);
				} else {
					unset($oConfig->limit);
				}
				break;
			case 'source':
				if (is_object($val)) {
					if (isset($val->scope)) {
						$this->_purifySource($val);
					} else {
						unset($oConfig->source);
					}
				} else {
					unset($oConfig->source);
				}
				break;
			}
		}

		return $oConfig;
	}
	/**
	 * 获得指定任务定义的状态
	 */
	public function getRuleStateByRound($oTaskConfig, $oRound) {
		$taskState = 'IP';
		$startAt = $endAt = 0;
		$current = time();
		if ($oStartRule = $this->getDeepValue($oTaskConfig, 'start.time')) {
			if ($this->getDeepValue($oStartRule, 'mode') === 'after_round_start_at') {
				if ($this->getDeepValue($oStartRule, 'unit') === 'hour') {
					$afterHours = (int) $this->getDeepValue($oStartRule, 'value', 0);
					if (!empty($oRound->start_at)) {
						$startAt = $oRound->start_at + ($afterHours * 3600);
						if ($current < $startAt) {
							$taskState = 'BS';
						}
					} else {
						$taskState = 'BS';
					}
				}
			}
		}
		if ($oEndRule = $this->getDeepValue($oTaskConfig, 'end.time')) {
			if ($this->getDeepValue($oEndRule, 'mode') === 'after_round_start_at') {
				if ($this->getDeepValue($oEndRule, 'unit') === 'hour') {
					$afterHours = (int) $this->getDeepValue($oEndRule, 'value', 0);
					if (!empty($oRound->start_at)) {
						$endAt = $oRound->start_at + ($afterHours * 3600);
						if ($current > $endAt) {
							$taskState = 'AE';
						}
					} else {
						$taskState = 'AE';
					}
				}
			}
		}

		return [true, ['state' => $taskState, 'start_at' => $startAt, 'end_at' => $endAt]];
	}
	/**
	 * 获得定义的任务规则
	 */
	public function getRule($taskType, $oUser = null, $oRound = null) {
		$rules = $this->{'get' . ucfirst($taskType) . 'Rule'}($oUser, $oRound);

		return $rules;
	}
	/**
	 * 目标任务
	 */
	public function getBaselineRule($oUser = null, $oRound = null) {
		$oApp = $this->_oApp;
		if (!isset($oApp->baselineConfig)) {
			$oApp = $this->model('matter\enroll')->byId($oApp->id, ['cascaded' => 'N', 'fields' => 'id,baseline_config']);
		}
		if (empty($oRound)) {
			$oRound = $oApp->appRound;
		}
		$oBaselineRnd = $this->model('matter\enroll\round')->getBaseline($this->_oApp, ['assignedRid' => $oRound->rid]);
		if (false === $oBaselineRnd) {
			return [];
		}

		$aBaselineRules = [];
		foreach ($oApp->baselineConfig as $oBaselineConfig) {
			if ($this->getDeepValue($oBaselineConfig, 'enabled') !== 'Y') {
				continue;
			}
			$aValid = $this->getRuleStateByRound($oBaselineConfig, $oBaselineRnd);
			if (false === $aValid[0]) {
				continue;
			}
			$oBaselineRule = new \stdClass;
			$oBaselineRule->id = $oBaselineConfig->id;
			$oBaselineRule->type = 'baseline';
			$oBaselineRule->rid = $oBaselineRnd->rid;
			tms_object_merge($oBaselineRule, $aValid[1]);
			$aBaselineRules[] = $oBaselineRule;
		}

		return $aBaselineRules;
	}
	/**
	 * 提问任务
	 */
	public function getQuestionRule($oUser = null, $oRound = null) {
		$oApp = $this->_oApp;
		if (!isset($oApp->dynaDataSchemas) || !isset($oApp->questionConfig)) {
			$oApp = $this->model('matter\enroll')->byId($oApp->id, ['cascaded' => 'N', 'fields' => 'id,data_schemas,question_config']);
		}
		if (empty($oRound)) {
			$oRound = $oApp->appRound;
		}

		$aQuestionRules = [];
		foreach ($oApp->questionConfig as $oQuestionConfig) {
			if ($this->getDeepValue($oQuestionConfig, 'enabled') !== 'Y') {
				continue;
			}
			if ($oUser !== false) {
				if (!empty($oQuestionConfig->role->groups)) {
					if (empty($oUser->group_id) || !in_array($oUser->group_id, $oQuestionConfig->role->groups)) {
						continue;
					}
				}
			}
			$aValid = $this->getRuleStateByRound($oQuestionConfig, $oRound);
			if (false === $aValid[0]) {
				continue;
			}
			$oQuestionRule = new \stdClass;
			$oQuestionRule->id = $oQuestionConfig->id;
			$oQuestionRule->type = 'question';
			$oQuestionRule->rid = $oRound->rid;
			tms_object_merge($oQuestionRule, $aValid[1]);
			$oQuestionRule->limit = $this->getDeepValue($oQuestionConfig, 'limit');
			$oQuestionRule->groups = $this->getDeepValue($oQuestionConfig, 'role.groups');
			$aQuestionRules[] = $oQuestionRule;
		}

		return $aQuestionRules;
	}
	/**
	 * 需要进行回答的题目
	 */
	public function getAnswerRule($oUser = null, $oRound = null) {
		$oApp = $this->_oApp;
		if (!isset($oApp->dynaDataSchemas) || !isset($oApp->answerConfig)) {
			$oApp = $this->model('matter\enroll')->byId($oApp->id, ['cascaded' => 'N', 'fields' => 'id,data_schemas,answer_config']);
		}
		if (empty($oRound)) {
			$oRound = $oApp->appRound;
		}

		$aAnswerRules = [];
		foreach ($oApp->answerConfig as $oAnswerConfig) {
			if ($this->getDeepValue($oAnswerConfig, 'enabled') !== 'Y') {
				continue;
			}
			if (!empty($oAnswerConfig->role->groups)) {
				if (empty($oUser->group_id) || !in_array($oUser->group_id, $oAnswerConfig->role->groups)) {
					continue;
				}
			}
			$aValid = $this->getRuleStateByRound($oAnswerConfig, $oRound);
			if (false === $aValid[0]) {
				continue;
			}
			foreach ($oApp->dynaDataSchemas as $oSchema) {
				if (in_array($oSchema->id, $oAnswerConfig->schemas)) {
					$oAnswerRule = new \stdClass;
					$oAnswerRule->id = $oAnswerConfig->id;
					$oAnswerRule->type = 'answer';
					$oAnswerRule->rid = $oRound->rid;
					tms_object_merge($oAnswerRule, $aValid[1]);
					$oAnswerRule->limit = $this->getDeepValue($oAnswerConfig, 'limit');
					$oAnswerRule->groups = $this->getDeepValue($oAnswerConfig, 'role.groups');
					$oAnswerRule->schema = $oSchema;
					$aAnswerRules[$oSchema->id] = $oAnswerRule;
				}
			}
		}

		return $aAnswerRules;
	}
	/**
	 * 需要进行投票的题目
	 */
	public function getVoteRule($oUser = null, $oRound = null) {
		$oApp = $this->_oApp;
		if (!isset($oApp->dynaDataSchemas) || !isset($oApp->voteConfig)) {
			$oApp = $this->model('matter\enroll')->byId($oApp->id, ['cascaded' => 'N', 'fields' => 'id,data_schemas,vote_config']);
		}
		if (empty($oRound)) {
			$oRound = $oApp->appRound;
		}

		$aVoteRules = [];
		foreach ($oApp->voteConfig as $oVoteConfig) {
			if ($this->getDeepValue($oVoteConfig, 'enabled') !== 'Y') {
				continue;
			}
			if (!empty($oVoteConfig->role->groups)) {
				if (empty($oUser->group_id) || !in_array($oUser->group_id, $oVoteConfig->role->groups)) {
					continue;
				}
			}
			$aValid = $this->getRuleStateByRound($oVoteConfig, $oRound);
			if (false === $aValid[0]) {
				continue;
			}
			foreach ($oApp->dynaDataSchemas as $oSchema) {
				if (in_array($oSchema->id, $oVoteConfig->schemas)) {
					$oVoteRule = new \stdClass;
					$oVoteRule->id = $oVoteConfig->id;
					$oVoteRule->type = 'vote';
					$oVoteRule->rid = $oRound->rid;
					tms_object_merge($oVoteRule, $aValid[1]);
					$oVoteRule->limit = $this->getDeepValue($oVoteConfig, 'limit');
					$oVoteRule->groups = $this->getDeepValue($oVoteConfig, 'role.groups');
					$oVoteRule->schema = $oSchema;
					$aVoteRules[$oSchema->id] = $oVoteRule;
				}
			}
		}

		return $aVoteRules;
	}
	/**
	 * 需要进行打分的题目
	 */
	public function getScoreRule($oUser = null, $oRound = null) {
		$oApp = $this->_oApp;
		if (!isset($oApp->dynaDataSchemas) || !isset($oApp->scoreConfig)) {
			$oApp = $this->model('matter\enroll')->byId($oApp->id, ['cascaded' => 'N', 'fields' => 'id,data_schemas,score_config']);
		}
		if (empty($oRound)) {
			$oRound = $oApp->appRound;
		}

		$aScoreRules = [];
		foreach ($oApp->scoreConfig as $oScoreConfig) {
			if ($this->getDeepValue($oScoreConfig, 'enabled') !== 'Y') {
				continue;
			}
			if (!empty($oScoreConfig->role->groups)) {
				if (empty($oUser->group_id) || !in_array($oUser->group_id, $oScoreConfig->role->groups)) {
					continue;
				}
			}
			$aValid = $this->getRuleStateByRound($oScoreConfig, $oRound);
			if (false === $aValid[0]) {
				continue;
			}
			foreach ($oApp->dynaDataSchemas as $oSchema) {
				if (in_array($oSchema->id, $oScoreConfig->schemas)) {
					$oScoreRule = new \stdClass;
					$oScoreRule->id = $oScoreConfig->id;
					$oScoreRule->type = 'score';
					$oScoreRule->rid = $oRound->rid;
					tms_object_merge($oScoreRule, $aValid[1]);
					$oScoreRule->scoreApp = $this->getDeepValue($oScoreConfig, 'scoreApp');
					$oScoreRule->groups = $this->getDeepValue($oScoreConfig, 'role.groups');
					$oScoreRule->schema = $oSchema;
					$aScoreRules[$oSchema->id] = $oScoreRule;
				}
			}
		}

		return $aScoreRules;
	}
	/**
	 * 获得指定规则生成的任务
	 *
	 * @param object $oRule[type,id,rid,start_at,end_at] 如果不自动创建任务，不需要指定start_at和end_at
	 */
	public function byRule($oRule, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? 'id,rid,start_at,end_at' : $aOptions['fields'];
		$bCreateIfNone = isset($aOptions['createIfNone']) ? $aOptions['createIfNone'] : false;
		$q = [
			$fields,
			'xxt_enroll_task',
			['config_type' => $oRule->type, 'config_id' => $oRule->id, 'aid' => $this->_oApp->id, 'rid' => $oRule->rid, 'state' => 1],
		];
		$oTask = $this->query_obj_ss($q);
		if (false === $oTask) {
			if (true === $bCreateIfNone) {
				$oTask = new \stdClass;
				$oTask->siteid = $this->_oApp->siteid;
				$oTask->aid = $this->_oApp->id;
				$oTask->rid = $oRule->rid;
				$oTask->config_type = $oRule->type;
				$oTask->config_id = $oRule->id;
				$oTask->start_at = $oRule->start_at;
				$oTask->end_at = $oRule->end_at;
				$oTask->id = $this->insert('xxt_enroll_task', $oTask, true);
				$oTask->newCreate = true;
			}
		} else if (isset($oRule->start_at) || isset($oRule->end_at)) {
			$aUpdated['start_at'] = $this->getDeepValue($oRule, 'start_at', 0);
			$aUpdated['end_at'] = $this->getDeepValue($oRule, 'end_at', 0);
			$this->update('xxt_enroll_task', $aUpdated, ['id' => $oTask->id]);
			$oTask->start_at = $aUpdated['start_at'];
			$oTask->end_at = $aUpdated['end_at'];
		}

		return $oTask;
	}
	/**
	 *
	 */
	public function configById($type, $id) {
		$oConfig = tms_array_search($this->_oApp->{$type . 'Config'}, function ($oRule) use ($id) {return $oRule->id === $id;});
		if ($oConfig) {
			$oConfig->type = $type;
		}

		return $oConfig;
	}
	/**
	 * 获得任务的规则定义
	 */
	public function ruleByTask($oTask, $oRound) {
		$oRule = $this->configById($oTask->config_type, $oTask->config_id);
		if (false === $oRule) {
			return [false, '（1）投票任务参数错误'];
		}

		$oRuleState = $this->getRuleStateByRound($oRule, $oRound);
		if (false === $oRuleState[0]) {
			return [false, '（2）投票任务参数错误'];
		}
		tms_object_merge($oRule, $oRuleState[1]);

		return [true, $oRule];
	}
	/**
	 * 指定用户当前是否存在任务
	 */
	public function byId($id, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? 'id,aid,rid,start_at,end_at,config_type,config_id' : $aOptions['fields'];
		$q = [
			$fields,
			'xxt_enroll_task',
			['id' => $id],
		];
		$oTask = $this->query_obj_ss($q);
		if ($oTask && isset($oTask->config_type) && isset($oTask->config_id)) {
			if (empty($this->_oApp) && !empty($oTask->aid)) {
				$this->_oApp = $this->model('matter\enroll')->byId($oTask->aid, ['fields' => '*']);
			}
			if (!empty($this->_oApp->{$oTask->config_type . 'Config'})) {
				$oRuleConfig = $this->model('matter\enroll\task', $this->_oApp)->configById($oTask->config_type, $oTask->config_id);
				if ($oRuleConfig && $this->getDeepValue($oRuleConfig, 'enabled') === 'Y') {
					$oTaskRound = $this->model('matter\enroll\round')->byId($oTask->rid);
					if ($oTaskRound) {
						$oRuleState = $this->getRuleStateByRound($oRuleConfig, $oTaskRound);
						if (true === $oRuleState[0]) {
							tms_object_merge($oTask, $oRuleConfig, ['source', 'scoreApp', 'schemas']);
							tms_object_merge($oTask, $oRuleState[1], ['state']);
						}
					}
				}
			}
		}

		return $oTask;
	}
	/**
	 * 返回当前用户所有任务
	 */
	public function byUser($oUser, $aTaskTypes, $aTaskStates, $ek = null) {
		/* 指定了记录 */
		if (!empty($ek)) {
			$oRecord = $this->model('matter\enroll\record')->byId($ek);
			if (false === $oRecord || $oRecord->state !== '1') {
				return [false, '指定记录不存在'];
			}
		}

		$tasks = [];
		foreach ($aTaskTypes as $taskType) {
			$rules = $this->getRule($taskType, $oUser);
			if (!empty($rules)) {
				foreach ($rules as $oRule) {
					if (!in_array($oRule->state, $aTaskStates)) {
						continue;
					}
					$oTask = $this->byRule($oRule, ['createIfNone' => true]);
					if ($oTask) {
						tms_object_merge($oTask, $oRule, ['type', 'state', 'limit', 'groups', 'schemas']);
						if (!isset($modelTop)) {
							$modelTop = $this->model('matter\enroll\topic', $this->_oApp);
						}
						if ($oTopic = $modelTop->byTask($oTask, ['createIfNone' => true])) {
							$oTask->topic = $oTopic;
							/* 检查针对指定的记录，是否存在回答任务 */
							if (isset($oRecord) && $oTask->type === 'answer') {
								$oTaskRecordsResult = $modelTop->records($oTopic);
								$oTaskRecord = tms_array_search($oTaskRecordsResult->records, function ($oTaskRecord) use ($oRecord) {return $oTaskRecord->id === $oRecord->id;});
								if (!$oTaskRecord) {
									continue;
								}
							}
						}
						// 任务完成情况
						if (!isset($oTask->newCreate)) {
							$result = $this->isUndoneByTask($oUser, $oTask);
							$oTask->undone = $result;
						} else {
							$limitMin = !empty($oTask->limit->min) ? (int) $oTask->limit->min : 1;
							$oTask->undone = [true, $limitMin, 0];
						}

						$tasks[] = $oTask;
					}
				}
			}
		}

		return [true, $tasks];
	}

	/**
	 * 指定的用户是否没有完成活动要求的任务
	 */
	public function isUndoneByTask($oAssignedUser, $oTask, $rid = '') {
		$oApp = $this->_oApp;
		// 至少提交数量
		$limitMin = !empty($oTask->limit->min) ? (int) $oTask->limit->min : 1;
		// 任务专题
		$topic = $oTask->topic;
		// 任务未开始直接返回false
		if ($oTask->state === 'BS') {
			return [true, $limitMin, 0];
		}
		// 用户组限制
		if (!empty($oTask->groups)) {
			if (empty($oAssignedUser->group_id) || !in_array($oAssignedUser->group_id, $oTask->groups)) {
				return [false];
			}
		}

		if (empty($rid)) {
			$rid = $oApp->appRound;
		} else {
			$rid = $this->model('matter\enroll\round')->byId($oApp->id, ['fields' => 'id,rid,title,purpose,start_at,end_at,mission_rid']);
		}
		// 任务时间范围
		$satrtTime = empty($oTask->start_at) ? $rid->start_at : $oTask->start_at;
		$endTime = empty($oTask->end_at) ? $rid->end_at : $oTask->end_at;

		if ($oTask->type === 'question') {
			// 提问专题下规定时间范围内用户的记录数量
			$q = [
				'er.id',
				'xxt_enroll_topic_record tr,xxt_enroll_record er',
				"tr.topic_id = {$topic->id} and tr.record_id = er.id and er.state = 1 and er.userid = '" . $oAssignedUser->uid . "'"
			];
			if ($satrtTime > 0 || $endTime > 0) {
				$q[2] .= " and ((";
				$satrtTime > 0 && $q[2] .= "er.first_enroll_at > {$satrtTime}";
				$satrtTime > 0 && $endTime > 0 && $q[2] .= " and ";
				$endTime > 0 && $q[2] .= "er.first_enroll_at < {$endTime}";
				$q[2] .= ") or (";
				$satrtTime > 0 && $q[2] .= "er.enroll_at > {$satrtTime}";
				$satrtTime > 0 && $endTime > 0 && $q[2] .= " and ";
				$endTime > 0 && $q[2] .= "er.enroll_at < {$endTime}";
				$q[2] .= " ))";
			}
			$userRecords = $this->query_vals_ss($q);
			$userRecordSum = count($userRecords);
			if ($userRecordSum < $limitMin) {
				// 检查是否有通过评论完成任务,去除对自己提交记录的评论
				$q2 = [
					'count(tr.id)',
					'xxt_enroll_topic_record tr,xxt_enroll_record er,xxt_enroll_record_remark rr',
					"tr.topic_id = {$topic->id} and tr.record_id = er.id and er.state = 1 and er.enroll_key = rr.enroll_key and rr.state = 1 and rr.userid = '" . $oAssignedUser->uid . "'"
				];
				if ($userRecordSum > 0) {
					$q2[2] .= " and tr.record_id not in ('";
					$q2[2] .= implode("','", $userRecords);
					$q2[2] .= "')";
				}
				if ($satrtTime > 0 || $endTime > 0) {
					$q2[2] .= " and (";
					$satrtTime > 0 && $q2[2] .= "rr.create_at > {$satrtTime}";
					$satrtTime > 0 && $endTime > 0 && $q2[2] .= " and ";
					$endTime > 0 && $q2[2] .= "rr.create_at < {$endTime}";
					$q2[2] .= " )";
				}
				$userRemarkSum = (int) $this->query_val_ss($q2);
				if (($userRecordSum + $userRemarkSum) < $limitMin) {
					return [true, $limitMin, ($userRecordSum + $userRemarkSum)];
				}
			}
		}
		if ($oTask->type === 'vote') {
			$q = [
				'count(id)',
				'xxt_enroll_vote',
				"aid = '{$oApp->id}' and state = 1 and userid = '{$oAssignedUser->uid}'"
			];
			if ($satrtTime > 0 || $endTime > 0) {
				$q[2] .= " and (";
				$satrtTime > 0 && $q[2] .= "vote_at > {$satrtTime}";
				$satrtTime > 0 && $endTime > 0 && $q[2] .= " and ";
				$endTime > 0 && $q[2] .= "vote_at < {$endTime}";
				$q[2] .= " )";
			}
			$userVoteSum = (int) $this->query_val_ss($q);
			if ($userVoteSum < $limitMin) {
				return [true, $limitMin, $userVoteSum];
			}
		}
		if ($oTask->type === 'answer') {
			$q = [
				'ed.id',
				'xxt_enroll_topic_record tr,xxt_enroll_record_data ed',
				"tr.topic_id = {$topic->id} and tr.data_id = ed.id and ed.userid = '" . $oAssignedUser->uid . "' and ed.state = 1"
			];
			if ($satrtTime > 0 || $endTime > 0) {
				$q[2] .= " and (";
				$satrtTime > 0 && $q[2] .= "ed.submit_at > {$satrtTime}";
				$satrtTime > 0 && $endTime > 0 && $q[2] .= " and ";
				$endTime > 0 && $q[2] .= "ed.submit_at < {$endTime}";
				$q[2] .= " )";
			}
			$p = ['g' => 'ed.record_id'];
			$userAnswers = $this->query_objs_ss($q, $p);
			$userAnswerSum = count($userAnswers);
			if ($userAnswerSum < $limitMin) {
				return [true, $limitMin, $userAnswerSum];
			}
		}

		return [false];
	}
}