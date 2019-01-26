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
	const TypeNameZh = ['question' => '提问', 'answer' => '回答', 'vote' => '投票', 'score' => '打分'];

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
		$validProps = ['id', 'start', 'end', 'enabled'];
		foreach ($oConfig as $prop => $val) {
			if (!in_array($prop, $validProps)) {
				unset($oConfig->{$prop});
			}
		}

		return $oConfig;
	}
	/**
	 * 去掉无效的内容
	 */
	public function purifyQuestion($oConfig) {
		$validProps = ['id', 'start', 'end', 'role', 'limit', 'enabled'];
		foreach ($oConfig as $prop => $val) {
			if (!in_array($prop, $validProps)) {
				unset($oConfig->{$prop});
			}
			switch ($prop) {
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
		$validProps = ['id', 'start', 'end', 'role', 'limit', 'schemas', 'source', 'enabled'];
		foreach ($oConfig as $prop => $val) {
			if (!in_array($prop, $validProps)) {
				unset($oConfig->{$prop});
			}
			switch ($prop) {
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
		$validProps = ['id', 'start', 'end', 'role', 'limit', 'schemas', 'target', 'enabled', 'source'];
		foreach ($oConfig as $prop => $val) {
			if (!in_array($prop, $validProps)) {
				unset($oConfig->{$prop});
			}
			switch ($prop) {
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
			if (!empty($oQuestionConfig->role->groups)) {
				if (empty($oUser->group_id) || !in_array($oUser->group_id, $oQuestionConfig->role->groups)) {
					continue;
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
	public function byRule($oRule, $aOptons = []) {
		$fields = empty($aOptons['fields']) ? 'id,rid,start_at,end_at' : $aOptons['fields'];
		$bCreateIfNone = isset($aOptons['createIfNone']) ? $aOptons['createIfNone'] : false;
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
		$fields = empty($aOptons['fields']) ? 'id,aid,rid,start_at,end_at,config_type,config_id' : $aOptons['fields'];
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
}