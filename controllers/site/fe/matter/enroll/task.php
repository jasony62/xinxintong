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
	public function list_action($app, $type = null, $state = null, $rid = null, $ek = null) {
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N', 'appRid' => $rid]);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		/* 指定了记录 */
		if (!empty($ek)) {
			$oRecord = $this->model('matter\enroll\record')->byId($ek);
			if (false === $oRecord || $oRecord->state !== '1') {
				return new \ObjectNotFoundError();
			}
		}

		$oUser = $this->getUser($oApp);

		/* 有效的任务类型 */
		$aTaskTypes = ['question', 'answer', 'vote', 'score'];
		if (!empty($type)) {
			if (!in_array($type, $aTaskTypes)) {
				return new \ParameterError('没有指定有效的任务类型');
			}
			$aTaskTypes = [$type];
		}
		/* 有效的任务状态 */
		$aTaskStates = ['IP', 'BS', 'AE'];
		if (!empty($state)) {
			if (!in_array($state, $aTaskStates)) {
				return new \ParameterError('没有指定有效的任务状态');
			}
			$aTaskStates = [$state];
		}

		$modelTsk = $this->model('matter\enroll\task', $oApp);
		$tasks = [];
		foreach ($aTaskTypes as $taskType) {
			$rules = $modelTsk->getRule($taskType, $oUser);
			if (!empty($rules)) {
				foreach ($rules as $oRule) {
					if (!in_array($oRule->state, $aTaskStates)) {
						continue;
					}
					$oTask = $modelTsk->byRule($oRule, ['createIfNone' => true]);
					if ($oTask) {
						tms_object_merge($oTask, $oRule, ['type', 'state', 'limit', 'groups', 'schemas']);
						if (!isset($modelTop)) {
							$modelTop = $this->model('matter\enroll\topic', $oApp);
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

						$tasks[] = $oTask;
					}
				}
			}
		}
		/* 按照任务的开始时间排序 */
		usort($tasks, function ($a, $b) {
			if ($a->start_at === $b->start_at) {
				return 0;
			}
			return $a->start_at < $b->start_at ? -1 : 1;
		});

		return new \ResponseData($tasks);
	}
	/**
	 * 获得指定投票题目的记录数据
	 */
	public function votingRecData_action($app, $task) {
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1' || empty($oApp->voteConfig)) {
			return new \ObjectNotFoundError();
		}
		$oActiveRnd = $oApp->appRound;

		$modelTsk = $this->model('matter\enroll\task', $oApp);
		$oTask = $modelTsk->byId($task);
		if (false === $oTask || $oTask->config_type !== 'vote') {
			return new \ObjectNotFoundError('没有找到匹配的投票任务');
		}

		$oVoteRule = $modelTsk->ruleByTask($oTask, $oActiveRnd);
		if (false === $oVoteRule[0]) {
			return new \ParameterError($oVoteRule[1]);
		}
		$oVoteRule = $oVoteRule[1];

		$voteSchemas = array_filter($oApp->dynaDataSchemas, function ($oSchema) use ($oVoteRule) {return in_array($oSchema->id, $oVoteRule->schemas);});
		if (empty($voteSchemas)) {
			return new \ObjectNotFoundError('（3）投票任务参数错误');
		}

		$oUser = $this->getUser($oApp);

		$modelRecDat = $this->model('matter\enroll\data');
		$oResult = new \stdClass;

		foreach ($voteSchemas as $oSchema) {
			if ($oSchema->type === 'multitext') {
				/* 对答案投票 */
			} else {
				$oDataResult = $modelRecDat->byApp($oApp, $oUser, ['schemas' => [$oSchema->id]]);
				foreach ($oDataResult->records as $oRecData) {
					$oVotingRecData = new \stdClass;
					$oVoteResult = new \stdClass;
					$vote_at = (int) $modelRecDat->query_val_ss(['vote_at', 'xxt_enroll_vote', ['rid' => $oActiveRnd->rid, 'data_id' => $oRecData->id, 'state' => 1, 'userid' => $oUser->uid]]);
					$oVoteResult->vote_at = $vote_at;
					$oVoteResult->vote_num = $oRecData->vote_num;
					$oVoteResult->state = $oVoteRule->state;
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
	public function vote_action($data, $task) {
		$modelRecDat = $this->model('matter\enroll\data');
		$oRecData = $modelRecDat->byId($data, ['fields' => 'id,aid,rid,enroll_key,state,multitext_seq,userid,nickname']);
		if (false === $oRecData || $oRecData->state !== '1') {
			return new \ObjectNotFoundError('（1）指定的对象不存在或不可用');
		}

		$oApp = $this->model('matter\enroll')->byId($oRecData->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError('（2）指定的对象不存在或不可用');
		}

		$modelTsk = $this->model('matter\enroll\task', $oApp);
		$oTask = $modelTsk->byId($task);
		if (false === $oTask || $oTask->config_type !== 'vote') {
			return new \ObjectNotFoundError('没有找到匹配的投票任务');
		}

		$oUser = $this->getUser($oApp);

		$aVoteResult = $modelRecDat->vote($oApp, $oTask, $oRecData->id, $oUser);
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
	public function unvote_action($data, $task) {
		$modelRecDat = $this->model('matter\enroll\data');
		$oRecData = $modelRecDat->byId($data, ['fields' => 'id,aid,rid,enroll_key,state,multitext_seq,userid,nickname']);
		if (false === $oRecData || $oRecData->state !== '1') {
			return new \ObjectNotFoundError('（1）指定的对象不存在或不可用');
		}

		$oApp = $this->model('matter\enroll')->byId($oRecData->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError('（2）指定的对象不存在或不可用');
		}

		$modelTsk = $this->model('matter\enroll\task', $oApp);
		$oTask = $modelTsk->byId($task);
		if (false === $oTask || $oTask->config_type !== 'vote') {
			return new \ObjectNotFoundError('没有找到匹配的投票任务');
		}

		$oUser = $this->getUser($oApp);

		$aVoteResult = $modelRecDat->unvote($oApp, $oTask, $oRecData->id, $oUser);
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
}