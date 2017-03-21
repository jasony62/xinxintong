<?php
namespace matter\enroll;

class round_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function byId($roundId, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = [
			$fields,
			'xxt_enroll_round',
			['rid' => $roundId],
		];
		$round = $this->query_obj_ss($q);

		return $round;
	}
	/**
	 * 返回登记活动下的轮次
	 *
	 * @param object $oApp
	 *
	 */
	public function &byApp($oApp, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$state = isset($options['state']) ? $options['state'] : false;
		$page = isset($options['page']) ? $options['page'] : null;

		$q = [
			$fields,
			'xxt_enroll_round',
			['aid' => $oApp->id],
		];
		$state && $q[2]['state'] = [$state];

		$q2 = ['o' => 'create_at desc'];

		!empty($page) && $q2['r'] = ['o' => ($page->num - 1) * $page->size, 'l' => $page->size];

		$result = new \stdClass;
		$result->rounds = $this->query_objs_ss($q, $q2);

		if (!empty($page)) {
			$q[0] = 'count(*)';
			$result->total = $this->query_val_ss($q);
		}

		return $result;
	}
	/**
	 * 添加轮次
	 *
	 * @param object $oApp
	 * @param object $props
	 * @param object $oCreator
	 */
	public function create($oApp, $props, $oCreator = null) {
		if ($lastRound = $this->getLast($oApp)) {
			/**
			 * 检查或更新上一轮状态
			 */
			if ((int) $lastRound->state === 0) {
				return [false, '最近一个轮次【' . $lastRound->title . '】是新建状态，不允许创建新轮次'];
			}
			if ((int) $lastRound->state === 1) {
				$this->update(
					'xxt_enroll_round',
					['state' => 2],
					['aid' => $oApp->id, 'rid' => $lastRound->rid]
				);
			}
		}

		$roundId = uniqid();
		$round = [
			'siteid' => $oApp->siteid,
			'aid' => $oApp->id,
			'rid' => $roundId,
			'creater' => isset($oCreator->id) ? $oCreator->id : '',
			'create_at' => time(),
			'title' => $this->escape($props->title),
			'state' => isset($props->state) ? $props->state : 0,
			'start_at' => empty($props->start_at) ? 0 : $props->start_at,
		];
		$this->insert('xxt_enroll_round', $round, false);

		if (empty($oApp->multi_rounds) || $oApp->multi_rounds !== 'Y') {
			$this->update(
				'xxt_enroll',
				['multi_rounds' => 'Y'],
				['id' => $oApp->id]
			);
		}

		$round = $this->byId($roundId);

		return [true, $round];
	}
	/**
	 * 获得指定登记活动的当前轮次
	 *
	 * @param object $oApp
	 *
	 */
	public function getLast($oApp) {
		$q = [
			'*',
			'xxt_enroll_round',
			['aid' => $oApp->id],
		];
		$q2 = [
			'o' => 'create_at desc',
			'r' => ['o' => 0, 'l' => 1],
		];
		$rounds = $this->query_objs_ss($q, $q2);

		return count($rounds) === 1 ? $rounds[0] : false;
	}
	/**
	 * 获得指定登记活动中启用状态的轮次
	 *
	 * 登记活动只能有一个启用状态的轮次
	 * 如果登记活动设置了轮次定时生成规则，需要检查是否需要自动生成轮次
	 *
	 * @param object $app
	 *
	 */
	public function getActive($oApp, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		if (!empty($oApp->roundCron)) {
			$enabledRules = [];
			foreach ($oApp->roundCron as $rule) {
				if (isset($rule->enabled) && $rule->enabled === 'Y') {
					$enabledRules[] = $rule;
				}
			}
		}

		if (empty($enabledRules)) {
			$q = [
				$fields,
				'xxt_enroll_round',
				["siteid" => $oApp->siteid, "aid" => $oApp->id, "state" => 1],
			];
			$round = $this->query_obj_ss($q);
		} else {
			$rst = $this->_getRoundByCron($oApp, $enabledRules);
			if (false === $rst[0]) {
				return false;
			}
			$round = $rst[1];
		}

		return $round;
	}
	/**
	 * 根据定时规则生成轮次
	 *
	 * @param array $rules 定时生成轮次规则
	 *
	 */
	private function _getRoundByCron($oApp, $rules) {
		if (false === ($cronRound = $this->_lastRoundByCron($rules))) {
			return [false, '无法生成定时轮次'];
		}

		$round = false; // 和定时计划匹配的论次

		/* 检查已经存在的轮次是否满足定时规则 */
		if ($lastRound = $this->getLast($oApp)) {
			if ((int) $lastRound->start_at >= $cronRound->start_at) {
				$round = $lastRound;
			}
		}
		/* 创建新论次 */
		if (false === $round) {
			$rst = $this->create($oApp, $cronRound);
			if (false === $rst[0]) {
				return $rst;
			}
			$round = $rst[1];
		}

		return [true, $round];
	}
	/**
	 * 根据定时规则生成轮次
	 *
	 * 计算每个规则对应的最近一个轮次的生成时间，用最近的时间作为整个活动的最近生成时间
	 *
	 * @param array rules 定时生成轮次规则
	 *
	 */
	private function _lastRoundByCron($rules) {
		$latest = 0;
		$latestLabel = '';
		foreach ($rules as $rule) {
			if (empty($rule->period)) {
				continue;
			}
			/* 根据定时计划计算轮次的开始时间 */
			$hour = (int) date("G"); // 0-23
			$month = (int) date("n"); // 1-12
			$yday = (int) date("z") + 1; // 0-365
			$mday = (int) date("j"); // 1-31
			$year = (int) date("Y"); // yyyy
			$wday = (int) date('w'); //0 (for Sunday) through 6 (for Saturday)
			$week = (int) date('W'); //0-51, ISO-8601 week number of year, weeks starting on Monday

			if ($rule->period === 'M' && !empty($rule->mday)) {
				/* 某个月的某一天 */
				if ($mday === (int) $rule->mday) {
					/* 计算时间 */
					if (empty($rule->hour)) {
						$hour = 0;
					} else {
						if ($hour < (int) $rule->hour) {
							/* 在上个月的指定时间 */
							if ($month > 1) {
								$month--;
							} else {
								$year--;
								$month = 12;
							}
						}
						$hour = (int) $rule->hour;
					}
				} else if ($mday < (int) $rule->mday) {
					// 在上个月的指定日期
					if ($month > 1) {
						$month--;
					} else {
						$month = 12;
						$year--;
					}
				}
				$mday = (int) $rule->mday;
				$hour = empty($rule->hour) ? 0 : (int) $rule->hour;
			} else if ($rule->period === 'W' && isset($rule->wday) && strlen($rule->wday)) {
				/* 某周的某一天 */
				if ($wday === (int) $rule->wday) {
					/* 计算时间 */
					if (empty($rule->hour)) {
						$hour = 0;
					} else {
						if ($hour < (int) $rule->hour) {
							/* 在上一周的指定时间 */
							$mday -= 7;
							$week--;
						}
						$hour = (int) $rule->hour;
					}
				} else if ($wday > (int) $rule->wday) {
					// 在同一周
					$mday -= $wday - (int) $rule->wday;
					$hour = empty($rule->hour) ? 0 : (int) $rule->hour;
				} else {
					// 在上一周
					$mday = $mday - 7 + ((int) $rule->wday - $wday);
					$hour = empty($rule->hour) ? 0 : (int) $rule->hour;
					$week--;
				}
			} else if ($rule->period === 'D' && isset($rule->hour) && strlen($rule->hour)) {
				/* 每天指定时间 */
				if ($hour < (int) $rule->hour) {
					/* 在上个月的指定时间 */
					$yday--;
				}
				$hour = (int) $rule->hour;
			}
			$startAt = mktime($hour, 0, 0, $month, $mday, $year);

			// 记录活动的轮次生成时间
			if ($startAt > $latest) {
				$latest = $startAt;
				switch ($rule->period) {
				case 'M':
					$latestLabel = (int) date('n', $startAt) . '月';
					break;
				case 'W':
					$latestLabel = ((int) date('W', $startAt)) . '周';
					break;
				case 'D':
					$latestLabel = ((int) date('z', $startAt) + 1) . '日';
					break;
				}
			}
		}

		//die("y:$year,m:$month,d:$mday,h:$hour");
		$newRound = new \stdClass;
		$newRound->title = '轮次-' . $latestLabel;
		$newRound->start_at = $latest;
		$newRound->state = 1;

		return $newRound;
	}
}