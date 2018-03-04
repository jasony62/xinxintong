<?php
namespace matter\enroll;
/**
 * 登记活动用户事件
 */
class event_model extends \TMS_MODEL {
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
}