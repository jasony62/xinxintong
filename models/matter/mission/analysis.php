<?php
namespace matter\mission;
/**
 *
 */
class analysis_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function submit($oMission, $oUser, $oEventData, $page, $oClient) {
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
		$aNewTrace['siteid'] = $oMission->siteid;
		$aNewTrace['mission_id'] = $oMission->id;
		$aNewTrace['page'] = $page;
		$aNewTrace['userid'] = $oUser->uid;
		$aNewTrace['nickname'] = $oUser->nickname;
		$aNewTrace['event_first'] = $oEventFirst->type;
		$aNewTrace['event_first_at'] = $eventStartAt;
		$aNewTrace['event_end'] = $eventEnd->type;
		$aNewTrace['event_end_at'] = $eventEndAt;
		$aNewTrace['event_elapse'] = $eventElapse;
		$aNewTrace['events'] = $this->escape(json_encode($oEventData));
		$aNewTrace['user_agent'] = $oClient->agent;
		$aNewTrace['client_ip'] = $oClient->ip;

		$aNewTrace['id'] = $this->insert('xxt_mission_trace', $aNewTrace, true);

		// 更新用户信息
		$oUpdUserData = new \stdClass;
		$oUpdUserData->total_elapse = $eventElapse;
		if ($oEventFirst->type === 'load') {
			/* 打开页面 */
			$oUpdUserData->entry_num = 1;
			$oUpdUserData->last_entry_at = $eventStartAt;
		}

		$modelEvent = $this->model('matter\enroll\event');
		$modelEvent->updateMisUsrData($oMission, false, $oUser, $oUpdUserData);

		return [true, $aNewTrace];
	}
}