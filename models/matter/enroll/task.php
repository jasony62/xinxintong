<?php
namespace matter\enroll;
/**
 * 活动任务
 */
class task_model extends \TMS_MODEL {
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
		$current = time();
		if ($oStartRule = $this->getDeepValue($oTaskConfig, 'start.time')) {
			if ($this->getDeepValue($oStartRule, 'mode') === 'after_round_start_at') {
				if ($this->getDeepValue($oStartRule, 'unit') === 'hour') {
					$afterHours = (int) $this->getDeepValue($oStartRule, 'value');
					if (empty($oRound->start_at) || ($current < $oRound->start_at + ($afterHours * 3600))) {
						return [true, 'BS'];
					}
				}
			}
		}
		if ($oEndRule = $this->getDeepValue($oTaskConfig, 'end.time')) {
			if ($this->getDeepValue($oEndRule, 'mode') === 'after_round_start_at') {
				if ($this->getDeepValue($oEndRule, 'unit') === 'hour') {
					$afterHours = (int) $this->getDeepValue($oEndRule, 'value');
					if (empty($oRound->start_at) || ($current > $oRound->start_at + ($afterHours * 3600))) {
						return [true, 'AE'];
					}
				}
			}
		}

		return [true, 'IP'];
	}
	/**
	 * 提问任务
	 */
	public function getCanQuestion($oApp, $oUser = null, $oRound = null) {
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
			$oQuestionRule->state = $aValid[1];
			$oQuestionRule->limit = $this->getDeepValue($oQuestionConfig, 'limit');
			$oQuestionRule->groups = $this->getDeepValue($oQuestionConfig, 'role.groups');
			$aQuestionRules[] = $oQuestionRule;
		}

		return $aQuestionRules;
	}
	/**
	 * 需要进行回答的题目
	 */
	public function getCanAnswer($oApp, $oUser = null, $oRound = null) {
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
					$oVoteRule = new \stdClass;
					$oVoteRule->state = $aValid[1];
					$oVoteRule->limit = $this->getDeepValue($oAnswerConfig, 'limit');
					$oVoteRule->groups = $this->getDeepValue($oAnswerConfig, 'role.groups');
					$oSchema->answer = $oVoteRule;
					$aVoteSchemas[$oSchema->id] = $oSchema;
				}
			}
		}

		return $aVoteSchemas;
	}
	/**
	 * 需要进行投票的题目
	 */
	public function getCanVote($oApp, $oUser = null, $oRound = null) {
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
					$oVoteRule->state = $aValid[1];
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
	public function getCanScore($oApp, $oUser = null, $oRound = null) {
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
			$oScoreConfig->state = $aValid[1];
			foreach ($oApp->dynaDataSchemas as $oSchema) {
				if (in_array($oSchema->id, $oScoreConfig->schemas)) {
					$oScoreRule = new \stdClass;
					$oScoreRule->state = $aValid[1];
					$oScoreRule->scoreApp = $this->getDeepValue($oScoreConfig, 'scoreApp');
					$oScoreRule->groups = $this->getDeepValue($oScoreConfig, 'role.groups');
					$oSchema->score = $oScoreRule;
					$aScoreSchemas[$oSchema->id] = $oSchema;
				}
			}
		}

		return $aScoreSchemas;
	}
}