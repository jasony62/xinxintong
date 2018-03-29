<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动用户动态
 */
class event extends base {
	/**
	 * 列出指定活动中和当前用户相关的事件
	 */
	public function timeline_action($app, $scope = 'A', $page = 1, $size = 30) {
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$oUser = $this->getUser($oApp);

		$modelEvt = $this->model('matter\enroll\event');
		$fields = 'rid,enroll_key,event_at,event_name,event_op,group_id,userid,nickname,earn_coin,owner_userid,owner_nickname,owner_earn_coin,target_id,target_type';
		$oOptions = ['fields' => $fields];
		if ($scope === 'M') {
			$oOptions['user'] = $oUser;
		}
		$oOptions['page'] = (object) ['at' => $page, 'size' => $size];

		$oResult = $modelEvt->logByApp($oApp, $oOptions);
		if (count($oResult->logs)) {
			$modelRec = $this->model('matter\enroll\record');
			$recordReposLikeNum = 0;
			if (isset($oApp->actionRule->record->repos->pre)) {
				$oRule = $oApp->actionRule->record->repos->pre;
				if (!empty($oRule->record->likeNum)) {
					$recordReposLikeNum = (int) $oRule->record->likeNum;
				}
			}
			foreach ($oResult->logs as $oLog) {
				if ($oLog->userid === $oUser->uid) {
					$oLog->nickname = '你';
					$oLog->canGotoRemark = true;
				}
				if ($oLog->owner_userid === $oUser->uid) {
					$oLog->owner_nickname = '你';
					$oLog->canGotoRemark = true;
				}
				if (isset($oUser->is_leader) && $oUser->is_leader === 'S') {
					$oLog->canGotoRemark = true;
				}
				if (empty($oLog->canGotoRemark)) {
					$oRecord = $modelRec->byId($oLog->enroll_key, ['fields' => 'group_id,agreed,like_num']);
					if ($oRecord) {
						if ($oRecord->agreed === 'Y') {
							$oLog->canGotoRemark = true;
						} else if (!empty($oRecord->group_id)) {
							/* 如果是分组内的数据，只有组内成员，或者组内成员投票达到共享要求 */
							if (!empty($oUser->group_id) && $oRecord->group_id === $oUser->group_id) {
								$oLog->canGotoRemark = true;
							} else if ($recordReposLikeNum > 0) {
								if ($oRecord->like_num >= $recordReposLikeNum) {
									$oLog->canGotoRemark = true;
								}
							}
						} else {
							if ($oRecord->agreed !== 'D' && $oRecord->agreed !== 'N') {
								$oLog->canGotoRemark = true;
							}
						}
					}
				}
			}
		}

		return new \ResponseData($oResult);
	}
}