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
			$aRsp = $oTask->model->exec($oTask->matter, isset($oTask->arguments) ? $oTask->arguments : null);
			if (false === $aRsp[0]) {
				$invalidCause = empty($aRsp[1]) ? '' : (is_string($aRsp[1]) ? $aRsp[1] : $modelTim->toJson($aRsp[1]));
			}
			/* 记录日志 */
			$oLog = [
				'siteid' => $oTask->siteid,
				'task_id' => $oTask->id,
				'occur_at' => time(),
				'result' => $aRsp[0] ? 'true' : $invalidCause,
			];
			$modelTim->insert('xxt_log_timer', $oLog, true);
			/* 更新任务状态 */
			if (false == $aRsp[0]) {
				$modelTim->update('xxt_timer_task', ['enabled' => 'N', 'invalid_cause' => $invalidCause], ['id' => $oTask->id]);
			}
		}

		return new \ResponseData(count($tasks));
	}
}