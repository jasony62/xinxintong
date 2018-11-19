<?php
namespace pl\fe\matter\enroll;

require_once dirname(__FILE__) . '/main_base.php';
/*
 * 记录活动用户的行为轨迹
 */
class trace extends main_base {
	/**
	 * 获得指定活动中指定的用户的行为轨迹
	 */
	public function byUser_action($app, $user, $page = 1, $size = 30) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$modelEnl = $this->model('matter\enroll');
		$oEnlApp = $modelEnl->byId($app, ['fields' => 'id,state', 'cascaded' => 'N']);
		if (false === $oEnlApp) {
			return new \ObjectNotFoundError();
		}

		$fields = 'id,rid,page,record_id,topic_id,event_first,event_first_at,event_end,event_elapse,events,user_agent';
		$q = [
			$fields,
			'xxt_enroll_trace',
			['aid' => $oEnlApp->id, 'userid' => $user],
		];
		$q2 = [
			'r' => ['o' => ($page - 1) * $size, 'l' => $size],
			'o' => 'event_first_at desc',
		];
		$logs = $modelEnl->query_objs_ss($q, $q2);
		if (count($logs)) {
			foreach ($logs as $oLog) {
				$oLog->events = empty($oLog->events) ? new \stdClass : json_decode($oLog->events);
			}
		}

		$oResult = new \stdClass;
		$oResult->logs = $logs;

		$q[0] = 'count(*)';
		$oResult->total = (int) $modelEnl->query_val_ss($q);

		return new \ResponseData($oResult);
	}
	/**
	 * 获得指定活动中指定的操作的行为轨迹
	 */
	public function byBiz_action($app, $biz, $page = 1, $size = 30) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}
		if (empty($biz)) {
			return new \ResponseError('需要指定查询的操作名称');
		}

		$modelEnl = $this->model('matter\enroll');
		$oEnlApp = $modelEnl->byId($app, ['fields' => 'id,state', 'cascaded' => 'N']);
		if (false === $oEnlApp) {
			return new \ObjectNotFoundError();
		}

		$fields = 'id,rid,nickname,page,record_id,topic_id,event_first,event_first_at,event_end,event_elapse,events,user_agent';
		$q = [
			$fields,
			'xxt_enroll_trace',
			['aid' => $oEnlApp->id, 'events' => (object) ['op' => 'like', 'pat' => '%' . $biz . '%']],
		];
		$q2 = [
			'r' => ['o' => ($page - 1) * $size, 'l' => $size],
			'o' => 'event_first_at desc',
		];
		$logs = $modelEnl->query_objs_ss($q, $q2);
		if (count($logs)) {
			foreach ($logs as $oLog) {
				$oLog->events = empty($oLog->events) ? new \stdClass : json_decode($oLog->events);
			}
		}

		$oResult = new \stdClass;
		$oResult->logs = $logs;

		$q[0] = 'count(*)';
		$oResult->total = (int) $modelEnl->query_val_ss($q);

		return new \ResponseData($oResult);
	}
}