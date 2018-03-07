<?php
namespace matter\enroll;
/**
 * 登记活动用户事件
 */
class event_model extends \TMS_MODEL {
	/**
	 * 进入应用
	 */
	const EnterEventName = 'site.matter.enroll.read';
	/**
	 * 提交记录事件名称
	 */
	const SubmitEventName = 'site.matter.enroll.submit';
	/**
	 * 用户A填写数据被点评
	 */
	const RemarkEventName = 'site.matter.enroll.data.comment';
	/**
	 * 用户A点评别人的填写数据
	 */
	const RemarkOtherEventName = 'site.matter.enroll.data.other.comment';
	/**
	 * 用户A填写数据被赞同
	 */
	const LikeEventName = 'site.matter.enroll.data.like';
	/**
	 * 用户A赞同别人的填写数据
	 */
	const LikeOtherEventName = 'site.matter.enroll.data.other.like';
	/**
	 * 用户A评论被赞同
	 */
	const LikeRemarkEventName = 'site.matter.enroll.remark.like';
	/**
	 * 用户A赞同别人的评论
	 */
	const LikeRemarkOtherEventName = 'site.matter.enroll.remark.other.like';
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
	 * 更新用户汇总数据
	 */
	private function _updateUsrData($oApp, $rid, $bJumpCreate, $oUser, $oUsrEventData, $oUsrEnlData = null, $oUsrMisData = null) {
		$userid = $this->_getOperatorId($oUser);

		/* 登记活动中需要额外更新的数据 */
		$oUpdatedEnlUsrData = clone $oUsrEventData;
		if (isset($oUsrEnlData)) {
			foreach ($oUsrEnlData as $k => $v) {
				$oUpdatedEnlUsrData->{$k} = $v;
			}
		}
		if (isset($oUser->group_id)) {
			$oUpdatedEnlUsrData->group_id = $oUser->group_id;
		}

		/* 更新发起评论的活动用户轮次数据 */
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
		$oEnlUsrRnd = $modelUsr->byId($oApp, $userid, ['fields' => '*', 'rid' => $rid]);
		if (false === $oEnlUsrRnd) {
			if (!$bJumpCreate) {
				$oUpdatedEnlUsrData->rid = $rid;
				$modelUsr->add($oApp, $oUser, $oUpdatedEnlUsrData);
			}
		} else {
			$modelUsr->modify($oEnlUsrRnd, $oUpdatedEnlUsrData);
		}
		$oEnlUsrApp = $modelUsr->byId($oApp, $userid, ['fields' => '*', 'rid' => 'ALL']);
		if (false === $oEnlUsrApp) {
			if (!$bJumpCreate) {
				$oUpdatedEnlUsrData->rid = 'ALL';
				$modelUsr->add($oApp, $oUser, $oUpdatedEnlUsrData);
			}
		} else {
			$modelUsr->modify($oEnlUsrApp, $oUpdatedEnlUsrData);
		}

		/* 更新项目用户数据 */
		if (!empty($oApp->mission_id)) {
			$modelMisUsr = $this->model('matter\mission\user')->setOnlyWriteDbConn(true);
			/* 项目中需要额外更新的数据 */
			$oUpdatedMisUsrData = clone $oUsrEventData;
			if (isset($oUsrMisData)) {
				foreach ($oUsrMisData as $k => $v) {
					$oUpdatedMisUsrData->{$k} = $v;
				}
			}
			$oMission = $this->model('matter\mission')->byId($oApp->mission_id, ['fields' => 'siteid,id,user_app_type,user_app_id']);
			$oMisUser = $modelMisUsr->byId($oMission, $userid, ['fields' => '*']);
			/* 用户在项目中的所属分组 */
			if ($oMission->user_app_type === 'group') {
				$oMisUsrGrpApp = (object) ['id' => $oMission->user_app_id];
				$oMisGrpUser = $this->model('matter\group\player')->byUser($oMisUsrGrpApp, $oUser->uid, ['onlyOne' => true, 'round_id']);
				if (isset($oMisGrpUser->round_id) && $oMisUser->group_id !== $oMisGrpUser->round_id) {
					$oUpdatedMisUsrData->group_id = $oMisGrpUser->round_id;
				}
			}
			if (false === $oMisUser) {
				if (!$bJumpCreate) {
					$modelMisUsr->add($oMission, $oUser, $oUpdatedMisUsrData);
				}
			} else {
				$modelMisUsr->modify($oMisUser, $oUpdatedMisUsrData);
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
		$oEnlUsrRnd = $modelUsr->byId($oApp, $oUser->uid, ['fields' => 'id,state,nickname,group_id,last_enroll_at,enroll_num,user_total_coin', 'rid' => $oRecord->rid]);
		if (false === $oEnlUsrRnd) {
			$oUpdatedEnlUsrData->rid = $oRecord->rid;
			$modelUsr->add($oApp, $oUser, $oUpdatedEnlUsrData);
		} else {
			$modelUsr->modify($oEnlUsrRnd, $oUpdatedEnlUsrData);
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
	 * 评论填写记录
	 */
	public function remarkRecord($oApp, $oRecordOrData, $oOperator) {
		$this->_remarkRecordOrData($oApp, $oRecordOrData, $oOperator, 'record');
		$this->_beRemarkedRecordOrData($oApp, $oRecordOrData, $oOperator, 'record');
	}
	/**
	 * 评论填写数据
	 */
	public function remarkRecData($oApp, $oRecordOrData, $oOperator) {
		$this->_remarkRecordOrData($oApp, $oRecordOrData, $oOperator, 'record.data');
		$this->_beRemarkedRecordOrData($oApp, $oRecordOrData, $oOperator, 'record.data');
	}
	/**
	 * 评论填写记录或数据
	 */
	private function _remarkRecordOrData($oApp, $oRecordOrData, $oOperator, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::RemarkOtherEventName;
		$oNewModifyLog->args = (object) ['id' => $oRecordOrData->id, 'type' => $logArgType];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_remark_other_at = $current;
		$oUpdatedUsrData->remark_other_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		$aCoinResult = $modelUsr->awardCoin($oApp, $operatorId, $oRecordOrData->rid, self::RemarkOtherEventName);
		if ($aCoinResult[0] === true) {
			$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
		}

		return $this->_updateUsrData($oApp, $oRecordOrData->rid, false, $oOperator, $oUpdatedUsrData);
	}
	/**
	 * 填写记录或数据被评论
	 */
	private function _beRemarkedRecordOrData($oApp, $oRecordOrData, $oOperator, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::RemarkEventName;
		$oNewModifyLog->args = (object) ['id' => $oRecordOrData->id, 'type' => $logArgType];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_remark_at = $current;
		$oUpdatedUsrData->remark_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		$aCoinResult = $modelUsr->awardCoin($oApp, $operatorId, $oRecordOrData->rid, self::RemarkEventName);
		if ($aCoinResult[0] === true) {
			$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
		}

		$oUser = (object) ['uid' => $oRecordOrData->userid];

		return $this->_updateUsrData($oApp, $oRecordOrData->rid, true, $oUser, $oUpdatedUsrData);
	}
	/**
	 * 对记录执行推荐相关操作
	 */
	public function recommendRecord($oApp, $oRecord, $oOperator, $value) {
		$rst = null;
		if ('Y' === $value) {
			$rst = $this->_agreeRecordOrData($oApp, $oRecData, $oOperator, 'record');
		} else if ('Y' === $oRecord->agreed) {
			$rst = $this->_undoAgreeRecordOrData($oApp, $oRecord, $oOperator, $value, 'record');
		}

		return $rst;
	}
	/**
	 * 对记录数据执行推荐相关操作
	 */
	public function recommendRecordData($oApp, $oRecData, $oOperator, $value) {
		$rst = null;
		if ('Y' === $value) {
			$rst = $this->_agreeRecordOrData($oApp, $oRecData, $oOperator, 'record.data');
		} else if ('Y' === $oRecData->agreed) {
			$rst = $this->_undoAgreeRecordOrData($oApp, $oRecData, $oOperator, $value, 'record.data');
		}

		return $rst;
	}
	/**
	 * 赞同填写记录或数据
	 */
	private function _agreeRecordOrData($oApp, $oRecordOrData, $oOperator, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
		$oEnlUsrRnd = $modelUsr->byId($oApp, $oRecordOrData->userid, ['fields' => 'id,nickname,last_recommend_at,recommend_num,user_total_coin,modify_log', 'rid' => $oRecordOrData->rid]);
		$oEnlUsrByApp = $modelUsr->byId($oApp, $oRecordOrData->userid, ['fields' => 'id,nickname,last_recommend_at,recommend_num,user_total_coin,modify_log', 'rid' => 'ALL']);
		if ($oEnlUsrRnd && $oEnlUsrByApp) {
			/* 奖励积分 */
			$aCoinResult = $modelUsr->awardCoin($oApp, $oRecordOrData->userid, $oRecordOrData->rid, self::RecommendEventName);
			/* 记录修改日志 */
			$oNewModifyLog = new \stdClass;
			$oNewModifyLog->userid = $operatorId;
			$oNewModifyLog->at = $current;
			$oNewModifyLog->op = self::RecommendEventName . '_Y';
			$oNewModifyLog->args = (object) ['id' => $oRecordOrData->id, 'type' => $logArgType];
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
			$this->_invalidRecDataLastRecommendLog($oEnlUsrRnd->modify_log, $oRecordOrData);
			$modelUsr->modify($oEnlUsrRnd, $oUpdatedData);
			/* 更新用户活动范围的汇总数据 */
			$this->_invalidRecDataLastRecommendLog($oEnlUsrByApp->modify_log, $oRecordOrData);
			$modelUsr->modify($oEnlUsrByApp, $oUpdatedData);
			/* 更新用户在项目中的汇总数据 */
			if (!empty($oApp->mission_id)) {
				$modelMisUsr = $this->model('matter\mission\user')->setOnlyWriteDbConn(true);
				$oMission = (object) ['id' => $oApp->mission_id];
				$oMisUser = $modelMisUsr->byId($oMission, $oRecordOrData->userid, ['fields' => 'id,nickname,last_recommend_at,recommend_num,user_total_coin,modify_log']);
				if ($oMisUser) {
					$this->_invalidRecDataLastRecommendLog($oMisUser->modify_log, $oRecordOrData);
					$modelMisUsr->modify($oMisUser, $oUpdatedData);
				}
			}
		}

		return true;
	}
	/**
	 * 取消赞同记录数据
	 */
	private function _undoAgreeRecordOrData($oApp, $oRecordOrData, $oOperator, $value, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
		/* 取消推荐 */
		$oEnlUsrRnd = $modelUsr->byId($oApp, $oRecordOrData->userid, ['fields' => 'id,nickname,last_recommend_at,recommend_num,user_total_coin,modify_log', 'rid' => $oRecordOrData->rid]);
		$oEnlUsrByApp = $modelUsr->byId($oApp, $oRecordOrData->userid, ['fields' => 'id,nickname,last_recommend_at,recommend_num,user_total_coin,modify_log', 'rid' => 'ALL']);
		if ($oEnlUsrRnd && $oEnlUsrByApp) {
			/* 历史记录 */
			$oBeforeModifyLog = null;
			foreach ($oEnlUsrRnd->modify_log as $oLog) {
				if (isset($oLog->op) && $oLog->op === self::RecommendEventName . '_Y') {
					if (isset($oLog->args->id) && isset($oLog->args->type)) {
						if ($oLog->args->id === $oRecordOrData->id && $oLog->args->type === $logArgType) {
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
			$oNewModifyLog->args = (object) ['id' => $oRecordOrData->id, 'type' => $logArgType];
			/* 更新的数据 */
			$oUpdatedData = (object) [
				'recommend_num' => -1,
				'user_total_coin' => empty($oBeforeModifyLog->coin) ? 0 : -1 * (int) $oBeforeModifyLog->coin,
				'modify_log' => $oNewModifyLog,
			];
			/* 更新用户当前轮次的汇总数据 */
			if (isset($oBeforeModifyLog)) {
				$this->_invalidRecDataLastRecommendLog($oEnlUsrRnd->modify_log, $oRecordOrData);
				$oUpdatedData->last_recommend_at = $this->_getRecDataAgreedLastAt($oEnlUsrRnd->modify_log, $oRecordOrData);
			}
			$modelUsr->modify($oEnlUsrRnd, $oUpdatedData);
			/* 更新用户活动范围的汇总数据 */
			if (isset($oBeforeModifyLog)) {
				$this->_invalidRecDataLastRecommendLog($oEnlUsrByApp->modify_log, $oRecordOrData);
				$oUpdatedData->last_recommend_at = $this->_getRecDataAgreedLastAt($oEnlUsrByApp->modify_log, $oRecordOrData);
			}
			$modelUsr->modify($oEnlUsrByApp, $oUpdatedData);
			/* 更新项目用户数据 */
			if (!empty($oApp->mission_id)) {
				$modelMisUsr = $this->model('matter\mission\user')->setOnlyWriteDbConn(true);
				$oMission = (object) ['id' => $oApp->mission_id];
				$oMisUser = $modelMisUsr->byId($oMission, $oRecordOrData->userid, ['fields' => 'id,nickname,last_recommend_at,recommend_num,user_total_coin,modify_log']);
				if ($oMisUser) {
					if (isset($oBeforeModifyLog)) {
						$this->_invalidRecDataLastRecommendLog($oMisUser->modify_log, $oRecordOrData);
						$oUpdatedData->last_recommend_at = $this->_getRecDataAgreedLastAt($oMisUser->modify_log, $oRecordOrData);
					}
					$modelMisUsr->modify($oMisUser, $oUpdatedData);
				}
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
		$oNewModifyLog->op = self::LikeOtherEventName . '_Y';
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
				if (strpos($oLog->op, self::LikeOtherEventName) === 0) {
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
			$aCoinResult = $modelUsr->awardCoin($oApp, $operatorId, $oRecordOrData->rid, self::LikeOtherEventName);
			if ($aCoinResult[0] === true) {
				$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
			}
		}

		return $this->_updateUsrData($oApp, $oRecordOrData->rid, false, $oOperator, $oUpdatedUsrData);
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
		$oUser = (object) ['uid' => $oRecordOrData->userid];

		return $this->_updateUsrData($oApp, $oRecordOrData->rid, true, $oOperator, $oUpdatedUsrData);
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

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = time();
		$oNewModifyLog->op = self::LikeOtherEventName . '_N';
		$oNewModifyLog->args = (object) ['id' => $oRecordOrData->id, 'type' => $logArgType];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->like_other_num = -1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		return $this->_updateUsrData($oApp, $oRecordOrData->rid, true, $oOperator, $oUpdatedUsrData);
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

		/* 撤销获得的积分 */
		$oEnlUsrRnd = $modelUsr->byId($oApp, $oRecordOrData->userid, ['fields' => 'id,modify_log', 'rid' => $oRecordOrData->rid]);
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

		$oUser = (object) ['uid' => $oRecordOrData->userid];

		return $this->_updateUsrData($oApp, $oRecordOrData->rid, true, $oUser, $oUpdatedUsrData);
	}
	/**
	 * 评论点赞
	 * 同一条评论只有第一次点赞时才给积分奖励
	 */
	public function likeRemark($oApp, $oRemark, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::LikeRemarkOtherEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRemark->id];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_like_other_remark_at = $current;
		$oUpdatedUsrData->like_other_remark_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		/* 如果是第一次点赞给积分 */
		$bFirstLike = true;
		$oEnlUsrRnd = $modelUsr->byId($oApp, $operatorId, ['fields' => 'id,nickname,last_like_other_remark_at,like_other_remark_num,user_total_coin,modify_log', 'rid' => $oRemark->rid]);
		if ($oEnlUsrRnd && count($oEnlUsrRnd->modify_log)) {
			for ($i = count($oEnlUsrRnd->modify_log) - 1; $i >= 0; $i--) {
				$oLog = $oEnlUsrRnd->modify_log[$i];
				if (strpos($oLog->op, self::LikeRemarkOtherEventName) === 0) {
					if (isset($oNewModifyLog->args->id) && $oNewModifyLog->args->id === $oRemark->id) {
						$bFirstLike = false;
						break;
					}
				}
			}
		}
		if ($bFirstLike) {
			$aCoinResult = $modelUsr->awardCoin($oApp, $operatorId, $oRemark->rid, self::LikeRemarkOtherEventName);
			if ($aCoinResult[0] === true) {
				$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
			}
		}

		return $this->_updateUsrData($oApp, $oRemark->rid, false, $oOperator, $oUpdatedUsrData);
	}
	/**
	 * 评论被点赞
	 */
	public function beLikedRemark($oApp, $oRemark, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oRemark->userid;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::LikeRemarkEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRemark->id, 'operator' => $operatorId];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_like_remark_at = $current;
		$oUpdatedUsrData->like_remark_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;
		$aCoinResult = $modelUsr->awardCoin($oApp, $oRemark->userid, $oRemark->rid, self::LikeRemarkEventName);
		if ($aCoinResult[0] === true) {
			$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
		}

		$oUser = (object) ['uid' => $oRemark->userid];

		return $this->_updateUsrData($oApp, $oRemark->rid, true, $oUser, $oUpdatedUsrData);
	}
	/**
	 * 撤销发起对评论点赞
	 */
	public function undoLikeRemark($oApp, $oRemark, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::LikeRemarkOtherEventName . '_N';
		$oNewModifyLog->args = (object) ['id' => $oRemark->id];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->like_other_remark_num = -1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		return $this->_updateUsrData($oApp, $oRemark->rid, true, $oOperator, $oUpdatedUsrData);
	}
	/**
	 * 撤销评论被点赞
	 */
	public function undoBeLikedRemark($oApp, $oRemark, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oRemark->userid;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::LikeRemarkEventName . '_N';
		$oNewModifyLog->args = (object) ['id' => $oRemark->id, 'operator' => $operatorId];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->like_remark_num = -1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		$oEnlUsrRnd = $modelUsr->byId($oApp, $oRemark->userid, ['fields' => 'id,modify_log', 'rid' => $oRemark->rid]);
		/* 撤销获得的积分 */
		if ($oEnlUsrRnd && count($oEnlUsrRnd->modify_log)) {
			for ($i = count($oEnlUsrRnd->modify_log) - 1; $i >= 0; $i--) {
				$oLog = $oEnlUsrRnd->modify_log[$i];
				if ($oLog->op === self::LikeRemarkEventName . '_Y') {
					if (isset($oLog->args->id) && isset($oLog->args->operator)) {
						if ($oLog->args->id === $oRemark->id && $oLog->args->operator === $operatorId) {
							if (!empty($oLog->coin)) {
								$oUpdatedUsrData->user_total_coin = -1 * (int) $oLog->coin;
							}
							break;
						}
					}
				}
			}
		}

		$oUser = (object) ['uid' => $oRemark->userid];

		return $this->_updateUsrData($oApp, $oRemark->rid, true, $oUser, $oUpdatedUsrData);
	}
}