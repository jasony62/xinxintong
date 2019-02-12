<?php
namespace matter\task;
/**
 * 提醒未完成任务
 */
class undone_model extends \TMS_MODEL {
	/**
	 * 执行活动任务提醒任务
	 *
	 * @param object $oMatter
	 * @param mix $arguments
	 *
	 * @return array
	 */
	public function exec($oMatter, $arguments = null) {
		$oArguments = empty($arguments) ? new \stdClass : (is_object($arguments) ? $arguments : json_decode($arguments));
		if (empty($oArguments->receiver->scope) || !is_array($oArguments->receiver->scope)) {
			return [false, '没有指定有效的用户范围（1）'];
		}
		$aDiffScope = array_diff($oArguments->receiver->scope, ['user', 'leader', 'group']);
		if (!empty($aDiffScope)) {
			return [false, '没有指定有效的用户范围（2）'];
		}

		switch ($oMatter->type) {
		case 'enroll':
			$aResult = $this->_enroll($oMatter, $oArguments);
			break;
		default:
			return [false, '不支持的活动类型【' . $oMatter->type . '】'];
		}

		if (false === $aResult[0]) {
			return $aResult;
		}

		return [true];
	}
	/**
	 * 记录活动提醒通知
	 */
	private function _enroll($oEnlApp, $oTaskArgs) {
		$modelEnl = $this->model('matter\enroll');
		$modelUsr = $this->model('matter\enroll\user');

		$oEnlApp = $modelEnl->byId($oEnlApp->id, ['cascaded' => 'N']);
		if (false === $oEnlApp || $oEnlApp->state !== '1') {
			return [false, '指定的活动不存在，或已不可用'];
		}

		/* 没有完成任务的用户 */
		$oResult = $modelUsr->undoneByApp($oEnlApp, $oEnlApp->appRound->rid);
		$aUndoneUsers = $oResult->users;
		if (empty($aUndoneUsers)) {
			return [false, '活动中没有未完成任务的用户'];
		}

		/* 获得活动的进入链接 */
		$noticeURL = $oEnlApp->entryUrl;
		$noticeURL .= '&origin=timer';

		/* 给未完成任务的用户发通知 */
		if (in_array('user', $oTaskArgs->receiver->scope)) {
			// 所有接收通知的用户
			$aUndoneReceivers = array_map(function ($oUndoneUser) {return (object) ['userid' => $oUndoneUser->userid];}, $aUndoneUsers);
			if (count($aUndoneReceivers)) {
				$aResult = $this->sendByRemindTmpl($oEnlApp, $noticeURL, $aUndoneReceivers);
				if (false === $aResult[0]) {
					return $aResult;
				}
			}
		}
		/* 给未完成任务的用户的组长发通知 */
		if (in_array('leader', $oTaskArgs->receiver->scope)) {
			$aLeaderReceivers = [];
			$aUserAndLeaders = $modelUsr->getLeaderByUser($oEnlApp, array_column($aUndoneUsers, 'userid'));
			$aWholeLeaderUserids = array_reduce($aUserAndLeaders, function ($aResult, $aLeaderUserids) {
				return array_unique(array_merge($aResult, $aLeaderUserids));
			}, []);
			foreach ($aWholeLeaderUserids as $leaderUserid) {
				$aLeaderReceivers[] = (object) ['userid' => $leaderUserid];
			}
			if (count($aLeaderReceivers)) {
				$noticeURL .= '&page=kanban#undone';
				$oTmplTimerTaskParams = new \stdClass;
				$oTmplTimerTaskParams->receiver = '组长';
				$oTmplTimerTaskParams->page = '看板页';
				$aResult = $this->sendByReportTmpl($oEnlApp, $noticeURL, $aLeaderReceivers, $oTmplTimerTaskParams);
				if (false === $aResult[0]) {
					return $aResult;
				}
			}
		}
		/* 给指定的分组用户发通知 */
		if (in_array('group', $oTaskArgs->receiver->scope)) {
			if (!empty($oTaskArgs->receiver->group->id)) {
				$oGrpApp = $this->model('matter\group')->byId($oTaskArgs->receiver->group->id, ['fields' => 'title']);
				if ($oGrpApp) {
					$oTmplTimerTaskParams = new \stdClass;
					if (empty($oTaskArgs->receiver->group->team->id)) {
						/* 分组活动内的用户 */
						$q = [
							'distinct userid',
							'xxt_group_record',
							['state' => 1, 'aid' => $oTaskArgs->receiver->group->id],
						];
						$aGrpUsers = $modelEnl->query_objs_ss($q);
						$oTmplTimerTaskParams->receiver = $oGrpApp->title;
					} else {
						/* 指定分组的用户 */
						$oGrpAppTeam = $this->model('matter\group\team')->byId($oTaskArgs->receiver->group->team->id);
						if ($oGrpAppTeam) {
							$aGrpUsers = $this->model('matter\group\record')->byTeam($oTaskArgs->receiver->group->team->id, ['fields' => 'userid']);
							$oTmplTimerTaskParams->receiver = $oGrpAppTeam->title;
						}
					}
					if (empty($aGrpUsers)) {
						return [false, '指定的分组活动中没有符合接受通知条件的用户'];
					}
					$noticeURL .= '&page=kanban#undone';
					$oTmplTimerTaskParams->page = '看板页';
					$aResult = $this->sendByReportTmpl($oEnlApp, $noticeURL, $aGrpUsers, $oTmplTimerTaskParams);
					if (false === $aResult[0]) {
						return $aResult;
					}
				}
			}
		}

		return [true];
	}
	/**
	 * 用提醒模板发送通知
	 */
	protected function sendByRemindTmpl($oMatter, $noticeURL, $aReceivers) {
		$noticeName = 'timer.' . $oMatter->type . '.remind';
		return $this->sendByTmpl($oMatter, $noticeName, $noticeURL, $aReceivers);
	}
	/**
	 * 用报告模板发送通知
	 */
	protected function sendByReportTmpl($oMatter, $noticeURL, $aReceivers, $oTmplTimerTaskParams) {
		$noticeName = 'timer.' . $oMatter->type . '.report';
		return $this->sendByTmpl($oMatter, $noticeName, $noticeURL, $aReceivers, $oTmplTimerTaskParams);
	}
	/**
	 * 通过模板消息发送
	 */
	protected function sendByTmpl($oMatter, $noticeName, $noticeURL, $aReceivers, $oTmplTimerTaskParams = null) {
		/*获取模板消息id*/
		$aTmpOptions = ['onlySite' => false, 'noticeURL' => $noticeURL];
		if (!empty($oTmplTimerTaskParams)) {
			$aTmpOptions['timerTask'] = $oTmplTimerTaskParams;
		}
		$tmpConfig = $this->model('matter\tmplmsg\config')->getTmplConfig($oMatter, $noticeName, $aTmpOptions);
		if ($tmpConfig[0] === false) {
			return [false, $tmpConfig[1]];
		}
		$tmpConfig = $tmpConfig[1];

		$modelTmplBat = $this->model('matter\tmplmsg\batch');
		$oCreator = new \stdClass;
		$oCreator->uid = $noticeName;
		$oCreator->name = 'timer';
		$oCreator->src = 'pl';
		$modelTmplBat->send($oMatter->siteid, $tmpConfig->tmplmsgId, $oCreator, $aReceivers, $tmpConfig->oParams, ['send_from' => $oMatter->type . ':' . $oMatter->id]);

		return [true];
	}
}