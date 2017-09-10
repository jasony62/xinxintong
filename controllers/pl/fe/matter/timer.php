<?php
namespace pl\fe\matter;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 定时任务
 */
class timer extends \pl\fe\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';

		return $rule_action;
	}
	/**
	 *
	 */
	public function get_action($task) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTim = $this->model('matter\timer');
		$oTask = $modelTim->byId($task);

		return new \ResponseData($oTask);
	}
	/**
	 *
	 */
	public function byMatter_action($type, $id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTim = $this->model('matter\timer');
		$tasks = $modelTim->byMatter($type, $id);

		return new \ResponseData($tasks);
	}
	/**
	 * 添加定时任务
	 */
	public function create_action($site) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$oConfig = $this->getPostJson();

		$oTimer = new \stdClass;
		$oTimer->matter_type = $oConfig->matter->type;
		$oTimer->matter_id = $oConfig->matter->id;
		$oTimer->siteid = $site;
		$oTimer->enabled = 'N';

		$oTimer->task_model = $oConfig->task->model;
		!empty($oConfig->task->arguments) && $oTimer->task_arguments = $oConfig->task->arguments;
		$oTimer->task_expire_at = isset($oTimer->expireAt) ? $oTimer->expireAt : 0;

		if (isset($oConfig->timer)) {
			isset($oConfig->timer->min) && $oTimer->min = $oConfig->timer->min;
			isset($oConfig->timer->hour) && $oTimer->hour = $oConfig->timer->hour;
			isset($oConfig->timer->mday) && $oTimer->mday = $oConfig->timer->mday;
			isset($oConfig->timer->mon) && $oTimer->mon = $oConfig->timer->mon;
			isset($oConfig->timer->wday) && $oTimer->wday = $oConfig->timer->wday;
			isset($oConfig->timer->lelf_count) && $oTimer->left_count = $oConfig->timer->left_count;
		} else {
			$oTimer->pattern = 'W';
			$oTimer->mday = $oTimer->mon = $oTimer->wday = -1;
			$oTimer->min = 0;
			$oTimer->hour = 8;
			$oTimer->left_count = 1;
		}

		$modelTim = $this->model('matter\timer');
		$oTimer->id = $modelTim->insert('xxt_timer_task', $oTimer, true);

		return new \ResponseData($oTimer);
	}
	/**
	 * 更新定时任务属性信息
	 */
	public function update_action($site, $id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oNewUpdate = $this->getPostJson();

		/* 计算定时时间模式 */
		if (empty($oNewUpdate->pattern)) {
			return new \ParameterError('没有指定定时任务时间周期');
		}
		$pattern = $oNewUpdate->pattern;

		/* 时间规则 */
		switch ($pattern) {
		case 'Y': // year
			$oNewUpdate->wday = -1;
			break;
		case 'M': // month
			$oNewUpdate->mon = -1;
			$oNewUpdate->wday = -1;
			break;
		case 'W': // week
			$oNewUpdate->mon = -1;
			$oNewUpdate->mday = -1;
			break;
		default:
			return new \ParameterError('指定了不支持的定时任务时间周期【' . $pattern . '】');
		}

		$rst = $this->model()->update(
			'xxt_timer_task',
			$oNewUpdate,
			['siteid' => $site, 'id' => $id]
		);

		return new \ResponseData($oNewUpdate);
	}
	/**
	 * 删除定时任务
	 */
	public function remove_action($id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rsp = $this->model()->delete('xxt_timer_task', ['id' => $id]);

		return new \ResponseData($rsp);
	}
}
