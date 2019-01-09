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

	public function __construct($oApp) {
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
	public function purifyQuestion($oConfig) {
		$validProps = ['id', 'start', 'end', 'role', 'limit'];
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
		$validProps = ['id', 'start', 'end', 'role', 'limit', 'schemas', 'target'];
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
	public function purifyVote($oConfig) {
		$validProps = ['id', 'start', 'end', 'role', 'limit', 'schemas', 'target'];
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
	 *
	 */
	private function _checkTaskConfigByTime($oTaskConfig, $oRound) {
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
	 * 提问任务
	 */
	public function getCanQuestion($oUser = null, $oRound = null) {
		$oApp = $this->_oApp;
		if (!isset($oApp->dynaDataSchemas) || !isset($oApp->questionConfig)) {
			$oApp = $this->model('matter\enroll')->byId($oApp->id, ['cascaded' => 'N', 'fields' => 'id,data_schemas,question_config']);
		}
		if (empty($oRound)) {
			$oRound = $oApp->appRound;
		}

		$aQuestionRules = [];
		foreach ($oApp->questionConfig as $oQuestionConfig) {
			if (!empty($oQuestionConfig->role->groups)) {
				if (empty($oUser->group_id) || !in_array($oUser->group_id, $oQuestionConfig->role->groups)) {
					continue;
				}
			}
			$aValid = $this->_checkTaskConfigByTime($oQuestionConfig, $oRound);
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
	public function getCanAnswer($oUser = null, $oRound = null) {
		$oApp = $this->_oApp;
		if (!isset($oApp->dynaDataSchemas) || !isset($oApp->answerConfig)) {
			$oApp = $this->model('matter\enroll')->byId($oApp->id, ['cascaded' => 'N', 'fields' => 'id,data_schemas,answer_config']);
		}
		if (empty($oRound)) {
			$oRound = $oApp->appRound;
		}

		$aVoteSchemas = [];
		foreach ($oApp->answerConfig as $oAnswerConfig) {
			if (!empty($oAnswerConfig->role->groups)) {
				if (empty($oUser->group_id) || !in_array($oUser->group_id, $oAnswerConfig->role->groups)) {
					continue;
				}
			}
			$aValid = $this->_checkTaskConfigByTime($oAnswerConfig, $oRound);
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
					$oSchema->answer = $oAnswerRule;
					$aVoteSchemas[$oSchema->id] = $oSchema;
				}
			}
		}

		return $aVoteSchemas;
	}
	/**
	 * 需要进行投票的题目
	 */
	public function getCanVote($oUser = null, $oRound = null) {
		$oApp = $this->_oApp;
		if (!isset($oApp->dynaDataSchemas) || !isset($oApp->voteConfig)) {
			$oApp = $this->model('matter\enroll')->byId($oApp->id, ['cascaded' => 'N', 'fields' => 'id,data_schemas,vote_config']);
		}
		if (empty($oRound)) {
			$oRound = $oApp->appRound;
		}

		$aVoteSchemas = [];
		foreach ($oApp->voteConfig as $oVoteConfig) {
			if (!empty($oVoteConfig->role->groups)) {
				if (empty($oUser->group_id) || !in_array($oUser->group_id, $oVoteConfig->role->groups)) {
					continue;
				}
			}
			$aValid = $this->_checkTaskConfigByTime($oVoteConfig, $oRound);
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
					$oSchema->vote = $oVoteRule;
					$aVoteSchemas[$oSchema->id] = $oSchema;
				}
			}
		}

		return $aVoteSchemas;
	}
	/**
	 * 需要进行打分的题目
	 */
	public function getCanScore($oUser = null, $oRound = null) {
		$oApp = $this->_oApp;
		if (!isset($oApp->dynaDataSchemas) || !isset($oApp->scoreConfig)) {
			$oApp = $this->model('matter\enroll')->byId($oApp->id, ['cascaded' => 'N', 'fields' => 'id,data_schemas,score_config']);
		}
		if (empty($oRound)) {
			$oRound = $oApp->appRound;
		}

		$aScoreSchemas = [];
		foreach ($oApp->scoreConfig as $oScoreConfig) {
			if (!empty($oScoreConfig->role->groups)) {
				if (empty($oUser->group_id) || !in_array($oUser->group_id, $oScoreConfig->role->groups)) {
					continue;
				}
			}
			$aValid = $this->_checkTaskConfigByTime($oScoreConfig, $oRound);
			if (false === $aValid[0]) {
				continue;
			}
			foreach ($oApp->dynaDataSchemas as $oSchema) {
				if (in_array($oSchema->id, $oScoreConfig->schemas)) {
					$oScoreRule = new \stdClass;
					$oScoreRule->id = $oScoreConfig->id;
					$oScoreRule->type = 'score';
					$oScoreRule->rid = $oRound->rid;
					tms_object_merge($oVoteRule, $aValid[1]);
					$oScoreRule->scoreApp = $this->getDeepValue($oScoreConfig, 'scoreApp');
					$oScoreRule->groups = $this->getDeepValue($oScoreConfig, 'role.groups');
					$oSchema->score = $oScoreRule;
					$aScoreSchemas[$oSchema->id] = $oSchema;
				}
			}
		}

		return $aScoreSchemas;
	}
	/**
	 * 获得指定规则生成的任务
	 */
	public function byRule($oRule, $aOptons = []) {
		$fields = empty($aOptons['fields']) ? 'id,start_at,end_at' : $aOptons['fields'];
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
	 * 指定用户当前是否存在任务
	 */
	public function currentByUser($oUser, $aOptions = []) {
		$fields = empty($aOptons['fields']) ? 'id,start_at,end_at' : $aOptons['fields'];
		$q = [
			$fields,
			'xxt_enroll_task',
			['aid' => $this->_oApp->id, 'state' => 1],
		];
		if (!empty($aOptons['type'])) {
			$q[2]['config_type'] = $aOptons['type'];
		}

		/* 任务时间段 */
		$current = time();
		$q[2]['start_at'] = (object) ['op' => '<=', 'pat' => $current];
		$q[2]['end_at'] = (object) ['op' => '>=', 'pat' => $current];

		$tasks = $this->query_objs_ss($q);

		return $tasks;
	}
}