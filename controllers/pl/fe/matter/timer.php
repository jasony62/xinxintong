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
	 * 素材指定的定时任务
	 */
	public function byMatter_action($type, $id, $model = '') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$aOptions = ['model' => $model];

		$oPosted = $this->getPostJson();
		if ($oPosted) {
			if (isset($oPosted->taskArguments)) {
				$aOptions['taskArguments'] = $oPosted->taskArguments;
			}
		}

		$modelTim = $this->model('matter\timer');
		$tasks = $modelTim->byMatter($type, $id, $aOptions);

		return new \ResponseData($tasks);
	}
	/**
	 * 给指定素材添加定时任务
	 */
	public function create_action() {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oConfig = $this->getPostJson();

		if (empty($oConfig->matter->type) || empty($oConfig->matter->id)) {
			return new \ParameterError();
		}
		$oMatter = $this->model('matter\\' . $oConfig->matter->type)->byId($oConfig->matter->id, ['fields' => 'id,siteid,round_cron', 'cascaded' => 'N']);
		if (false === $oMatter) {
			return new \ObjectNotFoundError();
		}

		$modelTim = $this->model('matter\timer');

		$oNewTimer = new \stdClass;
		$oNewTimer->matter_type = $oConfig->matter->type;
		$oNewTimer->matter_id = $oConfig->matter->id;
		$oNewTimer->siteid = $oMatter->siteid;
		$oNewTimer->enabled = 'N';

		$oNewTimer->task_model = $oConfig->task->model;
		!empty($oConfig->task->arguments) && $oNewTimer->task_arguments = $this->escape($modelTim->toJson($oConfig->task->arguments));
		$oNewTimer->task_expire_at = isset($oNewTimer->expireAt) ? $oNewTimer->expireAt : 0;

		if (isset($oConfig->timer)) {
			isset($oConfig->timer->offset_matter_type) && $oNewTimer->offset_matter_type = $oConfig->timer->offset_matter_type;
			isset($oConfig->timer->offset_matter_id) && $oNewTimer->offset_matter_id = $oConfig->timer->offset_matter_id;
			$oNewTimer->offset_mode = isset($oConfig->timer->offset_mode) ? $oConfig->timer->offset_mode : 'AS';
			$oNewTimer->offset_hour = isset($oConfig->timer->offset_hour) ? $oConfig->timer->offset_hour : 0;
			$oNewTimer->offset_min = isset($oConfig->timer->offset_min) ? $oConfig->timer->offset_min : 0;
			isset($oConfig->timer->min) && $oNewTimer->min = $oConfig->timer->min;
			isset($oConfig->timer->hour) && $oNewTimer->hour = $oConfig->timer->hour;
			isset($oConfig->timer->mday) && $oNewTimer->mday = $oConfig->timer->mday;
			isset($oConfig->timer->mon) && $oNewTimer->mon = $oConfig->timer->mon;
			isset($oConfig->timer->wday) && $oNewTimer->wday = $oConfig->timer->wday;
			$oNewTimer->enabled = isset($oConfig->timer->enabled) ? $oConfig->timer->enabled : 'N';
		} else {
			$oNewTimer->pattern = 'W';
			$oNewTimer->mday = $oNewTimer->mon = $oNewTimer->wday = -1;
			$oNewTimer->min = 0;
			$oNewTimer->hour = 8;
			$oNewTimer->offset_matter_type = 'N'; // 固定时间，没有参照对象
			$oNewTimer->offset_mode = 'AS';
			$oNewTimer->offset_hour = 0;
			$oNewTimer->offset_min = 0;
		}
		$oNewTimer->notweekend = 'Y';

		switch ($oNewTimer->offset_matter_type) {
		case 'RC':
			foreach ($oMatter->roundCron as $oRule) {
				if ($oRule->id === $oNewTimer->offset_matter_id) {
					$oReferCron = $oRule;
					break;
				}
			}
			if (!isset($oReferCron)) {
				return new \ParameterError('定时任务的相对时间参照的【填写轮次生成规则】不存在');
			}
			$oResult = $modelTim->setTimeByRoundCron($oNewTimer, $oReferCron, false);
			if (false === $oResult[0]) {
				return new \ParameterError($oResult[1]);
			}
			tms_object_merge($oNewTimer, $oResult[1]);
			break;
		}

		$oNewTimer->id = $modelTim->insert('xxt_timer_task', $oNewTimer, true);
		if (!empty($oNewTimer->task_arguments)) {
			$oNewTimer->task_arguments = $oConfig->task->arguments;
		}

		$oNewTimer->name = $modelTim->readableTaskName($oNewTimer);

		return new \ResponseData($oNewTimer);
	}
	/**
	 * 更新定时任务属性信息
	 *
	 * @param int $id 任务ID
	 */
	public function update_action($id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelTim = $this->model('matter\timer');
		$oBeforeTimer = $modelTim->byId($id);
		if (false === $oBeforeTimer) {
			return new \ObjectNotFoundError();
		}

		$oNewUpdate = $this->getPostJson();

		if (empty($oNewUpdate->offset_matter_type) || !in_array($oNewUpdate->offset_matter_type, ['N', 'RC'])) {
			return new \ParameterError('没有指定定时任务的相对时间模式');
		}
		if ($oNewUpdate->offset_matter_type === 'N' && empty($oNewUpdate->pattern)) {
			return new \ParameterError('没有指定定时任务时间周期');
		}
		if ($oNewUpdate->offset_matter_type === 'N' && empty($oNewUpdate->task_expire_at)) {
			return new \ParameterError('没有指定定时任务的【终止时间】');
		}

		switch ($oNewUpdate->offset_matter_type) {
		case 'N': // 固定时间
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
			break;
		case 'RC': // 相对轮次规则的时间
			if (empty($oNewUpdate->offset_matter_id)) {
				return new \ParameterError('没有指定定时任务的相对时间参照的【填写轮次生成规则】');
			}
			if (empty($oNewUpdate->offset_mode) || !in_array($oNewUpdate->offset_mode, ['AS', 'BE'])) {
				return new \ParameterError('没有指定定时任务的相对时间的参照模式');
			}
			$oMatter = $this->model('matter\\' . $oBeforeTimer->matter_type)->byId($oBeforeTimer->matter_id);
			if (false === $oMatter || empty($oMatter->roundCron)) {
				return new \ParameterError('定时任务的相对时间参照的【填写轮次生成规则】不存在');
			}
			foreach ($oMatter->roundCron as $oRule) {
				if ($oRule->id === $oNewUpdate->offset_matter_id) {
					$oReferCron = $oRule;
					break;
				}
			}
			if (!isset($oReferCron)) {
				return new \ParameterError('定时任务的相对时间参照的【填写轮次生成规则】不存在');
			}
			$oResult = $modelTim->setTimeByRoundCron($oNewUpdate, $oReferCron, false);
			if (false === $oResult[0]) {
				return new \ParameterError($oResult[1]);
			}
			foreach ($oResult[1] as $prop => $val) {
				$oNewUpdate->{$prop} = $val;
			}
			break;
		}
		if (isset($oNewUpdate->task_arguments)) {
			$oTaskArguments = $oNewUpdate->task_arguments;
			if (is_object($oNewUpdate->task_arguments)) {
				$oNewUpdate->task_arguments = $modelTim->escape($modelTim->toJson($oNewUpdate->task_arguments));
			}
			$oNewUpdate->task_arguments = $oNewUpdate->task_arguments;
		}

		$rst = $modelTim->update(
			'xxt_timer_task',
			$oNewUpdate,
			['id' => $id]
		);

		if (isset($oTaskArguments)) {
			$oNewUpdate->task_arguments = $oTaskArguments;
		}

		$oNewUpdate->name = $modelTim->readableTaskName($oNewUpdate);

		return new \ResponseData($oNewUpdate);
	}
	/**
	 * 删除定时任务
	 *
	 * @param int $id 任务ID
	 */
	public function remove_action($id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rsp = $this->model()->delete('xxt_timer_task', ['id' => $id]);

		return new \ResponseData($rsp);
	}
}
