<?php
namespace site;

require_once dirname(__FILE__) . '/base.php';
/**
 * 定时任务控制器
 */
class timer extends base {
	/**
	 * 执行定时任务
	 */
	public function timer_action() {
		/**
		 * 查找匹配的定时任务
		 */
		$modelTimer = $this->model('matter\timer');
		$tasks = $modelTimer->tasksByTime();
		/**
		 * 记录日志
		 */
		foreach ($tasks as $task) {
			$rsp = $task->exec();
			$log = array(
				'siteid' => $task->siteid,
				'task_id' => $task->id,
				'occur_at' => time(),
				'result' => json_encode($rsp),
			);
			$modelTimer->insert('xxt_log_timer', $log, true);
		}

		return new \ResponseData(count($tasks));
	}
}