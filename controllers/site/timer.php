<?php
namespace site;

require_once dirname(__FILE__) . '/base.php';
/**
 *
 */
class timer extends base {
	/**
	 * 执行定时任务
	 */
	public function exec_action() {
		/**
		 * 查找匹配的定时任务
		 */
		$modelTimer = $this->model('matter\timer');
		$tasks = $modelTimer->tasksByTime();
		/**
		 * 记录日志
		 */
		foreach ($tasks as $oTask) {
			$rsp = $oTask->model->exec($oTask->matter, isset($oTask->arguments) ? $oTask->arguments : null);
			$log = [
				'siteid' => $oTask->siteid,
				'task_id' => $oTask->id,
				'occur_at' => time(),
				'result' => $rsp[0] ? 'true' : (is_string($rsp[1]) ? $rsp[1] : $modelTimer->toJson($rsp[1])),
			];
			$modelTimer->insert('xxt_log_timer', $log, true);
		}

		return new \ResponseData(count($tasks));
	}
}