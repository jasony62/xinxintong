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
	 * 用户A提交的填写记录获得新协作填写数据项
	 */
	const GetSubmitCoworkEventName = 'site.matter.enroll.cowork.get.submit';
	/**
	 * 用户A提交新协作填写记录
	 */
	const DoSubmitCoworkEventName = 'site.matter.enroll.cowork.do.submit';
	/**
	 * 用户A填写数据被点评
	 */
	const GetRemarkEventName = 'site.matter.enroll.data.get.remark';
	/**
	 * 用户A填写的协作数据获得点评
	 */
	const GetRemarkCoworkEventName = 'site.matter.enroll.cowork.get.remark';
	/**
	 * 用户A点评别人的填写数据
	 */
	const DoRemarkEventName = 'site.matter.enroll.data.do.remark';
	/**
	 * 用户A填写数据被赞同
	 */
	const GetLikeEventName = 'site.matter.enroll.data.get.like';
	/**
	 * 用户A赞同别人的填写数据
	 */
	const DoLikeEventName = 'site.matter.enroll.data.do.like';
	/**
	 * 用户A填写数据被赞同
	 */
	const GetLikeCoworkEventName = 'site.matter.enroll.cowork.get.like';
	/**
	 * 用户A赞同别人的填写的协作数据
	 */
	const DoLikeCoworkEventName = 'site.matter.enroll.cowork.do.like';
	/**
	 * 用户A评论被赞同
	 */
	const GetLikeRemarkEventName = 'site.matter.enroll.remark.get.like';
	/**
	 * 用户A赞同别人的评论
	 */
	const DoLikeRemarkEventName = 'site.matter.enroll.remark.do.like';
	/**
	 * 推荐记录事件名称
	 */
	const GetAgreeEventName = 'site.matter.enroll.data.get.agree';
	/**
	 * 推荐评论事件名称
	 */
	const GetAgreeCoworkEventName = 'site.matter.enroll.cowork.get.agree';
	/**
	 * 推荐评论事件名称
	 */
	const GetAgreeRemarkEventName = 'site.matter.enroll.remark.get.agree';
	/**
	 *
	 */
	private function _getOperatorId($oOperator) {
		$operatorId = isset($oOperator->uid) ? $oOperator->uid : (isset($oOperator->userid) ? $oOperator->userid : (isset($oOperator->id) ? $oOperator->id : ''));
		return $operatorId;
	}
	/**
	 * 更新用户汇总数据
	 */
	private function _updateUsrData($oApp, $rid, $bJumpCreate, $oUser, $oUsrEventData, $fnUsrRndData = null, $fnUsrAppData = null, $fnUsrMisData = null) {
		$userid = $this->_getOperatorId($oUser);

		/* 登记活动中需要额外更新的数据 */
		$oUpdatedEnlUsrData = clone $oUsrEventData;
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
			if (isset($fnUsrRndData)) {
				$oResult = $fnUsrRndData($oEnlUsrRnd);
				if ($oResult) {
					$oUpdatedRndUsrData = clone $oUpdatedEnlUsrData;
					foreach ($oResult as $k => $v) {
						$oUpdatedRndUsrData->{$k} = $v;
					}
				}
			}
			if (isset($oUpdatedRndUsrData)) {
				$modelUsr->modify($oEnlUsrRnd, $oUpdatedRndUsrData);
			} else {
				$modelUsr->modify($oEnlUsrRnd, $oUpdatedEnlUsrData);
			}
		}
		$oEnlUsrApp = $modelUsr->byId($oApp, $userid, ['fields' => '*', 'rid' => 'ALL']);
		if (false === $oEnlUsrApp) {
			if (!$bJumpCreate) {
				$oUpdatedEnlUsrData->rid = 'ALL';
				$modelUsr->add($oApp, $oUser, $oUpdatedEnlUsrData);
			}
		} else {
			if (isset($fnUsrAppData)) {
				$oResult = $fnUsrAppData($oEnlUsrApp);
				if ($oResult) {
					$oUpdatedAppUsrData = clone $oUpdatedEnlUsrData;
					foreach ($oResult as $k => $v) {
						$oUpdatedAppUsrData->{$k} = $v;
					}
				}
			}
			if (isset($oUpdatedRndUsrData)) {
				$modelUsr->modify($oEnlUsrApp, $oUpdatedAppUsrData);
			} else {
				$modelUsr->modify($oEnlUsrApp, $oUpdatedEnlUsrData);
			}
		}

		/* 更新项目用户数据 */
		if (!empty($oApp->mission_id)) {
			$modelMisUsr = $this->model('matter\mission\user')->setOnlyWriteDbConn(true);
			/* 项目中需要额外更新的数据 */
			$oUpdatedMisUsrData = clone $oUsrEventData;
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
				if (isset($fnUsrMisData)) {
					$oResult = $fnUsrMisData($oEnlUsrApp);
					if ($oResult) {
						foreach ($oResult as $k => $v) {
							$oUpdatedMisUsrData->{$k} = $v;
						}
					}
				}
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
		if (isset($oRecord->score->sum)) {
			$oUpdatedUsrData->score = $oRecord->score->sum;
		}

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

		$fnUpdateRndUser = function ($oUserData) use ($oRecord, $oUser) {
			$oResult = new \stdClass;
			if (isset($oUser->group_id)) {
				$oResult->group_id = $oUser->group_id;
			}
			return $oResult;
		};
		$fnUpdateAppUser = function ($oUserData) use ($oApp, $oRecord, $oUser) {
			$oResult = new \stdClass;

			$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
			$sumScore = $modelUsr->query_val_ss([
				'sum(score)',
				'xxt_enroll_user',
				"siteid='$oApp->siteid' and aid='$oApp->id' and userid='$oUser->uid' and state=1 and rid <>'ALL'",
			]);

			$oResult->score = $sumScore;

			return $oResult;
		};

		return $this->_updateUsrData($oApp, $oRecord->rid, false, $oUser, $oUpdatedUsrData, $fnUpdateRndUser, $fnUpdateAppUser);
	}
	/**
	 * 填写记录获得提交协作填写项
	 */
	public function submitCowork($oApp, $oRecData, $oItem, $oOperator, $bSubmitNewItem = true) {
		$this->_doSubmitCowork($oApp, $oItem, $oOperator, $bSubmitNewItem);
		$this->_getSubmitCowork($oApp, $oRecData, $oItem, $oOperator, $bSubmitNewItem);
	}
	/**
	 * 执行提交协作填写项
	 */
	private function _doSubmitCowork($oApp, $oItem, $oUser, $bSubmitNewItem = true) {
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->op = self::DoSubmitCoworkEventName;
		$oNewModifyLog->userid = $oUser->uid;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->args = (object) ['id' => $oItem->id];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->nickname = $this->escape($oUser->nickname);
		$oUpdatedUsrData->last_do_cowork_at = $current;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		/* 提交新协作数据项 */
		if (true === $bSubmitNewItem) {
			$oNewModifyLog->op .= '_New';
			/* 提交记录的积分奖励 */
			$aCoinResult = $modelUsr->awardCoin($oApp, $oUser->uid, $oItem->rid, self::DoSubmitCoworkEventName);
			if ($aCoinResult[0] === true) {
				$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
			}
			$oUpdatedUsrData->do_cowork_num = 1;
		}

		/* 提交记录的积分奖励 */
		$aCoinResult = $modelUsr->awardCoin($oApp, $oUser->uid, $oItem->rid, self::DoSubmitCoworkEventName);
		if ($aCoinResult[0] === true) {
			$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
		}

		return $this->_updateUsrData($oApp, $oItem->rid, false, $oUser, $oUpdatedUsrData);
	}
	/**
	 * 填写记录获得提交协作填写项
	 */
	private function _getSubmitCowork($oApp, $oRecData, $oItem, $oOperator, $bSubmitNewItem = true) {
		if (empty($oRecData->userid)) {
			return false;
		}
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oOperator->uid;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::GetSubmitCoworkEventName;
		$oNewModifyLog->args = (object) ['id' => $oItem->id];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_cowork_at = $current;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;
		/* 提交新协作数据项 */
		if (true === $bSubmitNewItem) {
			$oNewModifyLog->op .= '_New';
			/* 提交记录的积分奖励 */
			$aCoinResult = $modelUsr->awardCoin($oApp, $oOperator->uid, $oItem->rid, self::GetSubmitCoworkEventName);
			if ($aCoinResult[0] === true) {
				$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
			}
			$oUpdatedUsrData->cowork_num = 1;
		}

		/* 提交记录的积分奖励 */
		$aCoinResult = $modelUsr->awardCoin($oApp, $oRecData->userid, $oRecData->rid, self::GetSubmitCoworkEventName);
		if ($aCoinResult[0] === true) {
			$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
		}

		$oUser = (object) ['uid' => $oRecData->userid];

		return $this->_updateUsrData($oApp, $oRecData->rid, true, $oUser, $oUpdatedUsrData);
	}
	/**
	 * 撤销协作填写项
	 */
	public function removeCowork($oApp, $oRecData, $oItem, $oOperator) {
		$this->_unDoSubmitCowork($oApp, $oItem, $oOperator);
		$this->_unGetSubmitCowork($oApp, $oRecData, $oItem, $oOperator);
	}
	/**
	 * 撤销协作填写项
	 */
	private function _unDoSubmitCowork($oApp, $oItem, $oUser) {
		$current = time();
		/* 日志回退函数 */
		$fnRollback = function ($oUserData) use ($oItem) {
			$aResult = [];
			$oLastestModifyLog = null; // 最近一次事件日志
			$oBeforeModifyLog = null; // 操作指定对象对应的事件日志
			foreach ($oUserData->modify_log as $oLog) {
				if (isset($oLog->op) && $oLog->op === self::DoSubmitCoworkEventName . '_New') {
					if (isset($oLog->args->id)) {
						if (!isset($oLastestModifyLog)) {
							$oLastestModifyLog = $oLog;
						}
						if ($oLog->args->id === $oItem->id) {
							$oBeforeModifyLog = $oLog;
							break;
						}
					}
				}
			}
			/* 回退积分奖励 */
			if (!empty($oBeforeModifyLog->coin)) {
				$aResult['user_total_coin'] = (-1) * (int) $oBeforeModifyLog->coin;
			}
			/* 最后一次事件发生时间 */
			if (!empty($oLastestModifyLog->at) && !empty($oUserData->last_do_cowork_at) && (int) $oLastestModifyLog->at > (int) $oUserData->last_do_cowork_at) {
				$aResult['last_do_cowork_at'] = $oLastestModifyLog->at;
			} else if ($oLastestModifyLog === $oBeforeModifyLog) {
				$aResult['last_do_cowork_at'] = 0;
			}
			if (count($aResult) === 0) {
				return false;
			}

			return (object) $aResult;
		};

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oUser->uid;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::DoSubmitCoworkEventName . '_Del';
		$oNewModifyLog->args = (object) ['id' => $oItem->id];
		/* 更新的数据 */
		$oUpdatedData = (object) [
			'do_cowork_num' => -1,
			'modify_log' => $oNewModifyLog,
		];

		$this->_updateUsrData($oApp, $oItem->rid, false, $oUser, $oUpdatedData, $fnRollback, $fnRollback, $fnRollback);

		return true;
	}
	/**
	 * 撤销协作填写数据项
	 */
	private function _unGetSubmitCowork($oApp, $oRecData, $oItem, $oOperator) {
		if (empty($oRecData->userid)) {
			return false;
		}
		$current = time();
		/* 日志回退函数 */
		$fnRollback = function ($oUserData) use ($oItem) {
			$aResult = [];
			$oLastestModifyLog = null; // 最近一次事件日志
			$oBeforeModifyLog = null; // 操作指定对象对应的事件日志
			foreach ($oUserData->modify_log as $oLog) {
				if (isset($oLog->op) && $oLog->op === self::GetSubmitCoworkEventName . '_New') {
					if (isset($oLog->args->id)) {
						if (!isset($oLastestModifyLog)) {
							$oLastestModifyLog = $oLog;
						}
						if ($oLog->args->id === $oItem->id) {
							$oBeforeModifyLog = $oLog;
							break;
						}
					}
				}
			}
			/* 回退积分奖励 */
			if (!empty($oBeforeModifyLog->coin)) {
				$aResult['user_total_coin'] = (-1) * (int) $oBeforeModifyLog->coin;
			}
			/* 最后一次事件发生时间 */
			if (!empty($oLastestModifyLog->at) && !empty($oUserData->last_cowork_at) && (int) $oLastestModifyLog->at > (int) $oUserData->last_cowork_at) {
				$aResult['last_cowork_at'] = $oLastestModifyLog->at;
			} else if ($oLastestModifyLog === $oBeforeModifyLog) {
				$aResult['last_cowork_at'] = 0;
			}
			if (count($aResult) === 0) {
				return false;
			}

			return (object) $aResult;
		};

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oOperator->uid;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::GetSubmitCoworkEventName . '_Del';
		$oNewModifyLog->args = (object) ['id' => $oItem->id];
		/* 更新的数据 */
		$oUpdatedData = (object) [
			'cowork_num' => -1,
			'modify_log' => $oNewModifyLog,
		];

		$oUser = (object) ['uid' => $oRecData->userid];

		$this->_updateUsrData($oApp, $oItem->rid, false, $oUser, $oUpdatedData, $fnRollback, $fnRollback, $fnRollback);

		return true;
	}
	/**
	 * 评论填写记录
	 */
	public function remarkRecord($oApp, $oRecOrData, $oOperator) {
		$this->_doRemarkRecOrData($oApp, $oRecOrData, $oOperator, 'record');
		$this->_getRemarkRecOrData($oApp, $oRecOrData, $oOperator, 'record');
	}
	/**
	 * 评论填写数据
	 */
	public function remarkRecData($oApp, $oRecOrData, $oOperator) {
		$this->_doRemarkRecOrData($oApp, $oRecOrData, $oOperator, 'record.data');
		$this->_getRemarkRecOrData($oApp, $oRecOrData, $oOperator, 'record.data');
	}
	/**
	 * 评论填写数据
	 */
	public function remarkCowork($oApp, $oRecOrData, $oOperator) {
		$this->_doRemarkRecOrData($oApp, $oRecOrData, $oOperator, 'record.data');
		$this->_getRemarkCowork($oApp, $oRecOrData, $oOperator);
	}
	/**
	 * 评论填写记录或数据
	 */
	private function _doRemarkRecOrData($oApp, $oRecOrData, $oOperator, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::DoRemarkEventName . '_New';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_do_remark_at = $current;
		$oUpdatedUsrData->do_remark_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		$aCoinResult = $modelUsr->awardCoin($oApp, $operatorId, $oRecOrData->rid, self::DoRemarkEventName);
		if ($aCoinResult[0] === true) {
			$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
		}

		return $this->_updateUsrData($oApp, $oRecOrData->rid, false, $oOperator, $oUpdatedUsrData);
	}
	/**
	 * 填写记录或数据获得评论
	 */
	private function _getRemarkRecOrData($oApp, $oRecOrData, $oOperator, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::GetRemarkEventName . '_New';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_remark_at = $current;
		$oUpdatedUsrData->remark_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		$aCoinResult = $modelUsr->awardCoin($oApp, $operatorId, $oRecOrData->rid, self::GetRemarkEventName);
		if ($aCoinResult[0] === true) {
			$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
		}

		$oUser = (object) ['uid' => $oRecOrData->userid];

		return $this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData);
	}
	/**
	 * 填写协作数据获得评论
	 */
	private function _getRemarkCowork($oApp, $oRecOrData, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::GetRemarkCoworkEventName . '_New';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_remark_cowork_at = $current;
		$oUpdatedUsrData->remark_cowork_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		$aCoinResult = $modelUsr->awardCoin($oApp, $operatorId, $oRecOrData->rid, self::GetRemarkCoworkEventName);
		if ($aCoinResult[0] === true) {
			$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
		}

		$oUser = (object) ['uid' => $oRecOrData->userid];

		return $this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData);
	}
	/**
	 * 赞同填写记录
	 * 同一条记录只有第一次点赞时才给积分奖励
	 */
	public function likeRecord($oApp, $oRecord, $oOperator) {
		return $this->_doLikeRecOrData($oApp, $oRecord, $oOperator, 'record');
	}
	/**
	 * 赞同填写记录数据
	 */
	public function likeRecData($oApp, $oRecData, $oOperator) {
		return $this->_doLikeRecOrData($oApp, $oRecData, $oOperator, 'record.data');
	}
	/**
	 * 赞同填写协作记录数据
	 */
	public function likeCowork($oApp, $oRecData, $oOperator) {
		return $this->_doLikeCowork($oApp, $oRecData, $oOperator);
	}
	/**
	 *
	 */
	private function _doLikeRecOrData($oApp, $oRecOrData, $oOperator, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::DoLikeEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_do_like_at = $current;
		$oUpdatedUsrData->do_like_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		return $this->_updateUsrData($oApp, $oRecOrData->rid, false, $oOperator, $oUpdatedUsrData);
	}
	/**
	 *
	 */
	private function _doLikeCowork($oApp, $oRecOrData, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::DoLikeCoworkEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_do_like_cowork_at = $current;
		$oUpdatedUsrData->do_like_cowork_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		return $this->_updateUsrData($oApp, $oRecOrData->rid, false, $oOperator, $oUpdatedUsrData);
	}
	/**
	 * 填写记录获得赞同
	 */
	public function getLikeRecord($oApp, $oRecord, $oOperator) {
		return $this->_getLikeRecOrData($oApp, $oRecord, $oOperator, 'record');
	}
	/**
	 * 填写数据获得赞同
	 */
	public function getLikeRecData($oApp, $oRecData, $oOperator) {
		return $this->_getLikeRecOrData($oApp, $oRecData, $oOperator, 'record.data');
	}
	/**
	 * 填写协作数据获得赞同
	 */
	public function getLikeCowork($oApp, $oRecData, $oOperator) {
		return $this->_getLikeCowork($oApp, $oRecData, $oOperator);
	}
	/**
	 * 填写记录或数据被点赞
	 */
	private function _getLikeRecOrData($oApp, $oRecOrData, $oOperator, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oRecOrData->userid;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::GetLikeEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType, 'operator' => $operatorId];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_like_at = $current;
		$oUpdatedUsrData->like_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;
		$aCoinResult = $modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GetLikeEventName);
		if ($aCoinResult[0] === true) {
			$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
		}
		$oUser = (object) ['uid' => $oRecOrData->userid];

		return $this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData);
	}
	/**
	 * 填写记录或数据被点赞
	 */
	private function _getLikeCowork($oApp, $oRecOrData, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oRecOrData->userid;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::GetLikeCoworkEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'operator' => $operatorId];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_like_cowork_at = $current;
		$oUpdatedUsrData->like_cowork_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;
		$aCoinResult = $modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GetLikeCoworkEventName);
		if ($aCoinResult[0] === true) {
			$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
		}
		$oUser = (object) ['uid' => $oRecOrData->userid];

		return $this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData);
	}
	/**
	 * 撤销填写记录点赞
	 */
	public function undoLikeRecord($oApp, $oRecord, $oOperator) {
		return $this->_undoLikeRecOrData($oApp, $oRecord, $oOperator, 'record');
	}
	/**
	 * 撤销填写数据点赞
	 */
	public function undoLikeRecData($oApp, $oRecData, $oOperator) {
		return $this->_undoLikeRecOrData($oApp, $oRecData, $oOperator, 'record.data');
	}
	/**
	 * 撤销填写数据点赞
	 */
	public function undoLikeCowork($oApp, $oRecData, $oOperator) {
		return $this->_undoLikeCowork($oApp, $oRecData, $oOperator);
	}
	/**
	 * 撤销赞同操作
	 */
	private function _undoLikeRecOrData($oApp, $oRecOrData, $oOperator, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = time();
		$oNewModifyLog->op = self::DoLikeEventName . '_N';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->do_like_num = -1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		/* 日志回退函数 */
		$fnRollback = function ($oUserData) use ($oRecOrData, $logArgType) {
			$aResult = []; // 要更新的数据
			if ($oUserData && count($oUserData->modify_log)) {
				$oLastestModifyLog = null; // 最近一次事件日志
				$oBeforeModifyLog = null; // 操作指定对象对应的事件日志
				$aRollbackLogs = []; // 插销操作日志
				foreach ($oUserData->modify_log as $oLog) {
					if ($oLog->op === self::DoLikeEventName . '_Y') {
						if (isset($oLog->args->type) && isset($oLog->args->id)) {
							/* 检查是否是已经撤销的操作 */
							$bRollbacked = false;
							foreach ($aRollbackLogs as $oRollbackLog) {
								if ($oLog->args->type === $oRollbackLog->args->type && $oLog->args->id === $oRollbackLog->args->id) {
									$bRollbacked = true;
									break;
								}
							}
							if ($bRollbacked) {
								continue;
							}
							/* 和撤销的操作同类型的最近发生的操作的日志，除了撤销的操作本身 */
							$oLastestModifyLog = $oLog;
							/* 由撤销的操作产生的日志 */
							if (empty($oBeforeModifyLog)) {
								if ($oLog->args->id === $oRecOrData->id && $oLog->args->type === $logArgType) {
									$oBeforeModifyLog = $oLog;
								}
							}
							if (isset($oBeforeModifyLog) && $oLastestModifyLog !== $oBeforeModifyLog) {
								break;
							}
						}
					} else if ($oLog->op === self::DoLikeEventName . '_N') {
						$aRollbackLogs[] = $oLog;
					}
				}
				/* 最后一次事件发生时间 */
				if ($oBeforeModifyLog === $oLastestModifyLog) {
					$aResult['last_do_like_at'] = 0;
				} else if (!empty($oLastestModifyLog->at)) {
					$aResult['last_do_like_at'] = $oLastestModifyLog->at;
				}
			}
			if (empty($aResult)) {
				return false;
			}
			return (object) $aResult;
		};

		return $this->_updateUsrData($oApp, $oRecOrData->rid, true, $oOperator, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);
	}
	/**
	 * 撤销赞同操作
	 */
	private function _undoLikeCowork($oApp, $oRecOrData, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = time();
		$oNewModifyLog->op = self::DoLikeCoworkEventName . '_N';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->do_like_cowork_num = -1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		/* 日志回退函数 */
		$fnRollback = function ($oUserData) use ($oRecOrData) {
			$aResult = []; // 要更新的数据
			if ($oUserData && count($oUserData->modify_log)) {
				$oLastestModifyLog = null; // 最近一次事件日志
				$oBeforeModifyLog = null; // 操作指定对象对应的事件日志
				$aRollbackLogs = []; // 插销操作日志
				foreach ($oUserData->modify_log as $oLog) {
					if ($oLog->op === self::DoLikeCoworkEventName . '_Y') {
						if (isset($oLog->args->id)) {
							/* 检查是否是已经撤销的操作 */
							$bRollbacked = false;
							foreach ($aRollbackLogs as $oRollbackLog) {
								if ($oLog->args->id === $oRollbackLog->args->id) {
									$bRollbacked = true;
									break;
								}
							}
							if ($bRollbacked) {
								continue;
							}
							/* 和撤销的操作同类型的最近发生的操作的日志，除了撤销的操作本身 */
							$oLastestModifyLog = $oLog;
							/* 由撤销的操作产生的日志 */
							if (empty($oBeforeModifyLog)) {
								if ($oLog->args->id === $oRecOrData->id) {
									$oBeforeModifyLog = $oLog;
								}
							}
							if (isset($oBeforeModifyLog) && $oLastestModifyLog !== $oBeforeModifyLog) {
								break;
							}
						}
					} else if ($oLog->op === self::DoLikeCoworkEventName . '_N') {
						$aRollbackLogs[] = $oLog;
					}
				}
				/* 最后一次事件发生时间 */
				if ($oBeforeModifyLog === $oLastestModifyLog) {
					$aResult['last_do_like_cowork_at'] = 0;
				} else if (!empty($oLastestModifyLog->at)) {
					$aResult['last_do_like_cowork_at'] = $oLastestModifyLog->at;
				}
			}
			if (empty($aResult)) {
				return false;
			}
			return (object) $aResult;
		};

		return $this->_updateUsrData($oApp, $oRecOrData->rid, true, $oOperator, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);
	}
	/**
	 * 取消填写记录被点赞
	 */
	public function undoGetLikeRecord($oApp, $oRecord, $oOperator) {
		return $this->_undoGetLikeRecOrData($oApp, $oRecord, $oOperator, 'record');
	}
	/**
	 * 取消填写数据被点赞
	 */
	public function undoGetLikeRecData($oApp, $oRecData, $oOperator) {
		return $this->_undoGetLikeRecOrData($oApp, $oRecData, $oOperator, 'record.data');
	}
	/**
	 * 取消协作填写数据被点赞
	 */
	public function undoGetLikeCowork($oApp, $oRecData, $oOperator) {
		return $this->_undoGetLikeCowork($oApp, $oRecData, $oOperator);
	}
	/**
	 * 取消被点赞
	 * 取消获得的积分
	 */
	private function _undoGetLikeRecOrData($oApp, $oRecOrData, $oOperator, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oRecOrData->userid;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::GetLikeEventName . '_N';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType, 'operator' => $operatorId];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->like_num = -1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		/* 日志回退函数 */
		$fnRollback = function ($oUserData) use ($oRecOrData, $logArgType, $operatorId) {
			$aResult = []; // 要更新的数据
			if ($oUserData && count($oUserData->modify_log)) {
				$oLastestModifyLog = null; // 最近一次事件日志
				$oBeforeModifyLog = null; // 操作指定对象对应的事件日志
				$aRollbackLogs = []; // 插销操作日志
				foreach ($oUserData->modify_log as $oLog) {
					if ($oLog->op === self::GetLikeEventName . '_Y') {
						if (isset($oLog->args->type) && isset($oLog->args->id) && isset($oLog->args->operator)) {
							/* 检查是否是已经撤销的操作 */
							$bRollbacked = false;
							foreach ($aRollbackLogs as $oRollbackLog) {
								if ($oLog->args->type === $oRollbackLog->args->type && $oLog->args->id === $oRollbackLog->args->id && $oLog->args->operator === $oRollbackLog->args->operator) {
									$bRollbacked = true;
									break;
								}
							}
							if ($bRollbacked) {
								continue;
							}
							/* 和撤销的操作同类型的最近发生的操作的日志，除了撤销的操作本身 */
							$oLastestModifyLog = $oLog;
							/* 由撤销的操作产生的日志 */
							if (empty($oBeforeModifyLog)) {
								if ($oLog->args->id === $oRecOrData->id && $oLog->args->type === $logArgType && $oLog->args->operator === $operatorId) {
									$oBeforeModifyLog = $oLog;
								}
							}
							if (isset($oBeforeModifyLog) && $oLastestModifyLog !== $oBeforeModifyLog) {
								break;
							}
						}
					} else if ($oLog->op === self::GetLikeEventName . '_N') {
						$aRollbackLogs[] = $oLog;
					}
				}
				/* 回退积分奖励 */
				if (!empty($oBeforeModifyLog->coin)) {
					$aResult['user_total_coin'] = (-1) * (int) $oBeforeModifyLog->coin;
				}
				/* 最后一次事件发生时间 */
				if ($oBeforeModifyLog === $oLastestModifyLog) {
					$aResult['last_like_at'] = 0;
				} else if (!empty($oLastestModifyLog->at)) {
					$aResult['last_like_at'] = $oLastestModifyLog->at;
				}
			}
			if (empty($aResult)) {
				return false;
			}
			return (object) $aResult;
		};

		$oUser = (object) ['uid' => $oRecOrData->userid];

		return $this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);
	}
	/**
	 * 取消被点赞
	 * 取消获得的积分
	 */
	private function _undoGetLikeCowork($oApp, $oRecOrData, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oRecOrData->userid;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::GetLikeCoworkEventName . '_N';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'operator' => $operatorId];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->like_cowork_num = -1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		/* 日志回退函数 */
		$fnRollback = function ($oUserData) use ($oRecOrData, $operatorId) {
			$aResult = []; // 要更新的数据
			if ($oUserData && count($oUserData->modify_log)) {
				$oLastestModifyLog = null; // 最近一次事件日志
				$oBeforeModifyLog = null; // 操作指定对象对应的事件日志
				$aRollbackLogs = []; // 插销操作日志
				foreach ($oUserData->modify_log as $oLog) {
					if ($oLog->op === self::GetLikeCoworkEventName . '_Y') {
						if (isset($oLog->args->id) && isset($oLog->args->operator)) {
							/* 检查是否是已经撤销的操作 */
							$bRollbacked = false;
							foreach ($aRollbackLogs as $oRollbackLog) {
								if ($oLog->args->id === $oRollbackLog->args->id && $oLog->args->operator === $oRollbackLog->args->operator) {
									$bRollbacked = true;
									break;
								}
							}
							if ($bRollbacked) {
								continue;
							}
							/* 和撤销的操作同类型的最近发生的操作的日志，除了撤销的操作本身 */
							$oLastestModifyLog = $oLog;
							/* 由撤销的操作产生的日志 */
							if (empty($oBeforeModifyLog)) {
								if ($oLog->args->id === $oRecOrData->id && $oLog->args->operator === $operatorId) {
									$oBeforeModifyLog = $oLog;
								}
							}
							if (isset($oBeforeModifyLog) && $oLastestModifyLog !== $oBeforeModifyLog) {
								break;
							}
						}
					} else if ($oLog->op === self::GetLikeCoworkEventName . '_N') {
						$aRollbackLogs[] = $oLog;
					}
				}
				/* 回退积分奖励 */
				if (!empty($oBeforeModifyLog->coin)) {
					$aResult['user_total_coin'] = (-1) * (int) $oBeforeModifyLog->coin;
				}
				/* 最后一次事件发生时间 */
				if ($oBeforeModifyLog === $oLastestModifyLog) {
					$aResult['last_like_cowork_at'] = 0;
				} else if (!empty($oLastestModifyLog->at)) {
					$aResult['last_like_cowork_at'] = $oLastestModifyLog->at;
				}
			}
			if (empty($aResult)) {
				return false;
			}
			return (object) $aResult;
		};

		$oUser = (object) ['uid' => $oRecOrData->userid];

		return $this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);
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
		$oNewModifyLog->op = self::DoLikeRemarkEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRemark->id];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_do_like_remark_at = $current;
		$oUpdatedUsrData->do_like_remark_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		return $this->_updateUsrData($oApp, $oRemark->rid, false, $oOperator, $oUpdatedUsrData);
	}
	/**
	 * 评论被点赞
	 */
	public function getLikeRemark($oApp, $oRemark, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oRemark->userid;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::GetLikeRemarkEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRemark->id, 'operator' => $operatorId];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_like_remark_at = $current;
		$oUpdatedUsrData->like_remark_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;
		$aCoinResult = $modelUsr->awardCoin($oApp, $oRemark->userid, $oRemark->rid, self::GetLikeRemarkEventName);
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
		$oNewModifyLog->op = self::DoLikeRemarkEventName . '_N';
		$oNewModifyLog->args = (object) ['id' => $oRemark->id];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->do_like_remark_num = -1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		return $this->_updateUsrData($oApp, $oRemark->rid, true, $oOperator, $oUpdatedUsrData);
	}
	/**
	 * 撤销评论被点赞
	 */
	public function undoGetLikeRemark($oApp, $oRemark, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oRemark->userid;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::GetLikeRemarkEventName . '_N';
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
				if ($oLog->op === self::GetLikeRemarkEventName . '_Y') {
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
	/**
	 * 对记录执行推荐相关操作
	 */
	public function agreeRecord($oApp, $oRecord, $oOperator, $value) {
		$rst = null;
		if ('Y' === $value) {
			$rst = $this->_getAgreeRecOrData($oApp, $oRecord, $oOperator, 'record');
		} else if ('Y' === $oRecord->agreed) {
			$rst = $this->_undoGetAgreeRecOrData($oApp, $oRecord, $oOperator, $value, 'record');
		}

		return $rst;
	}
	/**
	 * 对记录数据执行推荐相关操作
	 */
	public function agreeRecData($oApp, $oRecData, $oOperator, $value) {
		$rst = null;
		if ('Y' === $value) {
			$rst = $this->_getAgreeRecOrData($oApp, $oRecData, $oOperator, 'record.data');
		} else if ('Y' === $oRecData->agreed) {
			$rst = $this->_undoGetAgreeRecOrData($oApp, $oRecData, $oOperator, $value, 'record.data');
		}

		return $rst;
	}
	/**
	 * 赞同填写记录或数据
	 */
	private function _getAgreeRecOrData($oApp, $oRecOrData, $oOperator, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::GetAgreeEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType];

		/* 奖励积分 */
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
		$aCoinResult = $modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GetAgreeEventName);
		if ($aCoinResult[0] === true) {
			$oNewModifyLog->coin = $aCoinResult[1];
		}
		/* 更新的数据 */
		$oUpdatedUsrData = (object) [
			'last_agree_at' => $current,
			'agree_num' => 1,
			'user_total_coin' => $aCoinResult[0] === true ? $aCoinResult[1] : 0,
			'modify_log' => $oNewModifyLog,
		];

		$oUser = (object) ['uid' => $oRecOrData->userid];

		return $this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData);
	}
	/**
	 * 取消赞同记录数据
	 */
	private function _undoGetAgreeRecOrData($oApp, $oRecOrData, $oOperator, $value, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::GetAgreeEventName . '_' . $value;
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType];
		/* 更新的数据 */
		$oUpdatedUsrData = (object) [
			'agree_num' => -1,
			'modify_log' => $oNewModifyLog,
		];

		/* 日志回退函数 */
		$fnRollback = function ($oUserData) use ($oRecOrData, $logArgType) {
			$aResult = []; // 要更新的数据
			if ($oUserData && count($oUserData->modify_log)) {
				$oLastestModifyLog = null; // 最近一次事件日志
				$oBeforeModifyLog = null; // 操作指定对象对应的事件日志
				$aRollbackLogs = []; // 插销操作日志
				foreach ($oUserData->modify_log as $oLog) {
					if ($oLog->op === self::GetAgreeEventName . '_Y') {
						if (isset($oLog->args->type) && isset($oLog->args->id)) {
							/* 检查是否是已经撤销的操作 */
							$bRollbacked = false;
							foreach ($aRollbackLogs as $oRollbackLog) {
								if ($oLog->args->type === $oRollbackLog->args->type && $oLog->args->id === $oRollbackLog->args->id) {
									$bRollbacked = true;
									break;
								}
							}
							if ($bRollbacked) {
								continue;
							}
							/* 和撤销的操作同类型的最近发生的操作的日志，除了撤销的操作本身 */
							$oLastestModifyLog = $oLog;
							/* 由撤销的操作产生的日志 */
							if (empty($oBeforeModifyLog)) {
								if ($oLog->args->id === $oRecOrData->id && $oLog->args->type === $logArgType) {
									$oBeforeModifyLog = $oLog;
								}
							}
							if (isset($oBeforeModifyLog) && $oLastestModifyLog !== $oBeforeModifyLog) {
								break;
							}
						}
					} else if (strpos($oLog->op, self::GetAgreeEventName) === 0 && $oLog->op !== self::GetAgreeEventName . '_Y') {
						$aRollbackLogs[] = $oLog;
					}
				}
				/* 回退积分奖励。只要做了赞同的操作就给积分，不论结果是什么 */
				if (!empty($oBeforeModifyLog->coin)) {
					$aResult['user_total_coin'] = (-1) * (int) $oBeforeModifyLog->coin;
				}
				/* 最后一次事件发生时间 */
				if ($oBeforeModifyLog === $oLastestModifyLog) {
					$aResult['last_agree_at'] = 0;
				} else if (!empty($oLastestModifyLog->at)) {
					$aResult['last_agree_at'] = $oLastestModifyLog->at;
				}
			}
			if (empty($aResult)) {
				return false;
			}
			return (object) $aResult;
		};

		$oUser = (object) ['uid' => $oRecOrData->userid];

		return $this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);
	}
	/**
	 * 对记录数据执行推荐相关操作
	 */
	public function agreeCowork($oApp, $oRecData, $oOperator, $value) {
		$rst = null;
		if ('Y' === $value) {
			$rst = $this->_getAgreeCowork($oApp, $oRecData, $oOperator);
		} else if ('Y' === $oRecData->agreed) {
			$rst = $this->_undoGetAgreeCowork($oApp, $oRecData, $oOperator, $value);
		}

		return $rst;
	}
	/**
	 * 赞同填写记录或数据
	 */
	private function _getAgreeCowork($oApp, $oRecData, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::GetAgreeCoworkEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRecData->id];

		/* 奖励积分 */
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
		$aCoinResult = $modelUsr->awardCoin($oApp, $oRecData->userid, $oRecData->rid, self::GetAgreeCoworkEventName);
		if ($aCoinResult[0] === true) {
			$oNewModifyLog->coin = $aCoinResult[1];
		}
		/* 更新的数据 */
		$oUpdatedUsrData = (object) [
			'last_agree_cowork_at' => $current,
			'agree_cowork_num' => 1,
			'user_total_coin' => $aCoinResult[0] === true ? $aCoinResult[1] : 0,
			'modify_log' => $oNewModifyLog,
		];

		$oUser = (object) ['uid' => $oRecData->userid];

		return $this->_updateUsrData($oApp, $oRecData->rid, true, $oUser, $oUpdatedUsrData);
	}
	/**
	 * 取消赞同记录数据
	 */
	private function _undoGetAgreeCowork($oApp, $oRecData, $oOperator, $value) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::GetAgreeCoworkEventName . '_' . $value;
		$oNewModifyLog->args = (object) ['id' => $oRecData->id];
		/* 更新的数据 */
		$oUpdatedUsrData = (object) [
			'agree_cowork_num' => -1,
			'modify_log' => $oNewModifyLog,
		];

		/* 日志回退函数 */
		$fnRollback = function ($oUserData) use ($oRecData) {
			$aResult = []; // 要更新的数据
			if ($oUserData && count($oUserData->modify_log)) {
				$oLastestModifyLog = null; // 最近一次事件日志
				$oBeforeModifyLog = null; // 操作指定对象对应的事件日志
				$aRollbackLogs = []; // 插销操作日志
				foreach ($oUserData->modify_log as $oLog) {
					if ($oLog->op === self::GetAgreeCoworkEventName . '_Y') {
						if (isset($oLog->args->type) && isset($oLog->args->id)) {
							/* 检查是否是已经撤销的操作 */
							$bRollbacked = false;
							foreach ($aRollbackLogs as $oRollbackLog) {
								if ($oLog->args->type === $oRollbackLog->args->type && $oLog->args->id === $oRollbackLog->args->id) {
									$bRollbacked = true;
									break;
								}
							}
							if ($bRollbacked) {
								continue;
							}
							/* 和撤销的操作同类型的最近发生的操作的日志，除了撤销的操作本身 */
							$oLastestModifyLog = $oLog;
							/* 由撤销的操作产生的日志 */
							if (empty($oBeforeModifyLog)) {
								if ($oLog->args->id === $oRecData->id && $oLog->args->type === $logArgType) {
									$oBeforeModifyLog = $oLog;
								}
							}
							if (isset($oBeforeModifyLog) && $oLastestModifyLog !== $oBeforeModifyLog) {
								break;
							}
						}
					} else if (strpos($oLog->op, self::GetAgreeCoworkEventName) === 0 && $oLog->op !== self::GetAgreeCoworkEventName . '_Y') {
						$aRollbackLogs[] = $oLog;
					}
				}
				/* 回退积分奖励。只要做了赞同的操作就给积分，不论结果是什么 */
				if (!empty($oBeforeModifyLog->coin)) {
					$aResult['user_total_coin'] = (-1) * (int) $oBeforeModifyLog->coin;
				}
				/* 最后一次事件发生时间 */
				if ($oBeforeModifyLog === $oLastestModifyLog) {
					$aResult['last_agree_cowork_at'] = 0;
				} else if (!empty($oLastestModifyLog->at)) {
					$aResult['last_agree_cowork_at'] = $oLastestModifyLog->at;
				}
			}
			if (empty($aResult)) {
				return false;
			}
			return (object) $aResult;
		};

		$oUser = (object) ['uid' => $oRecData->userid];

		return $this->_updateUsrData($oApp, $oRecData->rid, true, $oUser, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);
	}
	/**
	 * 对记录执行推荐相关操作
	 */
	public function agreeRemark($oApp, $oRemark, $oOperator, $value) {
		$rst = null;
		if ('Y' === $value) {
			$rst = $this->_getAgreeRemark($oApp, $oRemark, $oOperator);
		} else if ('Y' === $oRemark->agreed) {
			$rst = $this->_undoGetAgreeRemark($oApp, $oRemark, $oOperator, $value);
		}

		return $rst;
	}
	/**
	 * 赞同填写记录或数据
	 */
	private function _getAgreeRemark($oApp, $oRemark, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::GetAgreeRemarkEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRemark->id];

		/* 奖励积分 */
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
		$aCoinResult = $modelUsr->awardCoin($oApp, $oRemark->userid, $oRemark->rid, self::GetAgreeRemarkEventName);
		if ($aCoinResult[0] === true) {
			$oNewModifyLog->coin = $aCoinResult[1];
		}

		/* 更新的数据 */
		$oUpdatedUsrData = (object) [
			'last_agree_remark_at' => $current,
			'agree_remark_num' => 1,
			'user_total_coin' => $aCoinResult[0] === true ? $aCoinResult[1] : 0,
			'modify_log' => $oNewModifyLog,
		];

		$oUser = (object) ['uid' => $oRemark->userid];

		return $this->_updateUsrData($oApp, $oRemark->rid, true, $oUser, $oUpdatedUsrData);
	}
	/**
	 * 取消赞同记录数据
	 */
	private function _undoGetAgreeRemark($oApp, $oRemark, $oOperator, $value) {
		$operatorId = $this->_getOperatorId($oOperator);
		$current = time();
		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $current;
		$oNewModifyLog->op = self::GetAgreeRemarkEventName . '_' . $value;
		$oNewModifyLog->args = (object) ['id' => $oRemark->id];
		/* 更新的数据 */
		$oUpdatedUsrData = (object) [
			'agree_remark_num' => -1,
			'modify_log' => $oNewModifyLog,
		];

		/* 日志回退函数 */
		$fnRollback = function ($oUserData) use ($oRemark) {
			$aResult = []; // 要更新的数据
			if ($oUserData && count($oUserData->modify_log)) {
				$oLastestModifyLog = null; // 最近一次事件日志
				$oBeforeModifyLog = null; // 操作指定对象对应的事件日志
				$aRollbackLogs = []; // 插销操作日志
				foreach ($oUserData->modify_log as $oLog) {
					if ($oLog->op === self::GetAgreeRemarkEventName . '_Y') {
						if (isset($oLog->args->id)) {
							/* 检查是否是已经撤销的操作 */
							$bRollbacked = false;
							foreach ($aRollbackLogs as $oRollbackLog) {
								if ($oLog->args->id === $oRollbackLog->args->id) {
									$bRollbacked = true;
									break;
								}
							}
							if ($bRollbacked) {
								continue;
							}
							/* 和撤销的操作同类型的最近发生的操作的日志，除了撤销的操作本身 */
							$oLastestModifyLog = $oLog;
							/* 由撤销的操作产生的日志 */
							if (empty($oBeforeModifyLog)) {
								if ($oLog->args->id === $oRemark->id) {
									$oBeforeModifyLog = $oLog;
								}
							}
							if (isset($oBeforeModifyLog) && $oLastestModifyLog !== $oBeforeModifyLog) {
								break;
							}
						}
					} else if (strpos($oLog->op, self::GetAgreeRemarkEventName) === 0 && $oLog->op !== self::GetAgreeRemarkEventName . '_Y') {
						$aRollbackLogs[] = $oLog;
					}
				}
				/* 回退积分奖励。只要做了赞同的操作就给积分，不论结果是什么 */
				if (!empty($oBeforeModifyLog->coin)) {
					$aResult['user_total_coin'] = (-1) * (int) $oBeforeModifyLog->coin;
				}
				/* 最后一次事件发生时间 */
				if ($oBeforeModifyLog === $oLastestModifyLog) {
					$aResult['last_agree_remark_at'] = 0;
				} else if (!empty($oLastestModifyLog->at)) {
					$aResult['last_agree_remark_at'] = $oLastestModifyLog->at;
				}
			}
			if (empty($aResult)) {
				return false;
			}
			return (object) $aResult;
		};

		$oUser = (object) ['uid' => $oRemark->userid];

		return $this->_updateUsrData($oApp, $oRemark->rid, true, $oUser, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);
	}
}