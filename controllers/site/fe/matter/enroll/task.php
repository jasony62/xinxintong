<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 活动任务
 */
class task extends base {
	/**
	 * 当前用户需要完成的任务
	 */
	public function list_action($app) {
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$oUser = $this->getUser($oApp);

		$tasks = new \stdClass;

		// 回答任务
		$tasks->question = $this->_getQuestionTask($oApp, $oUser);
		if (!empty($tasks->question->rules)) {
			$modelTsk = $this->model('matter\enroll\task', $oApp);
			$modelTop = $this->model('matter\enroll\topic', $oApp);
			foreach ($tasks->question->rules as $oRule) {
				$oTask = $modelTsk->byRule($oRule, ['createIfNone' => true]);
				if ($oTask) {
					$oTopic = $modelTop->byTask($oTask, ['createIfNone' => true]);
				}
			}
		}

		// 回答任务
		$tasks->answer = $this->_getAnswerTask($oApp, $oUser);

		// 投票任务
		$tasks->vote = $this->_getVoteTask($oApp, $oUser);

		// 打分任务
		$tasks->score = $this->_getScoreTask($oApp, $oUser);

		return new \ResponseData($tasks);
	}
	/**
	 * 获得指定投票题目的记录数据
	 */
	public function votingRecData_action($app) {
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1' || empty($oApp->voteConfig)) {
			return new \ObjectNotFoundError();
		}
		$oUser = $this->getUser($oApp);

		/* 获取记录的投票信息 */
		$aCanVoteSchemas = $this->model('matter\enroll\task', $oApp)->getCanVote($oUser);
		if (empty($aCanVoteSchemas)) {
			return new \ObjectNotFoundError('没有设置投票题目');
		}
		$oActiveRnd = $oApp->appRound;

		$modelRecDat = $this->model('matter\enroll\data');
		$oResult = new \stdClass;
		foreach ($aCanVoteSchemas as $oSchema) {
			if ($oSchema->type === 'multitext') {

			} else {
				$oDataResult = $modelRecDat->byApp($oApp, $oUser, ['schemas' => [$oSchema->id]]);
				foreach ($oDataResult->records as $oRecData) {
					$oVotingRecData = new \stdClass;
					$oVoteResult = new \stdClass;
					$vote_at = (int) $modelRecDat->query_val_ss(['vote_at', 'xxt_enroll_vote', ['rid' => $oActiveRnd->rid, 'data_id' => $oRecData->id, 'state' => 1, 'userid' => $oUser->uid]]);
					$oVoteResult->vote_at = $vote_at;
					$oVoteResult->vote_num = $oRecData->vote_num;
					$oVoteResult->state = $aCanVoteSchemas[$oSchema->id]->vote->state;
					//
					$oVotingRecData->id = $oRecData->id;
					$oVotingRecData->view = $oRecData->value;
					$oVotingRecData->voteResult = $oVoteResult;
					//
					$oResult->{$oSchema->id}[] = $oVotingRecData;
				}
			}
		}

		return new \ResponseData($oResult);
	}
	/**
	 * 对填写数据进行投票
	 *
	 * @param int $data xxt_enroll_record_data 的id
	 */
	public function vote_action($data) {
		$modelRecDat = $this->model('matter\enroll\data');
		$oRecData = $modelRecDat->byId($data, ['fields' => 'id,aid,rid,enroll_key,state,multitext_seq,userid,nickname']);
		if (false === $oRecData || $oRecData->state !== '1') {
			return new \ObjectNotFoundError('（1）指定的对象不存在或不可用');
		}

		$oApp = $this->model('matter\enroll')->byId($oRecData->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError('（2）指定的对象不存在或不可用');
		}

		$oUser = $this->getUser($oApp);

		$aVoteResult = $modelRecDat->vote($oApp, $oRecData->id, $oUser);
		if (false === $aVoteResult[0]) {
			return new \ResponseError($aVoteResult[1]);
		}
		$oNewVote = $aVoteResult[1];

		/* 记录事件汇总数据 */
		$modelEnlEvt = $this->model('matter\enroll\event');
		if ($oRecData->multitext_seq > 0) {
			$modelEnlEvt->voteRecCowork($oApp, $oRecData, $oUser);
		} else {
			$modelEnlEvt->voteRecSchema($oApp, $oRecData, $oUser);
		}

		return new \ResponseData([$oNewVote, $aVoteResult[2]]);
	}
	/**
	 * 对填写数据撤销投票
	 *
	 * @param int $data xxt_enroll_record_data 的id
	 */
	public function unvote_action($data) {
		$modelRecDat = $this->model('matter\enroll\data');
		$oRecData = $modelRecDat->byId($data, ['fields' => 'id,aid,rid,enroll_key,state,multitext_seq,userid,nickname']);
		if (false === $oRecData || $oRecData->state !== '1') {
			return new \ObjectNotFoundError('（1）指定的对象不存在或不可用');
		}

		$oApp = $this->model('matter\enroll')->byId($oRecData->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError('（2）指定的对象不存在或不可用');
		}

		$oUser = $this->getUser($oApp);

		$aVoteResult = $modelRecDat->unvote($oApp, $oRecData->id, $oUser);
		if (false === $aVoteResult[0]) {
			return new \ResponseError($aVoteResult[1]);
		}

		/* 记录事件汇总数据 */
		$modelEnlEvt = $this->model('matter\enroll\event');
		if ($oRecData->multitext_seq > 0) {
			$modelEnlEvt->unvoteRecCowork($oApp, $oRecData, $oUser);
		} else {
			$modelEnlEvt->unvoteRecSchema($oApp, $oRecData, $oUser);
		}

		return new \ResponseData($aVoteResult[1]);
	}
	/**
	 * 提问任务
	 */
	private function _getQuestionTask($oApp, $oUser) {
		if (empty($oApp->questionConfig)) {
			return false;
		}

		$aQuestionRules = $this->model('matter\enroll\task', $oApp)->getCanQuestion($oUser);
		if (empty($aQuestionRules)) {
			return false;
		}

		$aRunnings = [];
		foreach ($aQuestionRules as $oQuestionRule) {
			if ($this->getDeepValue($oQuestionRule, 'state') === 'IP') {
				$aRunnings[$oQuestionRule->id] = $oQuestionRule;
			}
		}
		if (empty($aRunnings)) {
			return false;
		}

		$oTask = new \stdClass;
		$oTask->name = 'question';
		$oTask->rules = $aRunnings;

		return $oTask;
	}
	/**
	 * 回答任务
	 */
	private function _getAnswerTask($oApp, $oUser) {
		if (empty($oApp->answerConfig)) {
			return false;
		}

		$aAnswerSchemas = $this->model('matter\enroll\task', $oApp)->getCanAnswer($oUser);
		if (empty($aAnswerSchemas)) {
			return false;
		}

		$aRunnings = [];
		foreach ($aAnswerSchemas as $oAnswerSchema) {
			if ($this->getDeepValue($oAnswerSchema, 'answer.state') === 'IP') {
				$aRunnings[$oAnswerSchema->id] = $oAnswerSchema;
			}
		}
		if (empty($aRunnings)) {
			return false;
		}

		$oTask = new \stdClass;
		$oTask->name = 'answer';
		$oTask->schemas = $aRunnings;

		return $oTask;
	}
	/**
	 * 投票任务
	 */
	private function _getVoteTask($oApp, $oUser) {
		if (empty($oApp->voteConfig)) {
			return false;
		}

		$aVoteSchemas = $this->model('matter\enroll\task', $oApp)->getCanVote($oUser);
		if (empty($aVoteSchemas)) {
			return false;
		}

		$aRunnings = [];
		foreach ($aVoteSchemas as $oVoteSchema) {
			if ($this->getDeepValue($oVoteSchema, 'vote.state') === 'IP') {
				$aRunnings[$oVoteSchema->id] = $oVoteSchema;
			}
		}
		if (empty($aRunnings)) {
			return false;
		}
		$oTask = new \stdClass;
		$oTask->name = 'vote';
		$oTask->schemas = $aRunnings;

		return $oTask;
	}
	/**
	 * 投票任务
	 */
	private function _getScoreTask($oApp, $oUser) {
		if (empty($oApp->scoreConfig)) {
			return false;
		}

		$aScoreSchemas = $this->model('matter\enroll\task', $oApp)->getCanScore($oUser);
		if (empty($aScoreSchemas)) {
			return false;
		}
		$aRunnings = [];
		foreach ($aScoreSchemas as $oScoreSchema) {
			if ($this->getDeepValue($oScoreSchema, 'score.state') === 'IP') {
				$aRunnings[] = $oScoreSchema;
			}
		}
		if (empty($aRunnings)) {
			return false;
		}

		$oTask = new \stdClass;
		$oTask->name = 'score';
		$oTask->schemas = $aRunnings;

		return $oTask;
	}
}