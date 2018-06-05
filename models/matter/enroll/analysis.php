<?php
namespace matter\enroll;
/**
 * 
 */
class analysis_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function submit($site, $oApp, $rid, $user, $eventData, $page, $recordId = 0, $topicId = 0, $client) {
		$events = $eventData->events;
		if (empty($events)) {
			$data = [false, '没有获取到任何事件'];
			return $data;
		}
		
		// 获取第一次事件
		$eventFirst = $events[0];
		// 获取最后一次事件
		$length = count($events);
		$eventEnd = $events[$length-1];
		// 事件总时长
		$eventElapse = round($eventEnd->elapse / 1000);
		// 事件开始时间
		$eventStartAt = round($eventData->start / 1000);
		// 事件结束时间
		$eventEndAt = $eventStartAt + $eventElapse;

		$inData = [];
		$inData['siteid'] = $site;
		$inData['aid'] = $oApp->id;
		$inData['rid'] = $rid;
		$inData['page'] = $page;
		if ($page === 'cowork') {
			$inData['record_id'] = $recordId;
		} else if ($page === 'topic') {
			$inData['topic_id'] = $topicId;
		}
		$inData['userid'] = $user->uid;
		$inData['nickname'] = $user->nickname;
		$inData['event_first'] = $eventFirst->type;
		$inData['event_first_at'] = $eventStartAt;
		$inData['event_end'] = $eventEnd->type;
		$inData['event_end_at'] = $eventEndAt;
		$inData['event_elapse'] = $eventElapse;
		$inData['events'] = json_encode($eventData);
		$inData['user_agent'] = $client->agent;
		$inData['client_ip'] = $client->ip;
		
		$inData['id'] = $this->insert('xxt_enroll_trace', $inData, true);

		// 更新用户信息
		if ($page === 'cowork') {
			$upUserData = new \stdClass;
			$upUserData->do_cowork_read_elapse = $eventElapse;
			// 查询记录提交者
			$creater = $this->model('matter\enroll\record')->byPlainId($recordId, ['fields' => 'userid uid,rid,nickname', 'verbose' => 'N']);
			if ($creater) {
				$upCreaterData = new \stdClass;
				$upCreaterData->cowork_read_elapse = $eventElapse;
				$rid = $creater->rid;
				unset($creater->rid);
			}
		} else if ($page === 'topic') {
			$upUserData = new \stdClass;
			$upUserData->do_topic_read_elapse = $eventElapse;
			// 查询专题页创建者
			$creater = $this->model('matter\enroll\topic')->byId($topicId, ['fields' => 'userid uid,nickname']);
			if ($creater) {
				$upCreaterData = new \stdClass;
				$upCreaterData->topic_read_elapse = $eventElapse;
			}
		} else {
			$upUserData = new \stdClass;
			$upUserData->do_repos_read_elapse = $eventElapse;
		}

		$modelEvent = $this->model('matter\enroll\event');
		$modelEvent->_updateUsrData($oApp, $rid, false, $user, $upUserData);
		// 更新被阅读者轮次数据
		if (!empty($upCreaterData)) {
			$modelEvent->_updateUsrData($oApp, $rid, false, $creater, $upCreaterData);
		}

		$data = [true, $inData];
		return $data;
	}
}