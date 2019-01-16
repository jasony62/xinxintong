<?php
namespace matter\enroll;
/**
 *
 */
class analysis_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function submit($oApp, $rid, $oUser, $oEventData, $page, $recordId = 0, $topicId = 0, $oClient) {
		$events = $oEventData->events;
		if (empty($events)) {
			$data = [false, '没有获取到任何事件'];
			return $data;
		}

		// 获取第一次事件
		$oEventFirst = $events[0];
		// 获取最后一次事件
		$length = count($events);
		$eventEnd = $events[$length - 1];
		// 事件总时长
		$eventElapse = round($eventEnd->elapse / 1000);
		// 事件开始时间
		$eventStartAt = round($oEventData->start / 1000);
		// 事件结束时间
		$eventEndAt = $eventStartAt + $eventElapse;

		$aNewTrace = [];
		$aNewTrace['siteid'] = $oApp->siteid;
		$aNewTrace['aid'] = $oApp->id;
		$aNewTrace['rid'] = $rid;
		$aNewTrace['page'] = $page;
		if ($page === 'cowork') {
			$aNewTrace['record_id'] = $recordId;
		} else if ($page === 'topic') {
			$aNewTrace['topic_id'] = $topicId;
		}
		$aNewTrace['userid'] = $oUser->uid;
		$aNewTrace['nickname'] = $this->escape($oUser->nickname);
		$aNewTrace['event_first'] = $oEventFirst->type;
		$aNewTrace['event_first_at'] = $eventStartAt;
		$aNewTrace['event_end'] = $eventEnd->type;
		$aNewTrace['event_end_at'] = $eventEndAt;
		$aNewTrace['event_elapse'] = $eventElapse;
		$aNewTrace['events'] = $this->escape($this->toJson($oEventData));
		$aNewTrace['user_agent'] = $oClient->agent;
		$aNewTrace['client_ip'] = $oClient->ip;

		$aNewTrace['id'] = $this->insert('xxt_enroll_trace', $aNewTrace, true);

		// 更新用户信息
		$oUpdUserData = new \stdClass;
		$oUpdUserData->total_elapse = $eventElapse;
		if ($oEventFirst->type === 'load') {
			/* 打开页面 */
			$oUpdUserData->entry_num = 1;
			$oUpdUserData->last_entry_at = $eventStartAt;
		}

		switch ($page) {
		case 'repos':
			$oUpdUserData->do_repos_read_elapse = $eventElapse;
			break;
		case 'cowork':
			$oUpdUserData = new \stdClass;
			$oUpdUserData->do_cowork_read_elapse = $eventElapse;
			// 查询记录提交者
			$oCreator = $this->model('matter\enroll\record')->byPlainId($recordId, ['fields' => 'userid uid,rid,nickname', 'verbose' => 'N']);
			if ($oCreator) {
				$oUpdCreatorData = new \stdClass;
				$oUpdCreatorData->cowork_read_elapse = $eventElapse;
				$rid = $oCreator->rid;
				unset($oCreator->rid);
			}
			break;
		case 'topic':
			$oUpdUserData = new \stdClass;
			$oUpdUserData->do_topic_read_elapse = $eventElapse;
			// 查询专题页创建者
			$oCreator = $this->model('matter\enroll\topic', $oApp)->byId($topicId, ['fields' => 'userid uid,nickname']);
			if ($oCreator) {
				$oUpdCreatorData = new \stdClass;
				$oUpdCreatorData->topic_read_elapse = $eventElapse;
			}
			break;
		}

		$modelEvent = $this->model('matter\enroll\event');
		$modelEvent->_updateUsrData($oApp, $rid, false, $oUser, $oUpdUserData);

		// 更新被阅读者数据
		if (!empty($oUpdCreatorData)) {
			$modelEvent->_updateUsrData($oApp, $rid, false, $oCreator, $oUpdCreatorData);
		}

		return [true, $aNewTrace];
	}
}