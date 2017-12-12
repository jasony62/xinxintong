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
		$now = time();
		$min = (int) date('i'); // 0-59
		$hour = date('G'); // 0-23
		$mday = date('j'); // 1-31
		$wday = date('w'); // 0-6（周日到周一）
		$mon = date('n'); // 1-12

		$q = [
			'*',
			'xxt_timer_task',
			"enabled='Y' and ((task_expire_at=0 and left_count>0) or task_expire_at>={$now})",
		];
		$q[2] .= " and (min=-1 or min=$min)";
		$q[2] .= " and (hour=-1 or hour=$hour)";
		$q[2] .= " and (mday=-1 or mday=$mday)";
		$q[2] .= " and (wday=-1 or wday=$wday)";
		$q[2] .= " and (mon=-1 or mon=$mon)";

		$schedules = $this->query_objs_ss($q);

		$tasks = [];
		foreach ($schedules as $oSchedule) {
			if (empty($oSchedule->task_model) || empty($oSchedule->matter_id) || empty($oSchedule->matter_type)) {
				continue;
			}
			if ($oSchedule->notweekend === 'Y') {
				if ($oSchedule->wday === '-1' && ($wday === '0' || $wday === '6')) {
					continue;
				}
			}
			$oTask = new \stdClass;
			$oTask->id = $oSchedule->id;
			$oTask->siteid = $oSchedule->siteid;
			if ($oSchedule->task_expire_at > 0) {
				$oTask->task_expire_at = $oSchedule->task_expire_at;
			} else {
				$oTask->left_count = $oSchedule->left_count;
			}

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
