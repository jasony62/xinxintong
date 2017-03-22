<?php
namespace matter;

require_once dirname(__FILE__) . '/base.php';
/**
 * 推送素材任务
 */
class TaskPush {
	//
	private $siteid;
	//
	private $taksId;
	/**
	 *
	 */
	public function __construct($site, $taskId) {
		$this->id = $taskId;
		$this->siteid = $site;
	}
	/**
	 *
	 */
	public function __get($property_name) {
		if (isset($this->$property_name)) {
			return $this->$property_name;
		} else {
			return null;
		}

	}
	/**
	 * 执行任务
	 */
	public function exec() {
		return new \ResponseData('ok');
	}
}
/**
 * 定时推送事件
 */
class timer_model extends base_model {
	/**
	 *
	 */
	protected function table() {
		return 'xxt_timer_push';
	}
	/*
		*
	*/
	public function getTypeName() {
		return 'timer';
	}
	/**
	 * 获得定义的转发接口
	 */
	public function &bySite($site, $enabled = null) {
		$q = array(
			'*',
			'xxt_timer_push',
			['siteid' => $site]
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
		$mday = date('j');
		$wday = date('w');

		$q = array(
			'*',
			'xxt_timer_push',
			"enabled = 'Y'"
		);
		$q[2] .= " and (hour=-1 or hour=$hour)";
		$q[2] .= " and (mday=-1 or mday=$mday)";
		$q[2] .= " and (wday=-1 or wday=$wday)";

		$schedules = $this->query_objs_ss($q);

		$tasks = array();
		foreach ($schedules as $schedule) {
			$task = new TaskPush($schedule->siteid, $schedule->id);
			$tasks[] = $task;
		}

		return $tasks;
	}
}
