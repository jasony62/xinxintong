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
	 * 获得素材下的定时任务
	 */
	public function &byMatter($type, $id, $aOptions = []) {
		$q = [
			'*',
			'xxt_timer_task',
			['matter_type' => $type, 'matter_id' => $id],
		];
		if (!empty($aOptions['model'])) {
			$q[2]['task_model'] = $aOptions['model'];
		}

		$tasks = $this->query_objs_ss($q);
		foreach ($tasks as $oTask) {
			if (property_exists($oTask, 'task_arguments')) {
				$oTask->task_arguments = empty($oTask->task_arguments) ? new \stdClass : json_decode($oTask->task_arguments);
			}
		}

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
			"enabled='Y' and task_expire_at>={$now}",
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

			$oTask = $this->_scheduleToTask($oSchedule);

			$tasks[] = $oTask;
		}

		return $tasks;
	}
	/**
	 * 生成执行任务
	 */
	private function &_scheduleToTask($oSchedule) {
		$oTask = new \stdClass;
		$oTask->id = $oSchedule->id;
		$oTask->siteid = $oSchedule->siteid;
		$oTask->task_expire_at = $oSchedule->task_expire_at;

		$oTask->model = $this->model('matter\task\\' . $oSchedule->task_model);

		$oMatter = new \stdClass;
		$oMatter->id = $oSchedule->matter_id;
		$oMatter->type = $oSchedule->matter_type;
		$oTask->matter = $oMatter;

		if (!empty($oSchedule->task_arguments)) {
			$oTask->arguments = json_decode($oSchedule->task_arguments);
		}

		return $oTask;
	}
	/**
	 * 根据素材的轮次定时生成规则，更新关联的定时任务
	 */
	public function updateByRoundCron($oMatter) {
		if (empty($oMatter->roundCron)) {
			$this->update(
				$this->table(),
				['enabled' => 'N', 'offset_matter_id' => ''],
				['matter_type' => $oMatter->type, 'matter_id' => $oMatter->id, 'offset_matter_type' => 'RC']
			);
			return true;
		}
		/* 所有设置了和参考轮次规则的相对时间的任务 */
		$q = [
			'*',
			$this->table(),
			['matter_type' => $oMatter->type, 'matter_id' => $oMatter->id, 'offset_matter_type' => 'RC'],
		];
		$tasks = $this->query_objs_ss($q);
		if (empty($tasks)) {return true;}

		$oCronById = new \stdClass;
		foreach ($oMatter->roundCron as $oCron) {
			$oCronById->{$oCron->id} = $oCron;
		}

		foreach ($tasks as $oTask) {
			if (!empty($oTask->offset_matter_id) && !isset($oCronById->{$oTask->offset_matter_id})) {
				/* 参照的对象不存在 */
				$this->update(
					$this->table(),
					['enabled' => 'N', 'offset_matter_id' => ''],
					['id' => $oTask->id]
				);
			} else {
				$oResult = $this->setTimeByRoundCron($oTask, $oCron);
				if (false === $oResult[0]) {
					/* 无法设置有效的时间 */
					$this->update(
						$this->table(),
						['enabled' => 'N', 'offset_matter_id' => ''],
						['id' => $oTask->id]
					);
				}
			}
		}

		return true;
	}
	/**
	 * 根据轮次的定时规则设置任务的执行时间
	 */
	public function setTimeByRoundCron($oTask, $oCron, $bPersit = true) {
		$oNewUpdate = new \stdClass;
		switch ($oCron->period) {
		case 'D':
			$oNewUpdate->pattern = 'W';
			$oNewUpdate->wday = '-1';
			break;
		case 'W':
			$oNewUpdate->pattern = 'W';
			$oNewUpdate->wday = $oCron->wday;
			break;
		case 'M':
			$oNewUpdate->pattern = 'M';
			$oNewUpdate->mday = $oCron->mday;
			break;
		}
		if ((isset($oTask->offset_hour) && $oTask->offset_hour) || (isset($oTask->offset_min) && $oTask->offset_min)) {
			switch ($oTask->offset_mode) {
			case 'AS':
				$oNewUpdate->hour = $oCron->hour + (isset($oTask->offset_hour) ? $oTask->offset_hour : 0);
				if ($oNewUpdate->hour > 23) {
					return [false, '定时任务的相对时间设置超出范围'];
				}
				$oNewUpdate->min = isset($oTask->offset_min) ? $oTask->offset_min : 0;
				break;
			case 'BE':
				$oNewUpdate->hour = $oCron->end_hour - (isset($oTask->offset_hour) ? $oTask->offset_hour : 0);
				if (isset($oTask->offset_min) && $oTask->offset_min > 0) {
					$oNewUpdate->hour--;
					$oNewUpdate->min = 60 - (isset($oTask->offset_min) ? $oTask->offset_min : 0);
				} else {
					$oNewUpdate->min = 0;
				}
				if ($oNewUpdate->hour < 0) {
					return [false, '定时任务的相对时间设置超出范围'];
				}
				break;
			}
		} else {
			$oNewUpdate->hour = $oCron->hour;
			$oNewUpdate->min = 0;
		}
		if ($bPersit) {
			$this->update($this->table(), $oNewUpdate, ['id' => $oTask->id]);
		}

		return [true, $oNewUpdate];
	}
}
