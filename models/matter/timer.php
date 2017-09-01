<?php
namespace matter;

require_once dirname(__FILE__) . '/base.php';
/**
 * 定时推送事件
 */
class timer_model extends base_model {
	/**
	 *
	 */
	protected function table() {
		return 'xxt_timer_task';
	}
	/*
		*
	*/
	public function getTypeName() {
		return 'timer';
	}
	/**
	 *
	 */
	public function &byMatter($type, $id) {
		$q = [
			'*',
			'xxt_timer_task',
			['matter_type' => $type, 'matter_id' => $id],
		];

		$tasks = $this->query_objs_ss($q);

		return $tasks;
	}
	/**
	 *
	 */
	public function &bySite($site, $enabled = null) {
		$q = array(
			'*',
			'xxt_timer_task',
			['siteid' => $site],
		);
		$enabled !== null && $q[2]['enabled'] = $enabled;

		!($timers = $this->query_objs_ss($q)) && $timers = array();

		return $timers;
	}
	/**
	 * 获得当前时间段要执行的任务
	 */
	public function tasksByTime() {
		$hour = date('G');
		$mday = date('j'); // 1-31
		$wday = date('N'); // 1-7

		$q = [
			'*',
			'xxt_timer_task',
			"enabled='Y'",
		];
		$q[2] .= " and (hour=-1 or hour=$hour)";
		$q[2] .= " and (mday=-1 or mday=$mday)";
		$q[2] .= " and (wday=-1 or wday=$wday)";

		$schedules = $this->query_objs_ss($q);

		$tasks = [];
		foreach ($schedules as $oSchedule) {
			if (empty($oSchedule->task_model) || empty($oSchedule->matter_id) || empty($oSchedule->matter_type)) {
				continue;
			}
			$oTask = new \stdClass;
			$oTask->id = $oSchedule->id;
			$oTask->siteid = $oSchedule->siteid;

			$oTask->model = $this->model('matter\task\\' . $oSchedule->task_model);

			$oMatter = new \stdClass;
			$oMatter->id = $oSchedule->matter_id;
			$oMatter->type = $oSchedule->matter_type;
			$oTask->matter = $oMatter;

			if (!empty($oSchedule->task_arguments)) {
				$oTask->arguments = json_decode($oSchedule->task_arguments);
			}

			$tasks[] = $oTask;
		}

		return $tasks;
	}
}
