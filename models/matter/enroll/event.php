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
	 * 保存记录事件名称
	 */
	const SaveEventName = 'site.matter.enroll.save';
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
	const DoRemarkEventName = 'site.matter.enroll.do.remark';
	/**
	 * 用户A填写数据被赞同
	 */
	const GetLikeEventName = 'site.matter.enroll.data.get.like';
	/**
	 * 用户A填写数据被反对
	 */
	const GetDislikeEventName = 'site.matter.enroll.data.get.dislike';
	/**
	 * 用户A赞同别人的填写数据
	 */
	const DoLikeEventName = 'site.matter.enroll.data.do.like';
	/**
	 * 用户A不赞同别人的填写数据
	 */
	const DoDislikeEventName = 'site.matter.enroll.data.do.dislike';
	/**
	 * 用户A填写数据被赞同
	 */
	const GetLikeCoworkEventName = 'site.matter.enroll.cowork.get.like';
	/**
	 * 用户A填写数据被反对
	 */
	const GetDislikeCoworkEventName = 'site.matter.enroll.cowork.get.dislike';
	/**
	 * 用户A赞同别人的填写的协作数据
	 */
	const DoLikeCoworkEventName = 'site.matter.enroll.cowork.do.like';
	/**
	 * 用户A不赞同别人的填写的协作数据
	 */
	const DoDislikeCoworkEventName = 'site.matter.enroll.cowork.do.dislike';
	/**
	 * 用户A留言被赞同
	 */
	const GetLikeRemarkEventName = 'site.matter.enroll.remark.get.like';
	/**
	 * 用户A留言被反对
	 */
	const GetDislikeRemarkEventName = 'site.matter.enroll.remark.get.dislike';
	/**
	 * 用户A赞同别人的留言
	 */
	const DoLikeRemarkEventName = 'site.matter.enroll.remark.do.like';
	/**
	 * 用户A不赞同别人的留言
	 */
	const DoDislikeRemarkEventName = 'site.matter.enroll.remark.do.dislike';
	/**
	 * 推荐记录事件名称
	 */
	const GetAgreeEventName = 'site.matter.enroll.data.get.agree';
	/**
	 * 推荐留言事件名称
	 */
	const GetAgreeCoworkEventName = 'site.matter.enroll.cowork.get.agree';
	/**
	 * 推荐留言事件名称
	 */
	const GetAgreeRemarkEventName = 'site.matter.enroll.remark.get.agree';
	/**
	 * 将用户留言转换设置为协作数据
	 */
	const DoRemarkAsCoworkEventName = 'site.matter.enroll.remark.as.cowork';
	/**
	 * 获得题目投票
	 */
	const GetVoteSchemaEventName = 'site.matter.enroll.schema.get.vote';
	/**
	 * 获得协作填写投票
	 */
	const GetVoteCoworkEventName = 'site.matter.enroll.cowork.get.vote';
	/**
	 *
	 */
	private function _getOperatorId($oOperator) {
		$operatorId = isset($oOperator->uid) ? $oOperator->uid : (isset($oOperator->userid) ? $oOperator->userid : (isset($oOperator->id) ? $oOperator->id : ''));
		return $operatorId;
	}
	/**
	 *
	 */
	private function _getOperatorName($oOperator) {
		$operatorName = isset($oOperator->nickname) ? $oOperator->nickname : (isset($oOperator->name) ? $oOperator->name : '');
		return $operatorName;
	}
	/**
	 * 记录事件日志
	 */
	public function _logEvent($oApp, $rid, $ek, $oTarget, $oEvent, $oOwnerEvent = null) {
		$oNewLog = new \stdClass;
		/* 事件 */
		$oNewLog->event_name = $oEvent->name;
		$oNewLog->event_op = $oEvent->op;
		$oNewLog->event_at = $oEvent->at;
		$oNewLog->earn_coin = isset($oEvent->coin) ? $oEvent->coin : 0;

		/* 活动 */
		$oNewLog->aid = $oApp->id;
		$oNewLog->siteid = $oApp->siteid;
		$oNewLog->rid = $rid;
		$oNewLog->enroll_key = $ek;

		/* 发起事件的用户 */
		$oOperator = $oEvent->user;
		$oOperatorId = $this->_getOperatorId($oOperator);
		$oNewLog->group_id = isset($oOperator->group_id) ? $oOperator->group_id : '';
		$oNewLog->userid = $oOperatorId;
		$oNewLog->nickname = $this->_getOperatorName($oOperator);

		/* 事件操作的对象 */
		$oNewLog->target_id = $oTarget->id;
		$oNewLog->target_type = $oTarget->type;

		/* 事件操作的对象的创建用户 */
		if (isset($oOwnerEvent)) {
			$oOwner = $oOwnerEvent->user;
			$oNewLog->owner_userid = $oOwner->uid;
			if (!isset($oOwner->nickname)) {
				$modelUsr = $this->model('matter\enroll\user');
				$oOwnerUsr = $modelUsr->byId($oApp, $oOwner->uid, ['fields' => 'nickname']);
				if ($oOwnerUsr) {
					$oNewLog->owner_nickname = $this->escape($oOwnerUsr->nickname);
				}
			} else {
				$oNewLog->owner_nickname = $this->escape($oOwner->nickname);
			}
			$oNewLog->owner_earn_coin = isset($oOwnerEvent->coin) ? $oOwnerEvent->coin : 0;
		}

		$oNewLog->id = $this->insert('xxt_enroll_log', $oNewLog, true);

		return $oNewLog;
	}
	/**
	 * 更新用户汇总数据
	 */
	public function _updateUsrData($oApp, $rid, $bJumpCreate, $oUser, $oUsrEventData, $fnUsrRndData = null, $fnUsrAppData = null, $fnUsrMisData = null) {
		$userid = $this->_getOperatorId($oUser);

		/* 登记活动中需要额外更新的数据 */
		$oUpdatedEnlUsrData = clone $oUsrEventData;
		if (isset($oUser->group_id)) {
			$oUpdatedEnlUsrData->group_id = $oUser->group_id;
		}

		/* 更新发起留言的活动用户轮次数据 */
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
				$oUpdateUsrData1 = $oUpdatedRndUsrData;
			} else {
				$oUpdateUsrData1 = $oUpdatedEnlUsrData;
			}
			if ($oEnlUsrRnd->state == 0) {
				$oUpdateUsrData1->state = 1;
			}
			$modelUsr->modify($oEnlUsrRnd, $oUpdateUsrData1);
		}

		/* 如果存在匹配的汇总轮次，进行数据汇总 */
		$modelRnd = $this->model('matter\enroll\round');
		$oAssignedRnd = $modelRnd->byId($rid, ['fields' => 'rid,start_at']);
		if (false === $oAssignedRnd) {
			return 0;
		}
		$sumRnds = $modelRnd->getSummary($oApp, $oAssignedRnd->start_at, ['fields' => 'id,rid,title,start_at,state', 'includeRounds' => 'N']);
		if (!empty($sumRnds)) {
			foreach ($sumRnds as $oSumRnd) {
				if ($oSumRnd && $oSumRnd->state === '1') {
					$oUpdatedEnlUsrSumData = $modelUsr->sumByRound($oApp, $oUser, $oSumRnd, $oUpdatedEnlUsrData);
					if ($oUpdatedEnlUsrSumData) {
						/* 用户在汇总轮次中的数据汇总 */
						$oEnlUsrSum = $modelUsr->byId($oApp, $userid, ['fields' => '*', 'rid' => $oSumRnd->rid]);
						if (false === $oEnlUsrSum) {
							if (!$bJumpCreate) {
								$oUpdatedEnlUsrSumData->rid = $oSumRnd->rid;
								$modelUsr->add($oApp, $oUser, $oUpdatedEnlUsrSumData);
							}
						} else {
							if ($oEnlUsrSum->state == 0) {
								$oUpdatedEnlUsrSumData->state = 1;
							}
							$modelUsr->modify($oEnlUsrSum, $oUpdatedEnlUsrSumData);
						}
					}
				}
			}
		}
		/* 用户在活动中的数据汇总 */
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
			if (isset($oUpdatedAppUsrData)) {
				$oUpdatedUsrData2 = $oUpdatedAppUsrData;
			} else {
				$oUpdatedUsrData2 = $oUpdatedEnlUsrData;
			}
			if ($oEnlUsrApp->state == 0) {
				$oUpdatedUsrData2->state = 1;
			}
			$modelUsr->modify($oEnlUsrApp, $oUpdatedUsrData2);
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
				$oMisGrpUser = $this->model('matter\group\user')->byUser($oMisUsrGrpApp, $oUser->uid, ['onlyOne' => true, 'round_id']);
				if (isset($oMisGrpUser->round_id)) {
					$oUpdatedMisUsrData->group_id = $oMisGrpUser->round_id;
				}
			}
			if (false === $oMisUser) {
				if (!$bJumpCreate) {
					$modelMisUsr->add($oMission, $oUser, $oUpdatedMisUsrData);
				}
			} else {
				if (isset($fnUsrMisData)) {
					$oResult = $fnUsrMisData($oMisUser);
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
	 * 更新用户汇总数据
	 */
	public function updateMisUsrData($oMission, $bJumpCreate, $oUser, $oUsrEventData, $fnUsrMisData = null) {
		$modelMisUsr = $this->model('matter\mission\user')->setOnlyWriteDbConn(true);
		/* 项目中需要额外更新的数据 */
		$oUpdatedMisUsrData = clone $oUsrEventData;
		// unset($oUpdatedMisUsrData->modify_log);

		$oMisUser = $modelMisUsr->byId($oMission, $oUser->uid, ['fields' => '*']);
		/* 用户在项目中的所属分组 */
		if ($oMission->user_app_type === 'group') {
			$oMisUsrGrpApp = (object) ['id' => $oMission->user_app_id];
			$oMisGrpUser = $this->model('matter\group\user')->byUser($oMisUsrGrpApp, $oUser->uid, ['onlyOne' => true, 'round_id']);
			if (isset($oMisGrpUser->round_id)) {
				$oUpdatedMisUsrData->group_id = $oMisGrpUser->round_id;
			}
		}
		if (false === $oMisUser) {
			if (!$bJumpCreate) {
				$modelMisUsr->add($oMission, $oUser, $oUpdatedMisUsrData);
			}
		} else {
			if (isset($fnUsrMisData)) {
				$oResult = $fnUsrMisData($oMisUser);
				if ($oResult) {
					foreach ($oResult as $k => $v) {
						$oUpdatedMisUsrData->{$k} = $v;
					}
				}
			}
			$modelMisUsr->modify($oMisUser, $oUpdatedMisUsrData);
		}

		return true;
	}
	/**
	 * 用户提交记录
	 */
	public function submitRecord($oApp, $oRecord, $oUser, $bSubmitNewRecord, $bReviseRecordBeyondRound = false) {
		$eventAt = isset($oRecord->enroll_at) ? $oRecord->enroll_at : time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
		$modelRnd = $this->model('matter\enroll\round');
		$oRecRnd = $modelRnd->byId($oRecord->rid, ['fields' => 'purpose,start_at,end_at,state']);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oUser->uid;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::SubmitEventName;
		$oNewModifyLog->args = (object) ['id' => $oRecord->id];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->nickname = $this->escape($oUser->nickname);
		$oUpdatedUsrData->last_enroll_at = $eventAt;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		/* 只有常规轮次才将记录得分计入用户总分 */
		if (in_array($oRecRnd->purpose, ['C', 'S'])) {
			if (isset($oRecord->score->sum)) {
				$oUpdatedUsrData->score = $oRecord->score->sum;
			}
		}
		if ($oRecRnd->purpose === 'C') {
			/* 提交新记录 */
			if (true === $bSubmitNewRecord) {
				$oNewModifyLog->op .= '_New';
				/* 提交记录的积分奖励 */
				$aCoinResult = $modelUsr->awardCoin($oApp, $oUser->uid, $oRecord->rid, self::SubmitEventName);
				if (!empty($aCoinResult[1])) {
					$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
				}
				$oUpdatedUsrData->enroll_num = 1;
			} else if (true === $bReviseRecordBeyondRound) {
				$oUpdatedUsrData->revise_num = 1;
			}
		}
		/* 更新用户汇总数据 */
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
				['siteid' => $oApp->siteid, 'aid' => $oApp->id, 'userid' => $oUser->uid, 'state' => 1, 'purpose' => 'C', 'rid' => (object) ['op' => '<>', 'pat' => 'ALL']],
			]);

			$oResult->score = $sumScore;

			return $oResult;
		};

		$this->_updateUsrData($oApp, $oRecord->rid, false, $oUser, $oUpdatedUsrData, $fnUpdateRndUser, $fnUpdateAppUser);
		// 如果日志插入失败需要重新增加
		if (isset($aCoinResult) && $aCoinResult[0] === false && !empty($aCoinResult[1])) {
			$modelUsr->awardCoin($oApp, $oUser->uid, $oRecord->rid, self::SubmitEventName);
		}

		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oRecord->id;
		$oTarget->type = 'record';
		$oEvent = new \stdClass;
		$oEvent->name = self::SubmitEventName;
		$oEvent->op = $bSubmitNewRecord ? 'New' : 'Update';
		$oEvent->at = $eventAt;
		$oEvent->user = $oUser;
		$oEvent->coin = isset($oUpdatedUsrData->user_total_coin) ? $oUpdatedUsrData->user_total_coin : 0;

		$this->_logEvent($oApp, $oRecord->rid, $oRecord->enroll_key, $oTarget, $oEvent);

		return true;
	}
	/**
	 * 用户保存记录
	 */
	public function saveRecord($oApp, $oRecord, $oUser) {
		$eventAt = isset($oRecord->enroll_at) ? $oRecord->enroll_at : time();

		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oRecord->id;
		$oTarget->type = 'record';

		$oEvent = new \stdClass;
		$oEvent->name = self::SaveEventName;
		$oEvent->op = 'Save';
		$oEvent->at = $eventAt;
		$oEvent->user = $oUser;
		$oEvent->coin = 0;

		$this->_logEvent($oApp, $oRecord->rid, $oRecord->enroll_key, $oTarget, $oEvent);

		return true;
	}
	/**
	 * 填写记录获得提交协作填写项
	 */
	public function submitCowork($oApp, $oRecData, $oItem, $oOperator, $bSubmitNewItem = true) {
		$oOperatorData = $this->_doSubmitCowork($oApp, $oItem, $oOperator, $bSubmitNewItem);
		$oOwnerData = $this->_getSubmitCowork($oApp, $oRecData, $oItem, $oOperator, $bSubmitNewItem);

		$eventAt = isset($oItem->submit_at) ? $oItem->submit_at : time();

		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oItem->id;
		$oTarget->type = 'cowork';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::DoSubmitCoworkEventName;
		$oEvent->op = 'New';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		$oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
		//
		$oOwnerEvent = new \stdClass;
		$oOwnerEvent->user = (object) ['uid' => $oRecData->userid];
		$oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

		$this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

		return $oOperatorData;
	}
	/**
	 * 执行提交协作填写项
	 */
	private function _doSubmitCowork($oApp, $oItem, $oUser, $bSubmitNewItem = true) {
		$eventAt = isset($oItem->submit_at) ? $oItem->submit_at : time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->op = self::DoSubmitCoworkEventName;
		$oNewModifyLog->userid = $oUser->uid;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->args = (object) ['id' => $oItem->id];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->nickname = $this->escape($oUser->nickname);
		$oUpdatedUsrData->last_do_cowork_at = $eventAt;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		/* 提交新协作数据项 */
		if (true === $bSubmitNewItem) {
			$oNewModifyLog->op .= '_New';
			/* 提交记录的积分奖励 */
			$aCoinResult = $modelUsr->awardCoin($oApp, $oUser->uid, $oItem->rid, self::DoSubmitCoworkEventName);
			if (!empty($aCoinResult[1])) {
				$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
			}
			$oUpdatedUsrData->do_cowork_num = 1;
		}

		$this->_updateUsrData($oApp, $oItem->rid, false, $oUser, $oUpdatedUsrData);
		// 如果日志插入失败需要重新增加
		if (isset($aCoinResult) && $aCoinResult[0] === false && !empty($aCoinResult[1])) {
			$modelUsr->awardCoin($oApp, $oUser->uid, $oItem->rid, self::DoSubmitCoworkEventName);
		}

		return $oUpdatedUsrData;
	}
	/**
	 * 填写记录获得提交协作填写项
	 */
	private function _getSubmitCowork($oApp, $oRecData, $oItem, $oOperator, $bSubmitNewItem = true) {
		if (empty($oRecData->userid)) {
			return false;
		}
		$eventAt = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oOperator->uid;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::GetSubmitCoworkEventName;
		$oNewModifyLog->args = (object) ['id' => $oItem->id];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_cowork_at = $eventAt;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;
		/* 提交新协作数据项 */
		if (true === $bSubmitNewItem) {
			$oNewModifyLog->op .= '_New';
			/* 提交记录的积分奖励 */
			$aCoinResult = $modelUsr->awardCoin($oApp, $oOperator->uid, $oItem->rid, self::GetSubmitCoworkEventName);
			if (!empty($aCoinResult[1])) {
				$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
			}
			$oUpdatedUsrData->cowork_num = 1;
		}

		$oUser = (object) ['uid' => $oRecData->userid];

		$this->_updateUsrData($oApp, $oRecData->rid, true, $oUser, $oUpdatedUsrData);
		// 如果日志插入失败需要重新增加
		if (isset($aCoinResult) && $aCoinResult[0] === false && !empty($aCoinResult[1])) {
			$modelUsr->awardCoin($oApp, $oOperator->uid, $oItem->rid, self::GetSubmitCoworkEventName);
		}

		return $oUpdatedUsrData;
	}
	/**
	 * 评论转成协作数据
	 */
	public function remarkAsCowork($oApp, $oRecData, $oItem, $oRemark, $oOperator) {
		//$oOperatorData = $this->_doSubmitCowork($oApp, $oItem, $oOperator, true);
		//$oOwnerData = $this->_getSubmitCowork($oApp, $oRecData, $oItem, $oOperator, true);

		$eventAt = isset($oItem->submit_at) ? $oItem->submit_at : time();

		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oItem->id;
		$oTarget->type = 'cowork';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::DoRemarkAsCoworkEventName;
		$oEvent->op = 'New';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		//$oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
		//
		$oOwnerEvent = new \stdClass;
		$oOwnerEvent->user = (object) ['uid' => $oRecData->userid];
		//$oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

		$this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);
	}
	/**
	 * 撤销协作填写项
	 */
	public function removeCowork($oApp, $oRecData, $oItem, $oOperator) {
		$this->_unDoSubmitCowork($oApp, $oItem, $oOperator);
		$this->_unGetSubmitCowork($oApp, $oRecData, $oItem, $oOperator);

		$eventAt = time();
		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oItem->id;
		$oTarget->type = 'cowork';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::DoSubmitCoworkEventName;
		$oEvent->op = 'Del';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		//
		$oOwnerEvent = new \stdClass;
		$oOwnerEvent->user = (object) ['uid' => $oRecData->userid];

		$oLog = $this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

		/* 更新被撤销的事件 */
		$this->update(
			'xxt_enroll_log',
			['undo_event_id' => $oLog->id],
			['target_id' => $oItem->id, 'target_type' => 'cowork', 'event_name' => self::DoSubmitCoworkEventName, 'event_op' => 'New', 'undo_event_id' => 0]
		);
	}
	/**
	 * 撤销协作填写项
	 */
	private function _unDoSubmitCowork($oApp, $oItem, $oUser) {
		$eventAt = time();
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
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::DoSubmitCoworkEventName . '_Del';
		$oNewModifyLog->args = (object) ['id' => $oItem->id];
		/* 更新的数据 */
		$oUpdatedData = (object) [
			'do_cowork_num' => -1,
			'modify_log' => $oNewModifyLog,
		];

		$this->_updateUsrData($oApp, $oItem->rid, false, $oUser, $oUpdatedData, $fnRollback, $fnRollback, $fnRollback);

		return $oUpdatedData;
	}
	/**
	 * 撤销协作填写数据项
	 */
	private function _unGetSubmitCowork($oApp, $oRecData, $oItem, $oOperator) {
		if (empty($oRecData->userid)) {
			return false;
		}
		$eventAt = time();
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
		$oNewModifyLog->at = $eventAt;
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
	 * 留言填写记录
	 */
	public function remarkRecord($oApp, $oRecord, $oRemark, $oOperator) {
		$oOperatorData = $this->_doRemarkRecOrData($oApp, $oRecord, $oRemark, $oOperator, 'record');
		$oOwnerData = $this->_getRemarkRecOrData($oApp, $oRecord, $oRemark, $oOperator, 'record');

		$eventAt = time();
		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oRecord->id;
		$oTarget->type = 'record';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::DoRemarkEventName;
		$oEvent->op = 'New';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		$oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
		//
		$oOwnerEvent = new \stdClass;
		$oOwnerEvent->user = (object) ['uid' => $oRecord->userid];
		$oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

		$this->_logEvent($oApp, $oRecord->rid, $oRecord->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

		return $oOperatorData;
	}
	/**
	 * 留言填写数据
	 */
	public function remarkRecData($oApp, $oRecOrData, $oRemark, $oOperator) {
		$oOperatorData = $this->_doRemarkRecOrData($oApp, $oRecOrData, $oRemark, $oOperator, 'record.data');
		$oOwnerData = $this->_getRemarkRecOrData($oApp, $oRecOrData, $oRemark, $oOperator, 'record.data');

		$eventAt = time();
		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oRecOrData->id;
		$oTarget->type = 'record.data';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::DoRemarkEventName;
		$oEvent->op = 'New';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		$oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
		//
		$oOwnerEvent = new \stdClass;
		$oOwnerEvent->user = (object) ['uid' => $oRecOrData->userid];
		$oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

		$this->_logEvent($oApp, $oRecOrData->rid, $oRecOrData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

		return $oOperatorData;
	}
	/**
	 * 留言填写数据
	 */
	public function remarkCowork($oApp, $oCowork, $oRemark, $oOperator) {
		$oOperatorData = $this->_doRemarkRecOrData($oApp, $oCowork, $oRemark, $oOperator, 'cowork');
		$oOwnerData = $this->_getRemarkCowork($oApp, $oCowork, $oRemark, $oOperator);

		$eventAt = time();
		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oCowork->id;
		$oTarget->type = 'cowork';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::DoRemarkEventName;
		$oEvent->op = 'New';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		$oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
		//
		$oOwnerEvent = new \stdClass;
		$oOwnerEvent->user = (object) ['uid' => $oCowork->userid];
		$oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

		$this->_logEvent($oApp, $oCowork->rid, $oCowork->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

		return $oOperatorData;
	}
	/**
	 * 留言填写记录或数据
	 */
	private function _doRemarkRecOrData($oApp, $oRecOrData, $oRemark, $oOperator, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::DoRemarkEventName . '_New';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_do_remark_at = $eventAt;
		$oUpdatedUsrData->do_remark_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		$aCoinResult = $modelUsr->awardCoin($oApp, $operatorId, $oRemark->rid, self::DoRemarkEventName);
		if (!empty($aCoinResult[1])) {
			$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
		}

		$this->_updateUsrData($oApp, $oRemark->rid, false, $oOperator, $oUpdatedUsrData);
		// 如果日志插入失败需要重新增加
		if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
			$modelUsr->awardCoin($oApp, $operatorId, $oRemark->rid, self::DoRemarkEventName);
		}

		return $oUpdatedUsrData;
	}
	/**
	 * 填写记录或数据获得留言
	 */
	private function _getRemarkRecOrData($oApp, $oRecOrData, $oRemark, $oOperator, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::GetRemarkEventName . '_New';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_remark_at = $eventAt;
		$oUpdatedUsrData->remark_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		$aCoinResult = $modelUsr->awardCoin($oApp, $operatorId, $oRemark->rid, self::GetRemarkEventName);
		if (!empty($aCoinResult[1])) {
			$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
		}

		$oUser = (object) ['uid' => $oRecOrData->userid];

		$this->_updateUsrData($oApp, $oRemark->rid, false, $oUser, $oUpdatedUsrData);
		// 如果日志插入失败需要重新增加
		if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
			$modelUsr->awardCoin($oApp, $operatorId, $oRemark->rid, self::GetRemarkEventName);
		}

		return $oUpdatedUsrData;
	}
	/**
	 * 填写协作数据获得留言
	 */
	private function _getRemarkCowork($oApp, $oRecOrData, $oRemark, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::GetRemarkCoworkEventName . '_New';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_remark_cowork_at = $eventAt;
		$oUpdatedUsrData->remark_cowork_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		$aCoinResult = $modelUsr->awardCoin($oApp, $operatorId, $oRemark->rid, self::GetRemarkCoworkEventName);
		if (!empty($aCoinResult[1])) {
			$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
		}

		$oUser = (object) ['uid' => $oRecOrData->userid];

		$this->_updateUsrData($oApp, $oRemark->rid, true, $oUser, $oUpdatedUsrData);
		// 如果日志插入失败需要重新增加
		if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
			$modelUsr->awardCoin($oApp, $operatorId, $oRemark->rid, self::GetRemarkCoworkEventName);
		}

		return $oUpdatedUsrData;
	}
	/**
	 * 更新留言
	 */
	public function updateRemark($oApp, $oRemark, $oOperator) {
		$eventAt = time();
		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oRemark->id;
		$oTarget->type = 'remark';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::DoRemarkEventName;
		$oEvent->op = 'Upd';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		$oEvent->coin = 0;

		$this->_logEvent($oApp, $oRemark->rid, $oRemark->enroll_key, $oTarget, $oEvent);
	}
	/**
	 * 撤销留言
	 */
	public function removeRemark($oApp, $oRemark, $oOperator) {
		$eventAt = time();
		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oRemark->id;
		$oTarget->type = 'remark';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::DoRemarkEventName;
		$oEvent->op = 'Del';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		$oEvent->coin = 0;

		$this->_logEvent($oApp, $oRemark->rid, $oRemark->enroll_key, $oTarget, $oEvent);
	}
	/**
	 * 赞同填写记录
	 * 同一条记录只有第一次点赞时才给积分奖励
	 */
	public function likeRecord($oApp, $oRecord, $oOperator) {
		$oOperatorData = $this->_doLikeRecOrData($oApp, $oRecord, $oOperator, 'record');
		$oOwnerData = $this->_getLikeRecOrData($oApp, $oRecord, $oOperator, 'record');

		$eventAt = time();
		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oRecord->id;
		$oTarget->type = 'record';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::DoLikeEventName;
		$oEvent->op = 'Y';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		$oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
		//
		$oOwnerEvent = new \stdClass;
		$oOwnerEvent->user = (object) ['uid' => $oRecord->userid];
		$oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

		$this->_logEvent($oApp, $oRecord->rid, $oRecord->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

		return $oOperatorData;
	}
	/**
	 * 不赞同填写记录
	 *
	 */
	public function dislikeRecord($oApp, $oRecord, $oOperator) {
		$oOperatorData = $this->_doDislikeRecOrData($oApp, $oRecord, $oOperator, 'record');
		$oOwnerData = $this->_getDislikeRecOrData($oApp, $oRecord, $oOperator, 'record');

		$eventAt = time();
		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oRecord->id;
		$oTarget->type = 'record';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::DoDislikeEventName;
		$oEvent->op = 'Y';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		$oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
		//
		$oOwnerEvent = new \stdClass;
		$oOwnerEvent->user = (object) ['uid' => $oRecord->userid];
		$oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

		$this->_logEvent($oApp, $oRecord->rid, $oRecord->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

		return $oOperatorData;
	}
	/**
	 * 赞同填写记录数据
	 */
	public function likeRecData($oApp, $oRecData, $oOperator) {
		$oOperatorData = $this->_doLikeRecOrData($oApp, $oRecData, $oOperator, 'record.data');
		$oOwnerData = $this->_getLikeRecOrData($oApp, $oRecData, $oOperator, 'record.data');

		$eventAt = time();
		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oRecData->id;
		$oTarget->type = 'record.data';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::DoLikeEventName;
		$oEvent->op = 'Y';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		$oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
		//
		$oOwnerEvent = new \stdClass;
		$oOwnerEvent->user = (object) ['uid' => $oRecData->userid];
		$oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

		$this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);
	}
	/**
	 * 反对填写记录数据
	 */
	public function dislikeRecData($oApp, $oRecData, $oOperator) {
		$oOperatorData = $this->_doDislikeRecOrData($oApp, $oRecData, $oOperator, 'record.data');
		$oOwnerData = $this->_getDislikeRecOrData($oApp, $oRecData, $oOperator, 'record.data');

		$eventAt = time();
		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oRecData->id;
		$oTarget->type = 'record.data';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::DoDislikeEventName;
		$oEvent->op = 'Y';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		$oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
		//
		$oOwnerEvent = new \stdClass;
		$oOwnerEvent->user = (object) ['uid' => $oRecData->userid];
		$oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

		$this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);
	}
	/**
	 * 赞同填写协作记录数据
	 */
	public function likeCowork($oApp, $oRecData, $oOperator) {
		$oOperatorData = $this->_doLikeCowork($oApp, $oRecData, $oOperator);
		$oOwnerData = $this->_getLikeCowork($oApp, $oRecData, $oOperator);

		$eventAt = time();
		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oRecData->id;
		$oTarget->type = 'cowork';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::DoLikeCoworkEventName;
		$oEvent->op = 'Y';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		$oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
		//
		$oOwnerEvent = new \stdClass;
		$oOwnerEvent->user = (object) ['uid' => $oRecData->userid];
		$oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

		$this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);
	}
	/**
	 * 反对填写协作记录数据
	 */
	public function dislikeCowork($oApp, $oRecData, $oOperator) {
		$oOperatorData = $this->_doDislikeCowork($oApp, $oRecData, $oOperator);
		$oOwnerData = $this->_getDislikeCowork($oApp, $oRecData, $oOperator);

		$eventAt = time();
		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oRecData->id;
		$oTarget->type = 'cowork';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::DoDislikeCoworkEventName;
		$oEvent->op = 'Y';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		$oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
		//
		$oOwnerEvent = new \stdClass;
		$oOwnerEvent->user = (object) ['uid' => $oRecData->userid];
		$oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

		$this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);
	}
	/**
	 *
	 */
	private function _doLikeRecOrData($oApp, $oRecOrData, $oOperator, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::DoLikeEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_do_like_at = $eventAt;
		$oUpdatedUsrData->do_like_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		$this->_updateUsrData($oApp, $oRecOrData->rid, false, $oOperator, $oUpdatedUsrData);

		return $oUpdatedUsrData;
	}
	/**
	 *
	 */
	private function _doDislikeRecOrData($oApp, $oRecOrData, $oOperator, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::DoDislikeEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_do_dislike_at = $eventAt;
		$oUpdatedUsrData->do_dislike_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		$this->_updateUsrData($oApp, $oRecOrData->rid, false, $oOperator, $oUpdatedUsrData);

		return $oUpdatedUsrData;
	}
	/**
	 *
	 */
	private function _doLikeCowork($oApp, $oRecOrData, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::DoLikeCoworkEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_do_like_cowork_at = $eventAt;
		$oUpdatedUsrData->do_like_cowork_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		$this->_updateUsrData($oApp, $oRecOrData->rid, false, $oOperator, $oUpdatedUsrData);

		return $oUpdatedUsrData;
	}
	/**
	 *
	 */
	private function _doDislikeCowork($oApp, $oRecOrData, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::DoDislikeCoworkEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_do_dislike_cowork_at = $eventAt;
		$oUpdatedUsrData->do_dislike_cowork_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		$this->_updateUsrData($oApp, $oRecOrData->rid, false, $oOperator, $oUpdatedUsrData);

		return $oUpdatedUsrData;
	}
	/**
	 * 填写记录或数据被点赞
	 */
	private function _getLikeRecOrData($oApp, $oRecOrData, $oOperator, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oRecOrData->userid;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::GetLikeEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType, 'operator' => $operatorId];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_like_at = $eventAt;
		$oUpdatedUsrData->like_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;
		$aCoinResult = $modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GetLikeEventName);
		if (!empty($aCoinResult[1])) {
			$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
		}
		$oUser = (object) ['uid' => $oRecOrData->userid];

		$this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData);
		// 如果日志插入失败需要重新增加
		if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
			$modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GetLikeEventName);
		}

		return $oUpdatedUsrData;
	}
	/**
	 * 填写记录或数据被反对
	 */
	private function _getDislikeRecOrData($oApp, $oRecOrData, $oOperator, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oRecOrData->userid;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::GetDislikeEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType, 'operator' => $operatorId];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_dislike_at = $eventAt;
		$oUpdatedUsrData->dislike_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;
		$aCoinResult = $modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GetDislikeEventName);
		if (!empty($aCoinResult[1])) {
			$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
		}
		$oUser = (object) ['uid' => $oRecOrData->userid];

		$this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData);
		// 如果日志插入失败需要重新增加
		if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
			$modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GetDislikeEventName);
		}

		return $oUpdatedUsrData;
	}
	/**
	 * 填写记录或数据被点赞
	 */
	private function _getLikeCowork($oApp, $oRecOrData, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oRecOrData->userid;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::GetLikeCoworkEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'operator' => $operatorId];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_like_cowork_at = $eventAt;
		$oUpdatedUsrData->like_cowork_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;
		$aCoinResult = $modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GetLikeCoworkEventName);
		if (!empty($aCoinResult[1])) {
			$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
		}
		$oUser = (object) ['uid' => $oRecOrData->userid];

		$this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData);
		// 如果日志插入失败需要重新增加
		if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
			$modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GetLikeCoworkEventName);
		}

		return $oUpdatedUsrData;
	}
	/**
	 * 填写记录或数据被反对
	 */
	private function _getDislikeCowork($oApp, $oRecOrData, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oRecOrData->userid;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::GetDislikeCoworkEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'operator' => $operatorId];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_dislike_cowork_at = $eventAt;
		$oUpdatedUsrData->dislike_cowork_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;
		$aCoinResult = $modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GetDislikeCoworkEventName);
		if (!empty($aCoinResult[1])) {
			$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
		}
		$oUser = (object) ['uid' => $oRecOrData->userid];

		$this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData);
		// 如果日志插入失败需要重新增加
		if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
			$modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GetDislikeCoworkEventName);
		}

		return $oUpdatedUsrData;
	}
	/**
	 * 撤销填写记录点赞
	 */
	public function undoLikeRecord($oApp, $oRecord, $oOperator) {
		$this->_undoLikeRecOrData($oApp, $oRecord, $oOperator, 'record');
		$this->_undoGetLikeRecOrData($oApp, $oRecord, $oOperator, 'record');

		$eventAt = time();
		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oRecord->id;
		$oTarget->type = 'record';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::DoLikeEventName;
		$oEvent->op = 'N';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		//
		$oOwnerEvent = new \stdClass;
		$oOwnerEvent->user = (object) ['uid' => $oRecord->userid];

		$oLog = $this->_logEvent($oApp, $oRecord->rid, $oRecord->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

		/* 更新被撤销的事件 */
		$this->update(
			'xxt_enroll_log',
			['undo_event_id' => $oLog->id],
			['target_id' => $oRecord->id, 'target_type' => 'record', 'event_name' => self::DoLikeEventName, 'event_op' => 'Y', 'undo_event_id' => 0]
		);
	}
	/**
	 * 撤销填写记录反对
	 */
	public function undoDislikeRecord($oApp, $oRecord, $oOperator) {
		$this->_undoDislikeRecOrData($oApp, $oRecord, $oOperator, 'record');
		$this->_undoGetDislikeRecOrData($oApp, $oRecord, $oOperator, 'record');

		$eventAt = time();
		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oRecord->id;
		$oTarget->type = 'record';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::DoDislikeEventName;
		$oEvent->op = 'N';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		//
		$oOwnerEvent = new \stdClass;
		$oOwnerEvent->user = (object) ['uid' => $oRecord->userid];

		$oLog = $this->_logEvent($oApp, $oRecord->rid, $oRecord->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

		/* 更新被撤销的事件 */
		$this->update(
			'xxt_enroll_log',
			['undo_event_id' => $oLog->id],
			['target_id' => $oRecord->id, 'target_type' => 'record', 'event_name' => self::DoDislikeEventName, 'event_op' => 'Y', 'undo_event_id' => 0]
		);
	}
	/**
	 * 撤销填写数据点赞
	 */
	public function undoLikeRecData($oApp, $oRecData, $oOperator) {
		$this->_undoLikeRecOrData($oApp, $oRecData, $oOperator, 'record.data');
		$this->_undoGetLikeRecOrData($oApp, $oRecData, $oOperator, 'record.data');

		$eventAt = time();
		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oRecData->id;
		$oTarget->type = 'record.data';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::DoLikeEventName;
		$oEvent->op = 'N';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		//
		$oOwnerEvent = new \stdClass;
		$oOwnerEvent->user = (object) ['uid' => $oRecData->userid];

		$oLog = $this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

		/* 更新被撤销的事件 */
		$this->update(
			'xxt_enroll_log',
			['undo_event_id' => $oLog->id],
			['target_id' => $oRecData->id, 'target_type' => 'record.data', 'event_name' => self::DoLikeEventName, 'event_op' => 'Y', 'undo_event_id' => 0]
		);
	}
	/**
	 * 撤销填写数据反对
	 */
	public function undoDislikeRecData($oApp, $oRecData, $oOperator) {
		$this->_undoDislikeRecOrData($oApp, $oRecData, $oOperator, 'record.data');
		$this->_undoGetDislikeRecOrData($oApp, $oRecData, $oOperator, 'record.data');

		$eventAt = time();
		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oRecData->id;
		$oTarget->type = 'record.data';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::DoDislikeEventName;
		$oEvent->op = 'N';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		//
		$oOwnerEvent = new \stdClass;
		$oOwnerEvent->user = (object) ['uid' => $oRecData->userid];

		$oLog = $this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

		/* 更新被撤销的事件 */
		$this->update(
			'xxt_enroll_log',
			['undo_event_id' => $oLog->id],
			['target_id' => $oRecData->id, 'target_type' => 'record.data', 'event_name' => self::DoDislikeEventName, 'event_op' => 'Y', 'undo_event_id' => 0]
		);
	}
	/**
	 * 撤销填写数据点赞
	 */
	public function undoLikeCowork($oApp, $oCowork, $oOperator) {
		$this->_undoLikeCowork($oApp, $oCowork, $oOperator);
		$this->_undoGetLikeCowork($oApp, $oCowork, $oOperator);

		$eventAt = time();
		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oCowork->id;
		$oTarget->type = 'cowork';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::DoLikeCoworkEventName;
		$oEvent->op = 'N';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		//
		$oOwnerEvent = new \stdClass;
		$oOwnerEvent->user = (object) ['uid' => $oCowork->userid];

		$oLog = $this->_logEvent($oApp, $oCowork->rid, $oCowork->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

		/* 更新被撤销的事件 */
		$this->update(
			'xxt_enroll_log',
			['undo_event_id' => $oLog->id],
			['target_id' => $oCowork->id, 'target_type' => 'cowork', 'event_name' => self::DoLikeEventName, 'event_op' => 'Y', 'undo_event_id' => 0]
		);
	}
	/**
	 * 撤销填写数据反对
	 */
	public function undoDislikeCowork($oApp, $oCowork, $oOperator) {
		$this->_undoDislikeCowork($oApp, $oCowork, $oOperator);
		$this->_undoGetDislikeCowork($oApp, $oCowork, $oOperator);

		$eventAt = time();
		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oCowork->id;
		$oTarget->type = 'cowork';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::DoDislikeCoworkEventName;
		$oEvent->op = 'N';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		//
		$oOwnerEvent = new \stdClass;
		$oOwnerEvent->user = (object) ['uid' => $oCowork->userid];

		$oLog = $this->_logEvent($oApp, $oCowork->rid, $oCowork->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

		/* 更新被撤销的事件 */
		$this->update(
			'xxt_enroll_log',
			['undo_event_id' => $oLog->id],
			['target_id' => $oCowork->id, 'target_type' => 'cowork', 'event_name' => self::DoDislikeEventName, 'event_op' => 'Y', 'undo_event_id' => 0]
		);
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

		$this->_updateUsrData($oApp, $oRecOrData->rid, true, $oOperator, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);

		return $oUpdatedUsrData;
	}
	/**
	 * 撤销反对操作
	 */
	private function _undoDislikeRecOrData($oApp, $oRecOrData, $oOperator, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = time();
		$oNewModifyLog->op = self::DoDislikeEventName . '_N';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->do_dislike_num = -1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		/* 日志回退函数 */
		$fnRollback = function ($oUserData) use ($oRecOrData, $logArgType) {
			$aResult = []; // 要更新的数据
			if ($oUserData && count($oUserData->modify_log)) {
				$oLastestModifyLog = null; // 最近一次事件日志
				$oBeforeModifyLog = null; // 操作指定对象对应的事件日志
				$aRollbackLogs = []; // 插销操作日志
				foreach ($oUserData->modify_log as $oLog) {
					if ($oLog->op === self::DoDislikeEventName . '_Y') {
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
					} else if ($oLog->op === self::DoDislikeEventName . '_N') {
						$aRollbackLogs[] = $oLog;
					}
				}
				/* 最后一次事件发生时间 */
				if ($oBeforeModifyLog === $oLastestModifyLog) {
					$aResult['last_do_dislike_at'] = 0;
				} else if (!empty($oLastestModifyLog->at)) {
					$aResult['last_do_dislike_at'] = $oLastestModifyLog->at;
				}
			}
			if (empty($aResult)) {
				return false;
			}
			return (object) $aResult;
		};

		$this->_updateUsrData($oApp, $oRecOrData->rid, true, $oOperator, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);

		return $oUpdatedUsrData;
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

		$this->_updateUsrData($oApp, $oRecOrData->rid, true, $oOperator, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);

		return $oUpdatedUsrData;
	}
	/**
	 * 撤销反对操作
	 */
	private function _undoDislikeCowork($oApp, $oRecOrData, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = time();
		$oNewModifyLog->op = self::DoDislikeCoworkEventName . '_N';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->do_dislike_cowork_num = -1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		/* 日志回退函数 */
		$fnRollback = function ($oUserData) use ($oRecOrData) {
			$aResult = []; // 要更新的数据
			if ($oUserData && count($oUserData->modify_log)) {
				$oLastestModifyLog = null; // 最近一次事件日志
				$oBeforeModifyLog = null; // 操作指定对象对应的事件日志
				$aRollbackLogs = []; // 插销操作日志
				foreach ($oUserData->modify_log as $oLog) {
					if ($oLog->op === self::DoDislikeCoworkEventName . '_Y') {
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
					} else if ($oLog->op === self::DoDislikeCoworkEventName . '_N') {
						$aRollbackLogs[] = $oLog;
					}
				}
				/* 最后一次事件发生时间 */
				if ($oBeforeModifyLog === $oLastestModifyLog) {
					$aResult['last_do_dislike_cowork_at'] = 0;
				} else if (!empty($oLastestModifyLog->at)) {
					$aResult['last_do_dislike_cowork_at'] = $oLastestModifyLog->at;
				}
			}
			if (empty($aResult)) {
				return false;
			}
			return (object) $aResult;
		};

		$this->_updateUsrData($oApp, $oRecOrData->rid, true, $oOperator, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);

		return $oUpdatedUsrData;
	}
	/**
	 * 取消被点赞
	 * 取消获得的积分
	 */
	private function _undoGetLikeRecOrData($oApp, $oRecOrData, $oOperator, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oRecOrData->userid;
		$oNewModifyLog->at = $eventAt;
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

		$this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);

		return $oUpdatedUsrData;
	}
	/**
	 * 取消被反对
	 * 取消获得的积分
	 */
	private function _undoGetDislikeRecOrData($oApp, $oRecOrData, $oOperator, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oRecOrData->userid;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::GetDislikeEventName . '_N';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType, 'operator' => $operatorId];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->dislike_num = -1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		/* 日志回退函数 */
		$fnRollback = function ($oUserData) use ($oRecOrData, $logArgType, $operatorId) {
			$aResult = []; // 要更新的数据
			if ($oUserData && count($oUserData->modify_log)) {
				$oLastestModifyLog = null; // 最近一次事件日志
				$oBeforeModifyLog = null; // 操作指定对象对应的事件日志
				$aRollbackLogs = []; // 插销操作日志
				foreach ($oUserData->modify_log as $oLog) {
					if ($oLog->op === self::GetDislikeEventName . '_Y') {
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
					} else if ($oLog->op === self::GetDislikeEventName . '_N') {
						$aRollbackLogs[] = $oLog;
					}
				}
				/* 回退积分奖励 */
				if (!empty($oBeforeModifyLog->coin)) {
					$aResult['user_total_coin'] = (-1) * (int) $oBeforeModifyLog->coin;
				}
				/* 最后一次事件发生时间 */
				if ($oBeforeModifyLog === $oLastestModifyLog) {
					$aResult['last_dislike_at'] = 0;
				} else if (!empty($oLastestModifyLog->at)) {
					$aResult['last_dislike_at'] = $oLastestModifyLog->at;
				}
			}
			if (empty($aResult)) {
				return false;
			}
			return (object) $aResult;
		};

		$oUser = (object) ['uid' => $oRecOrData->userid];

		$this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);

		return $oUpdatedUsrData;
	}
	/**
	 * 取消被点赞
	 * 取消获得的积分
	 */
	private function _undoGetLikeCowork($oApp, $oRecOrData, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oRecOrData->userid;
		$oNewModifyLog->at = $eventAt;
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

		$this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);

		return $oUpdatedUsrData;
	}
	/**
	 * 取消被点赞
	 * 取消获得的积分
	 */
	private function _undoGetDislikeCowork($oApp, $oRecOrData, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oRecOrData->userid;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::GetDislikeCoworkEventName . '_N';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'operator' => $operatorId];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->dislike_cowork_num = -1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		/* 日志回退函数 */
		$fnRollback = function ($oUserData) use ($oRecOrData, $operatorId) {
			$aResult = []; // 要更新的数据
			if ($oUserData && count($oUserData->modify_log)) {
				$oLastestModifyLog = null; // 最近一次事件日志
				$oBeforeModifyLog = null; // 操作指定对象对应的事件日志
				$aRollbackLogs = []; // 插销操作日志
				foreach ($oUserData->modify_log as $oLog) {
					if ($oLog->op === self::GetDislikeCoworkEventName . '_Y') {
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
					} else if ($oLog->op === self::GetDislikeCoworkEventName . '_N') {
						$aRollbackLogs[] = $oLog;
					}
				}
				/* 回退积分奖励 */
				if (!empty($oBeforeModifyLog->coin)) {
					$aResult['user_total_coin'] = (-1) * (int) $oBeforeModifyLog->coin;
				}
				/* 最后一次事件发生时间 */
				if ($oBeforeModifyLog === $oLastestModifyLog) {
					$aResult['last_dislike_cowork_at'] = 0;
				} else if (!empty($oLastestModifyLog->at)) {
					$aResult['last_dislike_cowork_at'] = $oLastestModifyLog->at;
				}
			}
			if (empty($aResult)) {
				return false;
			}
			return (object) $aResult;
		};

		$oUser = (object) ['uid' => $oRecOrData->userid];

		$this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);

		return $oUpdatedUsrData;
	}
	/**
	 * 留言点赞
	 * 同一条留言只有第一次点赞时才给积分奖励
	 */
	public function likeRemark($oApp, $oRemark, $oOperator) {
		$oOperatorData = $this->_doLikeRemark($oApp, $oRemark, $oOperator);
		$oOwnerData = $this->_getLikeRemark($oApp, $oRemark, $oOperator);

		$eventAt = time();
		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oRemark->id;
		$oTarget->type = 'remark';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::DoLikeRemarkEventName;
		$oEvent->op = 'Y';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		$oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
		//
		$oOwnerEvent = new \stdClass;
		$oOwnerEvent->user = (object) ['uid' => $oRemark->userid];
		$oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

		$this->_logEvent($oApp, $oRemark->rid, $oRemark->enroll_key, $oTarget, $oEvent, $oOwnerEvent);
	}
	/**
	 * 留言点踩
	 */
	public function dislikeRemark($oApp, $oRemark, $oOperator) {
		$oOperatorData = $this->_doDislikeRemark($oApp, $oRemark, $oOperator);
		$oOwnerData = $this->_getDislikeRemark($oApp, $oRemark, $oOperator);

		$eventAt = time();
		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oRemark->id;
		$oTarget->type = 'remark';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::DoDislikeRemarkEventName;
		$oEvent->op = 'Y';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		$oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
		//
		$oOwnerEvent = new \stdClass;
		$oOwnerEvent->user = (object) ['uid' => $oRemark->userid];
		$oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

		$this->_logEvent($oApp, $oRemark->rid, $oRemark->enroll_key, $oTarget, $oEvent, $oOwnerEvent);
	}
	/**
	 * 留言点赞
	 */
	private function _doLikeRemark($oApp, $oRemark, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::DoLikeRemarkEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRemark->id];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_do_like_remark_at = $eventAt;
		$oUpdatedUsrData->do_like_remark_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		$this->_updateUsrData($oApp, $oRemark->rid, false, $oOperator, $oUpdatedUsrData);

		return $oUpdatedUsrData;
	}
	/**
	 * 留言点踩
	 */
	private function _doDislikeRemark($oApp, $oRemark, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::DoDislikeRemarkEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRemark->id];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_do_dislike_remark_at = $eventAt;
		$oUpdatedUsrData->do_dislike_remark_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		$this->_updateUsrData($oApp, $oRemark->rid, false, $oOperator, $oUpdatedUsrData);

		return $oUpdatedUsrData;
	}
	/**
	 * 留言被点赞
	 */
	private function _getLikeRemark($oApp, $oRemark, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oRemark->userid;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::GetLikeRemarkEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRemark->id, 'operator' => $operatorId];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_like_remark_at = $eventAt;
		$oUpdatedUsrData->like_remark_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;
		$aCoinResult = $modelUsr->awardCoin($oApp, $oRemark->userid, $oRemark->rid, self::GetLikeRemarkEventName);
		if (!empty($aCoinResult[1])) {
			$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
		}

		$oUser = (object) ['uid' => $oRemark->userid];

		$this->_updateUsrData($oApp, $oRemark->rid, true, $oUser, $oUpdatedUsrData);
		// 如果日志插入失败需要重新增加
		if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
			$modelUsr->awardCoin($oApp, $oRemark->userid, $oRemark->rid, self::GetLikeRemarkEventName);
		}

		return $oUpdatedUsrData;
	}
	/**
	 * 留言被反对
	 */
	private function _getDislikeRemark($oApp, $oRemark, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oRemark->userid;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::GetDislikeRemarkEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRemark->id, 'operator' => $operatorId];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_dislike_remark_at = $eventAt;
		$oUpdatedUsrData->dislike_remark_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;
		$aCoinResult = $modelUsr->awardCoin($oApp, $oRemark->userid, $oRemark->rid, self::GetDislikeRemarkEventName);
		if (!empty($aCoinResult[1])) {
			$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
		}

		$oUser = (object) ['uid' => $oRemark->userid];

		$this->_updateUsrData($oApp, $oRemark->rid, true, $oUser, $oUpdatedUsrData);
		// 如果日志插入失败需要重新增加
		if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
			$modelUsr->awardCoin($oApp, $oRemark->userid, $oRemark->rid, self::GetDislikeRemarkEventName);
		}

		return $oUpdatedUsrData;
	}
	/**
	 * 撤销发起对留言点赞
	 */
	public function undoLikeRemark($oApp, $oRemark, $oOperator) {
		$this->_undoLikeRemark($oApp, $oRemark, $oOperator);
		$this->_undoGetLikeRemark($oApp, $oRemark, $oOperator);

		$eventAt = time();
		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oRemark->id;
		$oTarget->type = 'remark';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::DoLikeRemarkEventName;
		$oEvent->op = 'N';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		//
		$oOwnerEvent = new \stdClass;
		$oOwnerEvent->user = (object) ['uid' => $oRemark->userid];

		$oLog = $this->_logEvent($oApp, $oRemark->rid, $oRemark->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

		/* 更新被撤销的事件 */
		$this->update(
			'xxt_enroll_log',
			['undo_event_id' => $oLog->id],
			['target_id' => $oRemark->id, 'target_type' => 'remark', 'event_name' => self::DoLikeEventName, 'event_op' => 'Y', 'undo_event_id' => 0]
		);
	}
	/**
	 * 撤销发起对留言点踩
	 */
	public function undoDislikeRemark($oApp, $oRemark, $oOperator) {
		$this->_undoDislikeRemark($oApp, $oRemark, $oOperator);
		$this->_undoGetDislikeRemark($oApp, $oRemark, $oOperator);

		$eventAt = time();
		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oRemark->id;
		$oTarget->type = 'remark';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::DoDislikeRemarkEventName;
		$oEvent->op = 'N';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		//
		$oOwnerEvent = new \stdClass;
		$oOwnerEvent->user = (object) ['uid' => $oRemark->userid];

		$oLog = $this->_logEvent($oApp, $oRemark->rid, $oRemark->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

		/* 更新被撤销的事件 */
		$this->update(
			'xxt_enroll_log',
			['undo_event_id' => $oLog->id],
			['target_id' => $oRemark->id, 'target_type' => 'remark', 'event_name' => self::DoDislikeEventName, 'event_op' => 'Y', 'undo_event_id' => 0]
		);
	}
	/**
	 * 撤销发起对留言点赞
	 */
	private function _undoLikeRemark($oApp, $oRemark, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::DoLikeRemarkEventName . '_N';
		$oNewModifyLog->args = (object) ['id' => $oRemark->id];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->do_like_remark_num = -1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		$this->_updateUsrData($oApp, $oRemark->rid, true, $oOperator, $oUpdatedUsrData);

		return $oUpdatedUsrData;
	}
	/**
	 * 撤销发起对留言点踩
	 */
	private function _undoDislikeRemark($oApp, $oRemark, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::DoDislikeRemarkEventName . '_N';
		$oNewModifyLog->args = (object) ['id' => $oRemark->id];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->do_dislike_remark_num = -1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		$this->_updateUsrData($oApp, $oRemark->rid, true, $oOperator, $oUpdatedUsrData);

		return $oUpdatedUsrData;
	}
	/**
	 * 撤销留言被点赞
	 */
	private function _undoGetLikeRemark($oApp, $oRemark, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oRemark->userid;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::GetLikeRemarkEventName . '_N';
		$oNewModifyLog->args = (object) ['id' => $oRemark->id, 'operator' => $operatorId];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->like_remark_num = -1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		$oEnlUsrRnd = $modelUsr->byId($oApp, $oRemark->userid, ['fields' => 'id,modify_log', 'rid' => $oRemark->rid]);
		/* 撤销获得的积分 */
		if ($oEnlUsrRnd && count($oEnlUsrRnd->modify_log)) {
			for ($i = 0; $i < count($oEnlUsrRnd->modify_log); $i++) {
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

		$this->_updateUsrData($oApp, $oRemark->rid, true, $oUser, $oUpdatedUsrData);

		return $oUpdatedUsrData;
	}
	/**
	 * 撤销留言被点踩
	 */
	private function _undoGetDislikeRemark($oApp, $oRemark, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oRemark->userid;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::GetDislikeRemarkEventName . '_N';
		$oNewModifyLog->args = (object) ['id' => $oRemark->id, 'operator' => $operatorId];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->dislike_remark_num = -1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		$oEnlUsrRnd = $modelUsr->byId($oApp, $oRemark->userid, ['fields' => 'id,modify_log', 'rid' => $oRemark->rid]);
		/* 撤销获得的积分 */
		if ($oEnlUsrRnd && count($oEnlUsrRnd->modify_log)) {
			for ($i = 0; $i < count($oEnlUsrRnd->modify_log); $i++) {
				$oLog = $oEnlUsrRnd->modify_log[$i];
				if ($oLog->op === self::GetDislikeRemarkEventName . '_Y') {
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

		$this->_updateUsrData($oApp, $oRemark->rid, true, $oUser, $oUpdatedUsrData);

		return $oUpdatedUsrData;
	}
	/**
	 * 对记录执行推荐相关操作
	 */
	public function agreeRecord($oApp, $oRecord, $oOperator, $value) {
		if ('Y' === $value) {
			$oOwnerData = $this->_getAgreeRecOrData($oApp, $oRecord, $oOperator, 'record');
			$eventAt = time();
			/* 记录事件日志 */
			$oTarget = new \stdClass;
			$oTarget->id = $oRecord->id;
			$oTarget->type = 'record';
			//
			$oEvent = new \stdClass;
			$oEvent->name = self::GetAgreeEventName;
			$oEvent->op = 'Y';
			$oEvent->at = $eventAt;
			$oEvent->user = $oOperator;
			//
			$oOwnerEvent = new \stdClass;
			$oOwnerEvent->user = (object) ['uid' => $oRecord->userid];
			$oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

			$this->_logEvent($oApp, $oRecord->rid, $oRecord->enroll_key, $oTarget, $oEvent, $oOwnerEvent);
		} else if ('Y' === $oRecord->agreed) {
			$oOwnerData = $this->_undoGetAgreeRecOrData($oApp, $oRecord, $oOperator, $value, 'record');
			$eventAt = time();
			/* 记录事件日志 */
			$oTarget = new \stdClass;
			$oTarget->id = $oRecord->id;
			$oTarget->type = 'record';
			//
			$oEvent = new \stdClass;
			$oEvent->name = self::GetAgreeEventName;
			$oEvent->op = $value;
			$oEvent->at = $eventAt;
			$oEvent->user = $oOperator;
			//
			$oOwnerEvent = new \stdClass;
			$oOwnerEvent->user = (object) ['uid' => $oRecord->userid];

			$oLog = $this->_logEvent($oApp, $oRecord->rid, $oRecord->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

			/* 更新被撤销的事件 */
			$this->update(
				'xxt_enroll_log',
				['undo_event_id' => $oLog->id],
				['target_id' => $oRecord->id, 'target_type' => 'record', 'event_name' => self::GetAgreeEventName, 'event_op' => 'Y', 'undo_event_id' => 0]
			);
		}
	}
	/**
	 * 对记录数据执行推荐相关操作
	 */
	public function agreeRecData($oApp, $oRecData, $oOperator, $value) {
		if ('Y' === $value) {
			$oOwnerData = $this->_getAgreeRecOrData($oApp, $oRecData, $oOperator, 'record.data');
			$eventAt = time();
			/* 记录事件日志 */
			$oTarget = new \stdClass;
			$oTarget->id = $oRecData->id;
			$oTarget->type = 'record.data';
			//
			$oEvent = new \stdClass;
			$oEvent->name = self::GetAgreeEventName;
			$oEvent->op = 'Y';
			$oEvent->at = $eventAt;
			$oEvent->user = $oOperator;
			//
			$oOwnerEvent = new \stdClass;
			$oOwnerEvent->user = (object) ['uid' => $oRecData->userid];
			$oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

			$this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);
		} else if ('Y' === $oRecData->agreed) {
			$oOwnerData = $this->_undoGetAgreeRecOrData($oApp, $oRecData, $oOperator, $value, 'record.data');
			$eventAt = time();
			/* 记录事件日志 */
			$oTarget = new \stdClass;
			$oTarget->id = $oRecData->id;
			$oTarget->type = 'record.data';
			//
			$oEvent = new \stdClass;
			$oEvent->name = self::GetAgreeEventName;
			$oEvent->op = $value;
			$oEvent->at = $eventAt;
			$oEvent->user = $oOperator;
			//
			$oOwnerEvent = new \stdClass;
			$oOwnerEvent->user = (object) ['uid' => $oRecData->userid];

			$oLog = $this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

			/* 更新被撤销的事件 */
			$this->update(
				'xxt_enroll_log',
				['undo_event_id' => $oLog->id],
				['target_id' => $oRecData->id, 'target_type' => 'record.data', 'event_name' => self::GetAgreeEventName, 'event_op' => 'Y', 'undo_event_id' => 0]
			);
		}
	}
	/**
	 * 赞同填写记录或数据
	 */
	private function _getAgreeRecOrData($oApp, $oRecOrData, $oOperator, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();
		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::GetAgreeEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'type' => $logArgType];

		/* 奖励积分 */
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
		$aCoinResult = $modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GetAgreeEventName);
		if (!empty($aCoinResult[1])) {
			$oNewModifyLog->coin = $aCoinResult[1];
		}
		/* 更新的数据 */
		$oUpdatedUsrData = (object) [
			'last_agree_at' => $eventAt,
			'agree_num' => 1,
			'user_total_coin' => $aCoinResult[0] === true ? $aCoinResult[1] : 0,
			'modify_log' => $oNewModifyLog,
		];

		$oUser = (object) ['uid' => $oRecOrData->userid];

		$this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData);
		// 如果日志插入失败需要重新增加
		if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
			$modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GetAgreeEventName);
		}

		return $oUpdatedUsrData;
	}
	/**
	 * 取消赞同记录数据
	 */
	private function _undoGetAgreeRecOrData($oApp, $oRecOrData, $oOperator, $value, $logArgType) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $eventAt;
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

		$this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);

		return $oUpdatedUsrData;
	}
	/**
	 * 对记录数据执行推荐相关操作
	 */
	public function agreeCowork($oApp, $oRecData, $oOperator, $value) {
		if ('Y' === $value) {
			$oOwnerData = $this->_getAgreeCowork($oApp, $oRecData, $oOperator);
			$eventAt = time();
			/* 记录事件日志 */
			$oTarget = new \stdClass;
			$oTarget->id = $oRecData->id;
			$oTarget->type = 'cowork';
			//
			$oEvent = new \stdClass;
			$oEvent->name = self::GetAgreeCoworkEventName;
			$oEvent->op = 'Y';
			$oEvent->at = $eventAt;
			$oEvent->user = $oOperator;
			//
			$oOwnerEvent = new \stdClass;
			$oOwnerEvent->user = (object) ['uid' => $oRecData->userid];
			$oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

			$this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);
		} else if ('Y' === $oRecData->agreed) {
			$oOwnerData = $this->_undoGetAgreeCowork($oApp, $oRecData, $oOperator, $value);
			$eventAt = time();
			/* 记录事件日志 */
			$oTarget = new \stdClass;
			$oTarget->id = $oRecData->id;
			$oTarget->type = 'cowork';
			//
			$oEvent = new \stdClass;
			$oEvent->name = self::GetAgreeCoworkEventName;
			$oEvent->op = $value;
			$oEvent->at = $eventAt;
			$oEvent->user = $oOperator;
			//
			$oOwnerEvent = new \stdClass;
			$oOwnerEvent->user = (object) ['uid' => $oRecData->userid];

			$oLog = $this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

			/* 更新被撤销的事件 */
			$this->update(
				'xxt_enroll_log',
				['undo_event_id' => $oLog->id],
				['target_id' => $oRecData->id, 'target_type' => 'cowork', 'event_name' => self::GetAgreeCoworkEventName, 'event_op' => 'Y', 'undo_event_id' => 0]
			);
		}
	}
	/**
	 * 赞同填写记录或数据
	 */
	private function _getAgreeCowork($oApp, $oRecData, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();
		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::GetAgreeCoworkEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRecData->id];

		/* 奖励积分 */
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
		$aCoinResult = $modelUsr->awardCoin($oApp, $oRecData->userid, $oRecData->rid, self::GetAgreeCoworkEventName);
		if (!empty($aCoinResult[1])) {
			$oNewModifyLog->coin = $aCoinResult[1];
		}
		/* 更新的数据 */
		$oUpdatedUsrData = (object) [
			'last_agree_cowork_at' => $eventAt,
			'agree_cowork_num' => 1,
			'user_total_coin' => $aCoinResult[0] === true ? $aCoinResult[1] : 0,
			'modify_log' => $oNewModifyLog,
		];

		$oUser = (object) ['uid' => $oRecData->userid];

		$this->_updateUsrData($oApp, $oRecData->rid, true, $oUser, $oUpdatedUsrData);
		// 如果日志插入失败需要重新增加
		if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
			$modelUsr->awardCoin($oApp, $oRecData->userid, $oRecData->rid, self::GetAgreeCoworkEventName);
		}

		return $oUpdatedUsrData;
	}
	/**
	 * 取消赞同记录数据
	 */
	private function _undoGetAgreeCowork($oApp, $oRecData, $oOperator, $value) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $eventAt;
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

		$this->_updateUsrData($oApp, $oRecData->rid, true, $oUser, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);

		return $oUpdatedUsrData;
	}
	/**
	 * 对记录执行推荐相关操作
	 */
	public function agreeRemark($oApp, $oRemark, $oOperator, $value) {
		if ('Y' === $value) {
			$oOwnerData = $this->_getAgreeRemark($oApp, $oRemark, $oOperator);
			$eventAt = time();
			/* 记录事件日志 */
			$oTarget = new \stdClass;
			$oTarget->id = $oRemark->id;
			$oTarget->type = 'remark';
			//
			$oEvent = new \stdClass;
			$oEvent->name = self::GetAgreeRemarkEventName;
			$oEvent->op = 'Y';
			$oEvent->at = $eventAt;
			$oEvent->user = $oOperator;
			//
			$oOwnerEvent = new \stdClass;
			$oOwnerEvent->user = (object) ['uid' => $oRemark->userid];
			$oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

			$this->_logEvent($oApp, $oRemark->rid, $oRemark->enroll_key, $oTarget, $oEvent, $oOwnerEvent);
		} else if ('Y' === $oRemark->agreed) {
			$oOwnerData = $this->_undoGetAgreeRemark($oApp, $oRemark, $oOperator, $value);
			$eventAt = time();
			/* 记录事件日志 */
			$oTarget = new \stdClass;
			$oTarget->id = $oRemark->id;
			$oTarget->type = 'remark';
			//
			$oEvent = new \stdClass;
			$oEvent->name = self::GetAgreeRemarkEventName;
			$oEvent->op = $value;
			$oEvent->at = $eventAt;
			$oEvent->user = $oOperator;
			//
			$oOwnerEvent = new \stdClass;
			$oOwnerEvent->user = (object) ['uid' => $oRemark->userid];

			$oLog = $this->_logEvent($oApp, $oRemark->rid, $oRemark->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

			/* 更新被撤销的事件 */
			$this->update(
				'xxt_enroll_log',
				['undo_event_id' => $oLog->id],
				['target_id' => $oRemark->id, 'target_type' => 'remark', 'event_name' => self::GetAgreeRemarkEventName, 'event_op' => 'Y', 'undo_event_id' => 0]
			);
		}
	}
	/**
	 * 赞同填写记录或数据
	 */
	private function _getAgreeRemark($oApp, $oRemark, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();
		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::GetAgreeRemarkEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRemark->id];

		/* 奖励积分 */
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
		$aCoinResult = $modelUsr->awardCoin($oApp, $oRemark->userid, $oRemark->rid, self::GetAgreeRemarkEventName);
		if (!empty($aCoinResult[1])) {
			$oNewModifyLog->coin = $aCoinResult[1];
		}

		/* 更新的数据 */
		$oUpdatedUsrData = (object) [
			'last_agree_remark_at' => $eventAt,
			'agree_remark_num' => 1,
			'user_total_coin' => $aCoinResult[0] === true ? $aCoinResult[1] : 0,
			'modify_log' => $oNewModifyLog,
		];

		$oUser = (object) ['uid' => $oRemark->userid];

		$this->_updateUsrData($oApp, $oRemark->rid, true, $oUser, $oUpdatedUsrData);
		// 如果日志插入失败需要重新增加
		if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
			$modelUsr->awardCoin($oApp, $oRemark->userid, $oRemark->rid, self::GetAgreeRemarkEventName);
		}

		return $oUpdatedUsrData;
	}
	/**
	 * 取消赞同记录数据
	 */
	private function _undoGetAgreeRemark($oApp, $oRemark, $oOperator, $value) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();
		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $operatorId;
		$oNewModifyLog->at = $eventAt;
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

		$this->_updateUsrData($oApp, $oRemark->rid, true, $oUser, $oUpdatedUsrData, $fnRollback, $fnRollback, $fnRollback);

		return $oUpdatedUsrData;
	}
	/**
	 * 对协作填写进行投票
	 */
	public function voteRecCowork($oApp, $oRecData, $oOperator) {
		$oOperatorData = $this->_doVoteCowork($oApp, $oRecData, $oOperator);
		$oOwnerData = $this->_getVoteCowork($oApp, $oRecData, $oOperator);

		$eventAt = time();
		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oRecData->id;
		$oTarget->type = 'record.data';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::GetVoteCoworkEventName;
		$oEvent->op = 'Y';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		$oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
		//
		$oOwnerEvent = new \stdClass;
		$oOwnerEvent->user = (object) ['uid' => $oRecData->userid];
		$oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

		$this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);
	}
	/**
	 * 对题目进行投票
	 */
	public function voteRecSchema($oApp, $oRecData, $oOperator) {
		$oOperatorData = $this->_doVoteSchema($oApp, $oRecData, $oOperator);
		$oOwnerData = $this->_getVoteSchema($oApp, $oRecData, $oOperator);

		$eventAt = time();
		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oRecData->id;
		$oTarget->type = 'record.data';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::GetVoteSchemaEventName;
		$oEvent->op = 'Y';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		$oEvent->coin = isset($oOperatorData->user_total_coin) ? $oOperatorData->user_total_coin : 0;
		//
		$oOwnerEvent = new \stdClass;
		$oOwnerEvent->user = (object) ['uid' => $oRecData->userid];
		$oOwnerEvent->coin = isset($oOwnerData->user_total_coin) ? $oOwnerData->user_total_coin : 0;

		$this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);
	}
	/**
	 * 撤销对协作填写的投票
	 */
	public function unvoteRecCowork($oApp, $oRecData, $oOperator) {
		$this->_undoVoteCowork($oApp, $oRecData, $oOperator);
		$this->_undoGetVoteCowork($oApp, $oRecData, $oOperator);

		$eventAt = time();
		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oRecData->id;
		$oTarget->type = 'record.data';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::GetVoteCoworkEventName;
		$oEvent->op = 'N';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		//
		$oOwnerEvent = new \stdClass;
		$oOwnerEvent->user = (object) ['uid' => $oRecData->userid];

		$oLog = $this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

		/* 更新被撤销的事件 */
		$this->update(
			'xxt_enroll_log',
			['undo_event_id' => $oLog->id],
			['target_id' => $oRecData->id, 'target_type' => 'remark', 'event_name' => self::GetVoteCoworkEventName, 'event_op' => 'Y', 'undo_event_id' => 0]
		);
	}
	/**
	 * 撤销对题目的投票
	 */
	public function unvoteRecSchema($oApp, $oRecData, $oOperator) {
		$this->_undoVoteSchema($oApp, $oRecData, $oOperator);
		$this->_undoGetVoteSchema($oApp, $oRecData, $oOperator);

		$eventAt = time();
		/* 记录事件日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oRecData->id;
		$oTarget->type = 'record.data';
		//
		$oEvent = new \stdClass;
		$oEvent->name = self::GetVoteSchemaEventName;
		$oEvent->op = 'N';
		$oEvent->at = $eventAt;
		$oEvent->user = $oOperator;
		//
		$oOwnerEvent = new \stdClass;
		$oOwnerEvent->user = (object) ['uid' => $oRecData->userid];

		$oLog = $this->_logEvent($oApp, $oRecData->rid, $oRecData->enroll_key, $oTarget, $oEvent, $oOwnerEvent);

		/* 更新被撤销的事件 */
		$this->update(
			'xxt_enroll_log',
			['undo_event_id' => $oLog->id],
			['target_id' => $oRecData->id, 'target_type' => 'remark', 'event_name' => self::GetVoteSchemaEventName, 'event_op' => 'Y', 'undo_event_id' => 0]
		);
	}
	/**
	 * 对协作填写进行投票
	 */
	private function _doVoteCowork($oApp, $oRecOrData, $oOperator) {
		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;

		return $oUpdatedUsrData;
	}
	/**
	 * 协作填写获得投票
	 */
	private function _getVoteCowork($oApp, $oRecOrData, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oRecOrData->userid;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::GetVoteCoworkEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'operator' => $operatorId];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_vote_cowork_at = $eventAt;
		$oUpdatedUsrData->vote_cowork_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;
		$aCoinResult = $modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GetVoteCoworkEventName);
		if (!empty($aCoinResult[1])) {
			$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
		}
		$oUser = (object) ['uid' => $oRecOrData->userid];

		$this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData);
		// 如果日志插入失败需要重新增加
		if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
			$modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GetVoteCoworkEventName);
		}

		return $oUpdatedUsrData;
	}
	/**
	 * 对题目进行投票
	 */
	private function _doVoteSchema($oApp, $oRecOrData, $oOperator) {
		$oUpdatedUsrData = new \stdClass;

		return $oUpdatedUsrData;
	}
	/**
	 * 题目获得投票
	 */
	private function _getVoteSchema($oApp, $oRecOrData, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oRecOrData->userid;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::GetVoteSchemaEventName . '_Y';
		$oNewModifyLog->args = (object) ['id' => $oRecOrData->id, 'operator' => $operatorId];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->last_vote_schema_at = $eventAt;
		$oUpdatedUsrData->vote_schema_num = 1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;
		$aCoinResult = $modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GetVoteSchemaEventName);
		if (!empty($aCoinResult[1])) {
			$oUpdatedUsrData->user_total_coin = $oNewModifyLog->coin = $aCoinResult[1];
		}
		$oUser = (object) ['uid' => $oRecOrData->userid];

		$this->_updateUsrData($oApp, $oRecOrData->rid, true, $oUser, $oUpdatedUsrData);
		// 如果日志插入失败需要重新增加
		if ($aCoinResult[0] === false && !empty($aCoinResult[1])) {
			$modelUsr->awardCoin($oApp, $oRecOrData->userid, $oRecOrData->rid, self::GetVoteSchemaEventName);
		}

		return $oUpdatedUsrData;
	}
	/**
	 * 撤销发起对协作填写的投票
	 */
	private function _undoVoteCowork($oApp, $oRecOrData, $oOperator) {
		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;

		return $oUpdatedUsrData;
	}
	/**
	 * 撤销留言被点赞
	 */
	private function _undoGetVoteCowork($oApp, $oRecData, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oRecData->userid;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::GetVoteCoworkEventName . '_N';
		$oNewModifyLog->args = (object) ['id' => $oRecData->id, 'operator' => $operatorId];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->vote_cowork_num = -1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		$oEnlUsrRnd = $modelUsr->byId($oApp, $oRecData->userid, ['fields' => 'id,modify_log', 'rid' => $oRecData->rid]);
		/* 撤销获得的积分 */
		if ($oEnlUsrRnd && count($oEnlUsrRnd->modify_log)) {
			for ($i = 0; $i < count($oEnlUsrRnd->modify_log); $i++) {
				$oLog = $oEnlUsrRnd->modify_log[$i];
				if ($oLog->op === self::GetVoteCoworkEventName . '_Y') {
					if (isset($oLog->args->id) && isset($oLog->args->operator)) {
						if ($oLog->args->id === $oRecData->id && $oLog->args->operator === $operatorId) {
							if (!empty($oLog->coin)) {
								$oUpdatedUsrData->user_total_coin = -1 * (int) $oLog->coin;
							}
							break;
						}
					}
				}
			}
		}

		$oUser = (object) ['uid' => $oRecData->userid];

		$this->_updateUsrData($oApp, $oRecData->rid, true, $oUser, $oUpdatedUsrData);

		return $oUpdatedUsrData;
	}
	/**
	 * 撤销发起对协作填写的投票
	 */
	private function _undoVoteSchema($oApp, $oRecOrData, $oOperator) {
		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;

		return $oUpdatedUsrData;
	}
	/**
	 * 撤销留言被点赞
	 */
	private function _undoGetVoteSchema($oApp, $oRecData, $oOperator) {
		$operatorId = $this->_getOperatorId($oOperator);
		$eventAt = time();
		$modelUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);

		/* 记录修改日志 */
		$oNewModifyLog = new \stdClass;
		$oNewModifyLog->userid = $oRecData->userid;
		$oNewModifyLog->at = $eventAt;
		$oNewModifyLog->op = self::GetVoteSchemaEventName . '_N';
		$oNewModifyLog->args = (object) ['id' => $oRecData->id, 'operator' => $operatorId];

		/* 更新的数据 */
		$oUpdatedUsrData = new \stdClass;
		$oUpdatedUsrData->vote_schema_num = -1;
		$oUpdatedUsrData->modify_log = $oNewModifyLog;

		$oEnlUsrRnd = $modelUsr->byId($oApp, $oRecData->userid, ['fields' => 'id,modify_log', 'rid' => $oRecData->rid]);
		/* 撤销获得的积分 */
		if ($oEnlUsrRnd && count($oEnlUsrRnd->modify_log)) {
			for ($i = 0; $i < count($oEnlUsrRnd->modify_log); $i++) {
				$oLog = $oEnlUsrRnd->modify_log[$i];
				if ($oLog->op === self::GetVoteSchemaEventName . '_Y') {
					if (isset($oLog->args->id) && isset($oLog->args->operator)) {
						if ($oLog->args->id === $oRecData->id && $oLog->args->operator === $operatorId) {
							if (!empty($oLog->coin)) {
								$oUpdatedUsrData->user_total_coin = -1 * (int) $oLog->coin;
							}
							break;
						}
					}
				}
			}
		}

		$oUser = (object) ['uid' => $oRecData->userid];

		$this->_updateUsrData($oApp, $oRecData->rid, true, $oUser, $oUpdatedUsrData);

		return $oUpdatedUsrData;
	}
	/**
	 * 返回活动事件日志
	 */
	public function logByApp($oApp, $oOptions = []) {
		$fields = empty($oOptions['fields']) ? '*' : $oOptions['fields'];
		$q = [
			$fields,
			'xxt_enroll_log',
			"aid='{$oApp->id}'",
		];

		/* 按用户筛选 */
		if (isset($oOptions['user']) && is_object($oOptions['user'])) {
			$oUser = $oOptions['user'];
			if (!empty($oUser->uid)) {
				$q[2] .= " and(userid='{$oUser->uid}' or owner_userid='{$oUser->uid}')";
			}
		}
		$q2 = ['o' => 'event_at desc'];

		/* 查询结果分页 */
		if (isset($oOptions['page']) && is_object($oOptions['page'])) {
			$oPage = $oOptions['page'];
		} else {
			$oPage = (object) ['at' => 1, 'size' => 30];
		}
		$q2['r'] = ['o' => ((int) $oPage->at - 1) * (int) $oPage->size, 'l' => (int) $oPage->size];

		$logs = $this->query_objs_ss($q, $q2);

		$oResult = new \stdClass;
		$oResult->logs = $logs;
		/* 符合条件的数据总数 */
		if (count($logs) < (int) $oPage->size) {
			$oResult->total = ((int) $oPage->at - 1) * (int) $oPage->size + count($logs);
		} else {
			$q[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q);
			$oResult->total = $total;
		}

		return $oResult;
	}
}