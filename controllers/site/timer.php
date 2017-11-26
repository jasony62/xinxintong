<?php
namespace site;

require_once dirname(__FILE__) . '/base.php';
/**
 * 执行定时任务控制器
 */
class timer extends base {
	/**
	 * 执行定时任务
	 */
	public function exec_action() {
		/* 查找匹配的定时任务 */
		$modelTim = $this->model('matter\timer');
		$tasks = $modelTim->tasksByTime();

		foreach ($tasks as $oTask) {
			/* 执行任务 */
			$rsp = $oTask->model->exec($oTask->matter, isset($oTask->arguments) ? $oTask->arguments : null);

			/* 记录日志 */
			$oLog = [
				'siteid' => $oTask->siteid,
				'task_id' => $oTask->id,
				'occur_at' => time(),
				'result' => $rsp[0] ? 'true' : (is_string($rsp[1]) ? $rsp[1] : $modelTim->toJson($rsp[1])),
			];
			$modelTim->insert('xxt_log_timer', $oLog, true);

			/* 更新任务状态 */
			if (false == $rsp[0]) {
				$modelTim->update('update xxt_timer_task set enabled=\'N\' where id=' . $oTask->id);
			} else {
				$modelTim->update('update xxt_timer_task set left_count=left_count-1 where id=' . $oTask->id);
			}
		}

		return new \ResponseData(count($tasks));
	}
}