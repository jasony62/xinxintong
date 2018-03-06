<?php
namespace matter\enroll;
/**
 * 登记活动用户事件
 */
class event_model extends \TMS_MODEL {
	/**
	 * 提交记录事件名称
	 */
	const SubmitEventName = 'site.matter.enroll.submit';
	/**
	 * 用户A填写数据被赞同
	 */
	const LikeEventName = 'site.matter.enroll.data.like';
	/**
	 * 用户A赞同别人的填写数据
	 */
	const OtherLikeEventName = 'site.matter.enroll.data.other.like';
	/**
	 * 推荐事件名称
	 */
	const RecommendEventName = 'site.matter.enroll.data.recommend';
	/**
	 *
	 */
	private function _getOperatorId($oOperator) {
		$operatorId = isset($oOperator->uid) ? $oOperator->uid : (isset($oOperator->userid) ? $oOperator->userid : (isset($oOperator->id) ? $oOperator->id : ''));
		return $operatorId;
	}
	/**
	 * 获得日志中最后一次推荐的时间
	 */
	private function _getRecAgreedLastAt($oModifyLog, $oIgnoredRecord = null) {
		$lastAt = 0;
		if (empty($oModifyLog)) {
			return $lastAt;
		}

		for ($i = count($oModifyLog) - 1; $i >= 0; $i--) {
			$oOnceLog = $oModifyLog[$i];
			if (isset($oOnceLog->valid) && $oOnceLog->valid === 'N') {
				continue;
			}
			if ($oOnceLog->op === self::RecommendEventName . '_Y') {
				if (isset($oIgnoredRecord)) {
					if (isset($oOnceLog->args->type) && $oOnceLog->args->type === 'record') {
						if (isset($oOnceLog->args->id) && $oOnceLog->args->id === $oIgnoredRecord->id) {
							continue;
						}
					}
				}
				return $oOnceLog->at;
			}
		}

		return $lastAt;
	}
	/**
	 * 增加新日志前，使操作对象的上一条日志失效
	 */
	private function _invalidRecLastRecommendLog($oModifyLog, $oRecord) {
		$oLastestLog = null;
		for ($i = count($oModifyLog) - 1; $i >= 0; $i--) {
			$oOnceLog = $oModifyLog[$i];
			if (0 === strpos($oOnceLog->op, self::RecommendEventName)) {
				if (isset($oOnceLog->args->type) && $oOnceLog->args->type === 'record') {
					if (isset($oOnceLog->args->id) && $oOnceLog->args->id === $oRecord->id) {
						$oLastestLog = $oOnceLog;
						break;
					}
				}
			}
		}
		if (isset($oLastestLog)) {
			$oLastestLog->valid = 'N';
		}

		return $oLastestLog;
	}
	/**
	 * 获得日志中最后一次推荐的时间
	 */
	private function _getRecDataAgreedLastAt($oModifyLog, $oIgnoredRecData = null) {
		$lastAt = 0;
		if (empty($oModifyLog)) {
			return $lastAt;
		}

		for ($i = count($oModifyLog) - 1; $i >= 0; $i--) {
			$oOnceLog = $oModifyLog[$i];
			if (isset($oOnceLog->valid) && $oOnceLog->valid === 'N') {
				continue;
			}
			if ($oOnceLog->op === self::RecommendEventName . '_Y') {
				if (isset($oIgnoredRecData)) {
					if (isset($oOnceLog->args->type) && $oOnceLog->args->type === 'record.data') {
						if (isset($oOnceLog->args->id) && $oOnceLog->args->id === $oIgnoredRecData->id) {
							continue;
						}
					}
				}
				return $oOnceLog->at;
			}
		}

		return $lastAt;
	}
	/**
	 * 增加新日志前，使操作对象的上一条日志失效
	 */
	private function _invalidRecDataLastRecommendLog($oModifyLog, $oRecord) {
		$oLastestLog = null;
		for ($i = count($oModifyLog) - 1; $i >= 0; $i--) {
			$oOnceLog = $oModifyLog[$i];
			if (0 === strpos($oOnceLog->op, self::RecommendEventName)) {
				if (isset($oOnceLog->args->type) && $oOnceLog->args->type === 'record.data') {
					if (isset($oOnceLog->args->id) && $oOnceLog->args->id === $oRecord->id) {
						$oLastestLog = $oOnceLog;
						break;
					}
				}
			}
		}
		if (isset($oLastestLog)) {
			$oLastestLog->valid = 'N';
		}

		return $oLastestLog;
	}
	/**
	 * 对记录执行推荐相关操作
	 */
	public function recommendRecord($oApp, $oRecord, $oOperator, $value) {
		$rst = null;
		if ('Y' === $value) {
			$rst = $this->_agreeRecord($oApp, $oRecord, $oOperator);
		} else if ('Y' === $oRecord->agreed) {
			$rst = $this->_undoAgreeRecord($oApp, $oRecord, $oOperator, $value);
		}

		return $rst;
	}
	/**
	 * 赞同记录
	 */
	private function _agreeRecord($oApp, $oRecord, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
		$oEnlUsrByRnd = $modelUsr->byId($oApp, $oRecord->userid, ['fields' => 'id,nickname,last_recommend_at,recommend_num,user_total_coin,modify_log', 'rid' => $oRecord->rid]);
		$oEnlUsrByApp = $modelUsr->byId($oApp, $oRecord->userid, ['fields' => 'id,nickname,last_recommend_at,recommend_num,user_total_coin,modify_log', 'rid' => 'ALL']);
		if ($oEnlUsrByRnd && $oEnlUsrByApp) {
			/* 奖励积分 */
			$aCoinResult = $modelUsr->awardCoin($oApp, $oRecord->userid, $oRecord->rid, self::RecommendEventName);
			/* 记录修改日志 */
			$oNewModifyLog = new \stdClass;
			$oNewModifyLog->userid = $operatorId;
			$oNewModifyLog->at = $current;
			$oNewModifyLog->op = self::RecommendEventName . '_Y';
			$oNewModifyLog->args = (object) ['id' => $oRecord->id, 'type' => 'record'];
			if ($aCoinResult[0] === true) {
				$oNewModifyLog->coin = $aCoinResult[1];
			}
			/* 更新的数据 */
			$oUpdatedData = (object) [
				'last_recommend_at' => $current,
				'recommend_num' => 1,
				'user_total_coin' => $aCoinResult[0] === true ? $aCoinResult[1] : 0,
				'modify_log' => $oNewModifyLog,
			];
			/* 更新用户当前轮次的汇总数据 */
			$this->_invalidRecLastRecommendLog($oEnlUsrByRnd->modify_log, $oRecord);
			$modelUsr->modify($oEnlUsrByRnd, $oUpdatedData);
			/* 更新用户活动范围的汇总数据 */
			$this->_invalidRecLastRecommendLog($oEnlUsrByApp->modify_log, $oRecord);
			$modelUsr->modify($oEnlUsrByApp, $oUpdatedData);
			/* 更新用户在项目中的汇总数据 */
			if (!empty($oApp->mission_id)) {
				$modelMisUsr = $this->model('matter\mission\user')->setOnlyWriteDbConn(true);
				$oMission = (object) ['id' => $oApp->mission_id];
				$oMisUser = $modelMisUsr->byId($oMission, $oRecord->userid, ['fields' => 'id,nickname,last_recommend_at,recommend_num,user_total_coin,modify_log']);
				if ($oMisUser) {
					$this->_invalidRecLastRecommendLog($oMisUser->modify_log, $oRecord);
					$modelMisUsr->modify($oMisUser, $oUpdatedData);
				}
			}
		}

		return true;
	}
	/**
	 * 取消赞同记录
	 */
	private function _undoAgreeRecord($oApp, $oRecord, $oOperator, $value) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
		/* 取消推荐 */
		$oEnlUsrByRnd = $modelUsr->byId($oApp, $oRecord->userid, ['fields' => 'id,nickname,last_recommend_at,recommend_num,user_total_coin,modify_log', 'rid' => $oRecord->rid]);
		$oEnlUsrByApp = $modelUsr->byId($oApp, $oRecord->userid, ['fields' => 'id,nickname,last_recommend_at,recommend_num,user_total_coin,modify_log', 'rid' => 'ALL']);
		if ($oEnlUsrByRnd && $oEnlUsrByApp) {
			/* 历史记录 */
			$oBeforeModifyLog = null;
			foreach ($oEnlUsrByRnd->modify_log as $oLog) {
				if (isset($oLog->op) && $oLog->op === self::RecommendEventName . '_Y') {
					if (isset($oLog->args->id) && isset($oLog->args->type)) {
						if ($oLog->args->id === $oRecord->id && $oLog->args->type === 'record') {
							$oBeforeModifyLog = $oLog;
							break;
						}
					}
				}
			}
			/* 记录修改日志 */
			$oNewModifyLog = new \stdClass;
			$oNewModifyLog->userid = $operatorId;
			$oNewModifyLog->at = $current;
			$oNewModifyLog->op = self::RecommendEventName . '_' . $value;
			$oNewModifyLog->args = (object) ['id' => $oRecord->id, 'type' => 'record'];
			/* 更新的数据 */
			$oUpdatedData = (object) [
				'recommend_num' => -1,
				'user_total_coin' => empty($oBeforeModifyLog->coin) ? 0 : -1 * (int) $oBeforeModifyLog->coin,
				'modify_log' => $oNewModifyLog,
			];
			/* 更新用户当前轮次的汇总数据 */
			if (isset($oBeforeModifyLog)) {
				$this->_invalidRecLastRecommendLog($oEnlUsrByRnd->modify_log, $oRecord);
				$oUpdatedData->last_recommend_at = $this->_getRecAgreedLastAt($oEnlUsrByRnd->modify_log, $oRecord);
			}
			$modelUsr->modify($oEnlUsrByRnd, $oUpdatedData);
			/* 更新用户活动范围的汇总数据 */
			if (isset($oBeforeModifyLog)) {
				$this->_invalidRecLastRecommendLog($oEnlUsrByApp->modify_log, $oRecord);
				$oUpdatedData->last_recommend_at = $this->_getRecAgreedLastAt($oEnlUsrByApp->modify_log, $oRecord);
			}
			$modelUsr->modify($oEnlUsrByApp, $oUpdatedData);
			/* 更新项目用户数据 */
			if (!empty($oApp->mission_id)) {
				$modelMisUsr = $this->model('matter\mission\user')->setOnlyWriteDbConn(true);
				$oMission = (object) ['id' => $oApp->mission_id];
				$oMisUser = $modelMisUsr->byId($oMission, $oRecord->userid, ['fields' => 'id,nickname,last_recommend_at,recommend_num,user_total_coin,modify_log']);
				if ($oMisUser) {
					if (isset($oBeforeModifyLog)) {
						$this->_invalidRecLastRecommendLog($oMisUser->modify_log, $oRecord);
						$oUpdatedData->last_recommend_at = $this->_getRecAgreedLastAt($oMisUser->modify_log, $oRecord);
					}
					$modelMisUsr->modify($oMisUser, $oUpdatedData);
				}
			}
		}

		return true;
	}
	/**
	 * 对记录数据执行推荐相关操作
	 */
	public function recommendRecordData($oApp, $oRecData, $oOperator, $value) {
		$rst = null;
		if ('Y' === $value) {
			$rst = $this->_agreeRecordData($oApp, $oRecData, $oOperator);
		} else if ('Y' === $oRecData->agreed) {
			$rst = $this->_undoAgreeRecordData($oApp, $oRecData, $oOperator, $value);
		}

		return $rst;
	}
	/**
	 * 赞同记录数据
	 */
	private function _agreeRecordData($oApp, $oRecData, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
		$oEnlUsrByRnd = $modelUsr->byId($oApp, $oRecData->userid, ['fields' => 'id,nickname,last_recommend_at,recommend_num,user_total_coin,modify_log', 'rid' => $oRecData->rid]);
		$oEnlUsrByApp = $modelUsr->byId($oApp, $oRecData->userid, ['fields' => 'id,nickname,last_recommend_at,recommend_num,user_total_coin,modify_log', 'rid' => 'ALL']);
		if ($oEnlUsrByRnd && $oEnlUsrByApp) {
			/* 奖励积分 */
			$aCoinResult = $modelUsr->awardCoin($oApp, $oRecData->userid, $oRecData->rid, self::RecommendEventName);
			/* 记录修改日志 */
			$oNewModifyLog = new \stdClass;
			$oNewModifyLog->userid = $operatorId;
			$oNewModifyLog->at = $current;
			$oNewModifyLog->op = self::RecommendEventName . '_Y';
			$oNewModifyLog->args = (object) ['id' => $oRecData->id, 'type' => 'record.data'];
			if ($aCoinResult[0] === true) {
				$oNewModifyLog->coin = $aCoinResult[1];
			}
			/* 更新的数据 */
			$oUpdatedData = (object) [
				'last_recommend_at' => $current,
				'recommend_num' => 1,
				'user_total_coin' => $aCoinResult[0] === true ? $aCoinResult[1] : 0,
				'modify_log' => $oNewModifyLog,
			];
			/* 更新用户当前轮次的汇总数据 */
			$this->_invalidRecDataLastRecommendLog($oEnlUsrByRnd->modify_log, $oRecData);
			$modelUsr->modify($oEnlUsrByRnd, $oUpdatedData);
			/* 更新用户活动范围的汇总数据 */
			$this->_invalidRecDataLastRecommendLog($oEnlUsrByApp->modify_log, $oRecData);
			$modelUsr->modify($oEnlUsrByApp, $oUpdatedData);
			/* 更新用户在项目中的汇总数据 */
			if (!empty($oApp->mission_id)) {
				$modelMisUsr = $this->model('matter\mission\user')->setOnlyWriteDbConn(true);
				$oMission = (object) ['id' => $oApp->mission_id];
				$oMisUser = $modelMisUsr->byId($oMission, $oRecData->userid, ['fields' => 'id,nickname,last_recommend_at,recommend_num,user_total_coin,modify_log']);
				if ($oMisUser) {
					$this->_invalidRecDataLastRecommendLog($oMisUser->modify_log, $oRecData);
					$modelMisUsr->modify($oMisUser, $oUpdatedData);
				}
			}
		}

		return true;
	}
	/**
	 * 取消赞同记录数据
	 */
	private function _undoAgreeRecordData($oApp, $oRecData, $oOperator, $value) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
		/* 取消推荐 */
		$oEnlUsrByRnd = $modelUsr->byId($oApp, $oRecData->userid, ['fields' => 'id,nickname,last_recommend_at,recommend_num,user_total_coin,modify_log', 'rid' => $oRecData->rid]);
		$oEnlUsrByApp = $modelUsr->byId($oApp, $oRecData->userid, ['fields' => 'id,nickname,last_recommend_at,recommend_num,user_total_coin,modify_log', 'rid' => 'ALL']);
		if ($oEnlUsrByRnd && $oEnlUsrByApp) {
			/* 历史记录 */
			$oBeforeModifyLog = null;
			foreach ($oEnlUsrByRnd->modify_log as $oLog) {
				if (isset($oLog->op) && $oLog->op === self::RecommendEventName . '_Y') {
					if (isset($oLog->args->id) && isset($oLog->args->type)) {
						if ($oLog->args->id === $oRecData->id && $oLog->args->type === 'record.data') {
							$oBeforeModifyLog = $oLog;
							break;
						}
					}
				}
			}
			/* 记录修改日志 */
			$oNewModifyLog = new \stdClass;
			$oNewModifyLog->userid = $operatorId;
			$oNewModifyLog->at = $current;
			$oNewModifyLog->op = self::RecommendEventName . '_' . $value;
			$oNewModifyLog->args = (object) ['id' => $oRecData->id, 'type' => 'record.data'];
			/* 更新的数据 */
			$oUpdatedData = (object) [
				'recommend_num' => -1,
				'user_total_coin' => empty($oBeforeModifyLog->coin) ? 0 : -1 * (int) $oBeforeModifyLog->coin,
				'modify_log' => $oNewModifyLog,
			];
			/* 更新用户当前轮次的汇总数据 */
			if (isset($oBeforeModifyLog)) {
				$this->_invalidRecDataLastRecommendLog($oEnlUsrByRnd->modify_log, $oRecData);
				$oUpdatedData->last_recommend_at = $this->_getRecDataAgreedLastAt($oEnlUsrByRnd->modify_log, $oRecData);
			}
			$modelUsr->modify($oEnlUsrByRnd, $oUpdatedData);
			/* 更新用户活动范围的汇总数据 */
			if (isset($oBeforeModifyLog)) {
				$this->_invalidRecDataLastRecommendLog($oEnlUsrByApp->modify_log, $oRecData);
				$oUpdatedData->last_recommend_at = $this->_getRecDataAgreedLastAt($oEnlUsrByApp->modify_log, $oRecData);
			}
			$modelUsr->modify($oEnlUsrByApp, $oUpdatedData);
			/* 更新项目用户数据 */
			if (!empty($oApp->mission_id)) {
				$modelMisUsr = $this->model('matter\mission\user')->setOnlyWriteDbConn(true);
				$oMission = (object) ['id' => $oApp->mission_id];
				$oMisUser = $modelMisUsr->byId($oMission, $oRecData->userid, ['fields' => 'id,nickname,last_recommend_at,recommend_num,user_total_coin,modify_log']);
				if ($oMisUser) {
					if (isset($oBeforeModifyLog)) {
						$this->_invalidRecDataLastRecommendLog($oMisUser->modify_log, $oRecData);
						$oUpdatedData->last_recommend_at = $this->_getRecDataAgreedLastAt($oMisUser->modify_log, $oRecData);
					}
					$modelMisUsr->modify($oMisUser, $oUpdatedData);
				}
			}
		}

		return true;
	}
	/**
	 * 用户提交记录
	 */
	public function submitRecord($oApp, $oRecord, $oUser, $bSubmitNewRecord) {
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oUser->uid;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::SubmitEventName;
		$oNewModifyLog->args = (object) ['id' => $oRecord->id];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->nickname = $this->escape($oUser->nickname);
		$oUpdatedUsrData->last_enroll_at = $current;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		/* 提交新记录 */
		if (true === $bSubmitNewRecord) {
			$oNewModifyLog->op .= '_New';
			/* 提交记录的积分奖励 */
			$aCoinResult = $modelUsr->awardCoin($oApp, $oUser->uid, $oRecord->rid, self::SubmitEventName);
			if ($aCoinResult[0] === true) {
				$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
			}
			$oUpdatedUsrData->enroll_num = 1;
		}

		/* 更新的登记活动用户数据 */
		$oUpdatedEnlUsrData = clone $oUpdatedUsrData;
		if (isset($oUser->group_id)) {
			$oUpdatedEnlUsrData->group_id = $oUser->group_id;
		}
		if (isset($oRecord->score->sum)) {
			$oUpdatedEnlUsrData->score = $oRecord->score->sum;
		}

		/* 用户当前轮次的汇总数据 */
		$oEnlUsrByRnd = $modelUsr->byId($oApp, $oUser->uid, ['fields' => 'id,state,nickname,group_id,last_enroll_at,enroll_num,user_total_coin', 'rid' => $oRecord->rid]);
		if (false === $oEnlUsrByRnd) {
			$oUpdatedEnlUsrData->rid = $oRecord->rid;
			$modelUsr->add($oApp, $oUser, $oUpdatedEnlUsrData);
		} else {
			$modelUsr->modify($oEnlUsrByRnd, $oUpdatedEnlUsrData);
		}

		/* 用户活动范围的汇总数据 */
		$oEnlUsrByApp = $modelUsr->byId($oApp, $oUser->uid, ['fields' => 'id,state,nickname,group_id,last_enroll_at,enroll_num,user_total_coin', 'rid' => 'ALL']);
		if (false === $oEnlUsrByApp) {
			$oUpdatedEnlUsrData->rid = 'ALL';
			$modelUsr->add($oApp, $oUser, $oUpdatedEnlUsrData);
		} else {
			/* 更新用户获得的分数 */
			$sumScore = $modelUsr->query_val_ss([
				'sum(score)',
				'xxt_enroll_user',
				"siteid='$oApp->siteid' and aid='$oApp->id' and userid='$oUser->uid' and state=1 and rid <>'ALL'",
			]);
			$oUpdatedEnlUsrData->score = $sumScore;
			$modelUsr->modify($oEnlUsrByApp, $oUpdatedEnlUsrData);
		}

		/* 更新用户在项目中的汇总数据 */
		if (!empty($oApp->mission_id)) {
			$modelMisUsr = $this->model('matter\mission\user')->setOnlyWriteDbConn(true);
			$oMission = $this->model('matter\mission')->byId($oApp->mission_id, ['fields' => 'siteid,id,user_app_type,user_app_id']);
			if ($oMission->user_app_type === 'group') {
				$oMisUsrGrpApp = (object) ['id' => $oMission->user_app_id];
				$oMisGrpUser = $this->model('matter\group\player')->byUser($oMisUsrGrpApp, $oUser->uid, ['onlyOne' => true, 'round_id']);
				if (isset($oMisGrpUser->round_id)) {
					$oUpdatedUsrData->group_id = $oMisGrpUser->round_id;
				}
			}
			$oMisUser = $modelMisUsr->byId($oMission, $oRecord->userid, ['fields' => 'id,nickname,last_enroll_at,enroll_num,user_total_coin,modify_log']);
			if (false === $oMisUser) {
				$modelUsr->add($oApp, $oUser, $oUpdatedUsrData);
			} else {
				$modelMisUsr->modify($oMisUser, $oUpdatedUsrData);
			}
		}

		return true;
	}
	/**
	 * 填写记录被点赞
	 * 同一条记录只有第一次点赞时才给积分奖励
	 */
	public function likeRecord($oApp, $oRecord, $oOperator) {
		return $this->_likeRecordOrData($oApp, $oRecord, $oOperator, 'record');
	}
	/**
	 * 填写记录数据被点赞
	 */
	public function likeRecData($oApp, $oRecData, $oOperator) {
		return $this->_likeRecordOrData($oApp, $oRecData, $oOperator, 'record.data');
	}
	/**
	 *
	 */
	private function _likeRecordOrData($oApp, $oRecordOrData, $oOperator, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::OtherLikeEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRecordOrData->id, 'type' => $logArgType];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_like_other_at = $current;
		$oUpdatedUsrData->like_other_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		/* 如果是第一次点赞给积分 */
		$bFirstLike = true;
		$oEnlUsrRnd = $modelUsr->byId($oApp, $operatorId, ['fields' => 'id,nickname,last_like_other_at,like_other_num,user_total_coin,modify_log', 'rid' => $oRecordOrData->rid]);
		if ($oEnlUsrRnd && count($oEnlUsrRnd->modify_log)) {
			for ($i = count($oEnlUsrRnd->modify_log) - 1; $i >= 0; $i--) {
				$oLog = $oEnlUsrRnd->modify_log[$i];
				if (strpos($oLog->op, self::OtherLikeEventName) === 0) {
					if (isset($oNewModifyLog->args->id) && isset($oNewModifyLog->args->type)) {
						if ($oNewModifyLog->args->id === $oRecordOrData->id && $oNewModifyLog->args->type === $logArgType) {
							$bFirstLike = false;
							break;
						}
					}
				}
			}
		}
		if ($bFirstLike) {
			$aCoinResult = $modelUsr->awardCoin($oApp, $operatorId, $oRecordOrData->rid, self::OtherLikeEventName);
			if ($aCoinResult[0] === true) {
				$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
			}
		}

		/* 更新活动中的用户的数据 */
		$oUpdatedEnlUsrData = clone $oUpdatedUsrData;
		/* 更新进行点赞的活动用户的轮次数据 */
		if (false === $oEnlUsrRnd) {
			$oUpdatedEnlUsrData->rid = $oRecordOrData->rid;
			$modelUsr->add($oApp, $oOperator, $oUpdatedEnlUsrData);
		} else {
			$modelUsr->modify($oEnlUsrRnd, $oUpdatedEnlUsrData);
		}
		/* 更新进行点赞的活动用户的总数据 */
		$oEnlUsrApp = $modelUsr->byId($oApp, $operatorId, ['fields' => 'id,nickname,last_like_other_at,like_other_num,user_total_coin,modify_log', 'rid' => 'ALL']);
		if (false === $oEnlUsrApp) {
			$oUpdatedEnlUsrData->rid = 'ALL';
			$modelUsr->add($oApp, $oOperator, $oUpdatedEnlUsrData);
		} else {
			$modelUsr->modify($oEnlUsrApp, $oUpdatedEnlUsrData);
		}
		/* 更新项目用户数据 */
		if (!empty($oApp->mission_id)) {
			$modelMisUsr = $this->model('matter\mission\user')->setOnlyWriteDbConn(true);
			$oMission = new \stdClass;
			$oMission->id = $oApp->mission_id;
			$oMisUser = $modelMisUsr->byId($oMission, $operatorId, ['fields' => 'id,nickname,last_like_other_at,like_other_num,user_total_coin,modify_log']);
			if (false === $oMisUser) {
				$modelMisUsr->add($oMission, $oOperator, $oUpdatedUsrData);
			} else {
				$modelMisUsr->modify($oMisUser, $oUpdatedUsrData);
			}
		}

		return true;
	}
	/**
	 * 填写记录被点赞
	 */
	public function beLikedRecord($oApp, $oRecord, $oOperator) {
		return $this->_beLikedRecordOrData($oApp, $oRecord, $oOperator, 'record');
	}
	/**
	 * 填写数据被点赞
	 */
	public function beLikedRecData($oApp, $oRecData, $oOperator) {
		return $this->_beLikedRecordOrData($oApp, $oRecData, $oOperator, 'record.data');
	}
	/**
	 * 填写记录或数据被点赞
	 */
	private function _beLikedRecordOrData($oApp, $oRecordOrData, $oOperator, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oRecordOrData->userid;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::LikeEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRecordOrData->id, 'type' => $logArgType, 'operator' => $operatorId];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_like_at = $current;
		$oUpdatedUsrData->like_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;
		$aCoinResult = $modelUsr->awardCoin($oApp, $oRecordOrData->userid, $oRecordOrData->rid, self::LikeEventName);
		if ($aCoinResult[0] === true) {
			$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
		}

		$oEnlUsrRnd = $modelUsr->byId($oApp, $oRecordOrData->userid, ['fields' => 'id,userid,nickname,last_like_at,like_num,user_total_coin,modify_log', 'rid' => $oRecordOrData->rid]);
		$oEnlUsrApp = $modelUsr->byId($oApp, $oRecordOrData->userid, ['fields' => 'id,userid,nickname,last_like_at,like_num,user_total_coin,modify_log', 'rid' => 'ALL']);
		if ($oEnlUsrRnd && $oEnlUsrApp) {
			$modelUsr->modify($oEnlUsrRnd, $oUpdatedUsrData);
			$modelUsr->modify($oEnlUsrApp, $oUpdatedUsrData);
		}
		if (!empty($oApp->mission_id)) {
			$modelMisUsr = $this->model('matter\mission\user')->setOnlyWriteDbConn(true);
			$oMission = new \stdClass;
			$oMission->id = $oApp->mission_id;
			$oMisUser = $modelMisUsr->byId($oMission, $oRecordOrData->userid, ['fields' => 'id,userid,nickname,last_like_at,like_num,user_total_coin,modify_log']);
			if ($oMisUser) {
				$modelMisUsr->modify($oMisUser, $oUpdatedUsrData);
			}
		}

		return true;
	}
	/**
	 * 撤销填写记录点赞
	 */
	public function undoLikeRecord($oApp, $oRecord, $oOperator) {
		return $this->_undoLikeRecordOrData($oApp, $oRecord, $oOperator, 'record');
	}
	/**
	 * 撤销填写数据点赞
	 */
	public function undoLikeRecData($oApp, $oRecData, $oOperator) {
		return $this->_undoLikeRecordOrData($oApp, $oRecData, $oOperator, 'record.data');
	}
	/**
	 * 撤销点赞
	 */
	private function _undoLikeRecordOrData($oApp, $oRecordOrData, $oOperator, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::OtherLikeEventName . '_N';
		$oNewModifyLog->args = (object) ['id' => $oRecordOrData->id, 'type' => $logArgType];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->like_other_num = -1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		/* 更新进行点赞的活动用户的轮次数据 */
		$oEnlUsrRnd = $modelUsr->byId($oApp, $operatorId, ['fields' => 'id,nickname,last_like_other_at,like_other_num,user_total_coin,modify_log', 'rid' => $oRecordOrData->rid]);
		if ($oEnlUsrRnd) {
			$modelUsr->modify($oEnlUsrRnd, $oUpdatedUsrData);
		}
		/* 更新进行点赞的活动用户的总数据 */
		$oEnlUsrApp = $modelUsr->byId($oApp, $operatorId, ['fields' => 'id,nickname,last_like_other_at,like_other_num,user_total_coin,modify_log', 'rid' => 'ALL']);
		if ($oEnlUsrApp) {
			$modelUsr->modify($oEnlUsrApp, $oUpdatedUsrData);
		}
		/* 更新项目用户数据 */
		if (!empty($oApp->mission_id)) {
			$modelMisUsr = $this->model('matter\mission\user')->setOnlyWriteDbConn(true);
			$oMission = new \stdClass;
			$oMission->id = $oApp->mission_id;
			$oMisUser = $modelMisUsr->byId($oMission, $operatorId, ['fields' => 'id,nickname,last_like_other_at,like_other_num,user_total_coin,modify_log']);
			if ($oMisUser) {
				$modelMisUsr->modify($oMisUser, $oUpdatedUsrData);
			}
		}
	}
	/**
	 * 取消填写记录被点赞
	 */
	public function undoBeLikedRecord($oApp, $oRecord, $oOperator) {
		return $this->_undoBeLikedRecordOrData($oApp, $oRecord, $oOperator, 'record');
	}
	/**
	 * 取消填写数据被点赞
	 */
	public function undoBeLikedRecData($oApp, $oRecData, $oOperator) {
		return $this->_undoBeLikedRecordOrData($oApp, $oRecData, $oOperator, 'record.data');
	}
	/**
	 * 取消被点赞
	 * 取消获得的积分
	 */
	private function _undoBeLikedRecordOrData($oApp, $oRecordOrData, $oOperator, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oRecordOrData->userid;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::LikeEventName . '_N';
		$oNewModifyLog->args = (object) ['id' => $oRecordOrData->id, 'type' => $logArgType, 'operator' => $operatorId];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->like_num = -1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		$oEnlUsrRnd = $modelUsr->byId($oApp, $oRecordOrData->userid, ['fields' => 'id,userid,nickname,last_like_at,like_num,user_total_coin,modify_log', 'rid' => $oRecordOrData->rid]);

		/* 撤销获得的积分 */
		if ($oEnlUsrRnd && count($oEnlUsrRnd->modify_log)) {
			for ($i = count($oEnlUsrRnd->modify_log) - 1; $i >= 0; $i--) {
				$oLog = $oEnlUsrRnd->modify_log[$i];
				if ($oLog->op === self::LikeEventName . '_Y') {
					if (isset($oLog->args->id) && isset($oLog->args->type) && isset($oLog->args->operator)) {
						if ($oLog->args->id === $oRecordOrData->id && $oLog->args->type === $logArgType && $oLog->args->operator === $operatorId) {
							if (!empty($oLog->coin)) {
								$oUpdatedUsrData->user_total_coin = -1 * (int) $oLog->coin;
							}
							break;
						}
					}
				}
			}
		}

		$oEnlUsrApp = $modelUsr->byId($oApp, $oRecordOrData->userid, ['fields' => 'id,userid,nickname,last_like_at,like_num,user_total_coin,modify_log', 'rid' => 'ALL']);
		if ($oEnlUsrRnd && $oEnlUsrApp) {
			$modelUsr->modify($oEnlUsrRnd, $oUpdatedUsrData);
			$modelUsr->modify($oEnlUsrApp, $oUpdatedUsrData);
		}
		if (!empty($oApp->mission_id)) {
			$modelMisUsr = $this->model('matter\mission\user')->setOnlyWriteDbConn(true);
			$oMission = new \stdClass;
			$oMission->id = $oApp->mission_id;
			$oMisUser = $modelMisUsr->byId($oMission, $oRecordOrData->userid, ['fields' => 'id,userid,nickname,last_like_at,like_num,user_total_coin,modify_log']);
			if ($oMisUser) {
				$modelMisUsr->modify($oMisUser, $oUpdatedUsrData);
			}
		}

		return true;
	}
}