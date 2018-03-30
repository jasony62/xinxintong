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
	/**
	 * 动态相关任务
	 */
	public function task_action($app) {
		$modelApp = $this->model('matter\enroll');

		$oApp = $modelApp->byId($app, ['cascaded' => 'N', 'fields' => 'id,siteid,state,entry_rule,action_rule']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$modelRnd = $this->model('matter\enroll\round');
		$oActiveRnd = $modelRnd->getActive($oApp);

		$oUser = $this->getUser($oApp);

		$oActionRule = $oApp->actionRule;

		$tasks = [];
		/* 对提交填写记录数量有要求 */
		if (isset($oActionRule->record->submit->end)) {
			$oRule = $oActionRule->record->submit->end;
			if (!empty($oRule->min)) {
				$oRecords = $this->model('matter\enroll\record')->byUser($oApp, $oUser, ['fields' => 'id', 'rid' => empty($oActiveRnd) ? '' : $oActiveRnd->rid]);
				if (count($oRecords) >= $oRule->min) {
					$oRule->_ok = [count($oRecords)];
				} else {
					$oRule->_no = [(int) $oRule->min - count($oRecords)];
					$desc = empty($oRule->desc) ? ('每轮次每人需提交【' . $oRule->min . '条】记录') : $oRule->desc;
					if (!in_array(mb_substr($desc, -1), ['。', '，', '；', '.', ',', ';'])) {
						$desc .= '，';
					}
					$desc .= '还需提交【' . ((int) $oRule->min - count($oRecords)) . '条】。';
					$oRule->desc = $desc;
				}
				$oRule->id = 'record.submit.end';
				/* 积分奖励 */
				require_once TMS_APP_DIR . '/models/matter/enroll/event.php';
				$modelCoinRule = $this->model('matter\enroll\coin');
				$aCoin = $modelCoinRule->coinByMatter(\matter\enroll\event_model::SubmitEventName, $oApp);
				if ($aCoin && $aCoin[0]) {
					$oRule->coin = $aCoin[1];
				}
				$tasks[] = $oRule;
			}
		}
		/* 对开启点赞有要求 */
		if (isset($oActionRule->record->like->pre)) {
			$oRule = $oActionRule->record->like->pre;
			if (!empty($oRule->record->num)) {
				if (!empty($oUser->is_leader) || $oUser->is_leader !== 'S') {
					if (!empty($oUser->group_id)) {
						$oCriteria = new \stdClass;
						$oCriteria->record = (object) ['rid' => empty($oActiveRnd) ? '' : $oActiveRnd->rid];
						$oCriteria->record->group_id = $oUser->group_id;
						$oResult = $this->model('matter\enroll\record')->byApp($oApp, null, $oCriteria);
						if ((int) $oResult->total >= (int) $oRule->record->num) {
							$oRule->_ok = [(int) $oResult->total];
						} else {
							$oRule->_no = [(int) $oRule->record->num - (int) $oResult->total];
							$desc = empty($oRule->desc) ? ('每轮次每组提交【' . $oRule->record->num . '条】记录后开启点赞（投票）') : $oRule->desc;
							if (!in_array(mb_substr($desc, -1), ['。', '，', '；', '.', ',', ';'])) {
								$desc .= '，';
							}
							$oRule->desc .= '还需提交【' . ((int) $oRule->record->num - (int) $oResult->total) . '条】记录。';
						}
						$oRule->id = 'record.like.pre';
						$tasks[] = $oRule;
					}
				}
			}
		}
		/* 对提交填写记录的投票数量有要求 */
		if (isset($oActionRule->record->like->end)) {
			$oRule = $oActionRule->record->like->end;
			if (!empty($oRule->min)) {
				$oAppUser = $this->model('matter\enroll\user')->byId($oApp, $oUser->uid, ['fields' => 'id,do_like_num', 'rid' => empty($oActiveRnd) ? '' : $oActiveRnd->rid]);
				if ($oAppUser) {
					if ($oAppUser && (int) $oAppUser->do_like_num >= (int) $oRule->min) {
						$oRule->_ok = [(int) $oAppUser->do_like_num];
					} else {
						$oRule->_no = [(int) $oRule->min - (int) $oAppUser->do_like_num];
					}
				} else {
					$oRule->_no = [(int) $oRule->min];
				}
				$oRule->id = 'record.like.end';
				$tasks[] = $oRule;
			}
		}
		/* 对组长的任务要求 */
		if (!empty($oUser->group_id) && isset($oUser->is_leader) && $oUser->is_leader === 'Y') {
			/* 对组长推荐记录的要求 */
			if (isset($oActionRule->leader->record->agree->end)) {
				$oRule = $oActionRule->leader->record->agree->end;
				if (!empty($oRule->min)) {
					$oCriteria = new \stdClass;
					$oCriteria->record = (object) [
						'rid' => isset($oActiveRnd) ? $oActiveRnd->rid : '',
						'group_id' => $oUser->group_id,
						'agreed' => 'Y',
					];
					$oResult = $this->model('matter\enroll\record')->byApp($oApp, null, $oCriteria);
					if ($oResult->total >= $oRule->min) {
						$oRule->_ok = [(int) $oResult->total];
					} else {
						$oRule->_no = [(int) $oRule->min - (int) $oResult->total];
						$desc = empty($oRule->desc) ? ('每轮次组长需要推荐【' . $oRule->min . ((int) $oRule->max > (int) $oRule->min ? ('-' . $oRule->max) : '') . '条】记录') : $oRule->desc;
						if (!in_array(mb_substr($desc, -1), ['。', '，', '；', '.', ',', ';'])) {
							$desc .= '，';
						}
						$desc .= '还需推荐【' . ((int) $oRule->min - (int) $oResult->total) . '条】。';
						$oRule->desc = $desc;
					}
					$oRule->id = 'leader.record.agree.end';
					$tasks[] = $oRule;
				}
			}
		}

		return new \ResponseData($tasks);
	}
}