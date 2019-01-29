<?php
namespace matter;
/**
 * 活动轮次的基础功能
 */
trait Round {
	/**
	 * 根据定时规则生成轮次 公共方法，外部可访问
	 *
	 * 计算每个规则对应的最近一个轮次的生成时间，用最近的时间作为整个活动的最近生成时间
	 *
	 * @param array rules 定时生成轮次规则
	 *
	 */
	public function sampleByCron($rules) {
		return $this->_lastRoundByCron($rules);
	}
	/**
	 * 根据定时规则生成轮次
	 *
	 * 计算每个规则对应的最近一个轮次的生成时间，用最近的时间作为整个活动的最近生成时间
	 *
	 * @param array rules 定时生成轮次规则
	 *
	 */
	private function _lastRoundByCron($rules, $timestamp = null) {
		$WeekdayZh = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
		$latest = $latestEnd = 0;
		$latestLabel = '';
		$oLatestRule = null;
		foreach ($rules as $oRule) {
			if ($oRule->pattern === 'period') {
				/* 按周期生成规则 */
				if (empty($oRule->period)) {
					continue;
				}
				list($startAt, $endAt) = $this->_timeByPeriodRule($oRule, $timestamp);
			} else if ($oRule->pattern === 'interval') {
				/* 按间隔生成轮次 */
				if (empty($oRule->start_at) || empty($oRule->next->unit) || empty($oRule->next->interval)) {
					continue;
				}
				list($startAt, $endAt) = $this->_timeByIntervalRule($oRule, $timestamp);
			}
			// 记录活动的轮次生成时间
			if ($startAt > $latest) {
				$oLatestRule = $oRule;
				$latest = $startAt;
				$latestEnd = $endAt;
				$rndLabel = date('y年n月d日', $latest) . ' ' . $WeekdayZh[date('w', $latest)] . ' ' . date('H:i', $latest);
				if ($oRule->pattern === 'period') {
					switch ($oRule->period) {
					case 'M':
						$latestLabel = $rndLabel;
						break;
					case 'W':
						$latestLabel = $rndLabel . '（全年第' . ((int) date('W', $startAt)) . '周）';
						break;
					case 'D':
						$latestLabel = $rndLabel . '（全年第' . ((int) date('z', $startAt) + 1) . '日）';
						break;
					}
				} else {
					$latestLabel = $rndLabel;
				}
			}
		}
		if (!empty($oLatestRule->purpose)) {
			switch ($oLatestRule->purpose) {
			case 'B':
				$latestLabel .= '（目标）';
				break;
			case 'S':
				$latestLabel .= '（汇总）';
				break;
			}
		}

		$oNewRound = new \stdClass;
		$oNewRound->title = $latestLabel;
		$oNewRound->start_at = $latest;
		$oNewRound->end_at = $latestEnd;
		$oNewRound->state = 1;
		$oNewRound->purpose = empty($oLatestRule->purpose) ? 'C' : (in_array($oLatestRule->purpose, ['C', 'B', 'S']) ? $oLatestRule->purpose : 'C');

		return $oNewRound;
	}
	/**
	 * 根据指定规则计算轮次的开始结束时间
	 */
	private function _timeByPeriodRule($oRule, $timestamp = null) {
		/* 根据定时计划计算轮次的开始时间 */
		$hour = (int) (empty($timestamp) ? date("G") : date("G", $timestamp)); // 0-23
		$month = (int) (empty($timestamp) ? date("n") : date("n", $timestamp)); // 1-12
		$mday = (int) (empty($timestamp) ? date("j") : date("j", $timestamp)); // 1-31
		$year = (int) (empty($timestamp) ? date("Y") : date("Y", $timestamp)); // yyyy
		$wday = (int) (empty($timestamp) ? date("w") : date("w", $timestamp)); //0 (for Sunday) through 6 (for Saturday)
		$week = (int) (empty($timestamp) ? date("W") : date("W", $timestamp)); //0-51, ISO-8601 week number of year, weeks starting on Monday

		if ($oRule->period === 'M' && !empty($oRule->mday)) {
			/* 某个月的某一天 */
			if ($mday === (int) $oRule->mday) {
				/* 计算时间 */
				if (empty($oRule->hour)) {
					$hour = 0;
				} else {
					if ($hour < (int) $oRule->hour) {
						/* 在上个月的指定时间 */
						if ($month > 1) {
							$month--;
						} else {
							$year--;
							$month = 12;
						}
					}
					$hour = (int) $oRule->hour;
				}
			} else if ($mday < (int) $oRule->mday) {
				// 在上个月的指定日期
				if ($month > 1) {
					$month--;
				} else {
					$month = 12;
					$year--;
				}
			}
			//算出开始的日期
			$mday = (int) $oRule->mday;
			$oRule->end_mday = empty($oRule->end_mday) ? '1' : $oRule->end_mday;
			$hour = empty($oRule->hour) ? 0 : (int) $oRule->hour;
			if (isset($oRule->end_hour) && strlen($oRule->end_hour)) {
				$end_hour = (int) $oRule->end_hour;
				//算出结束的日期
				if ($oRule->mday == $oRule->end_mday) {
					if ($hour < $end_hour) {
						$end_month = $month;
						$end_year = $year;
					} else {
						if ($month < 12) {
							$end_month = $month + 1;
							$end_year = $year;
						} else {
							$end_month = 1;
							$end_year = $year + 1;
						}
					}
				} else if ($oRule->mday > $oRule->end_mday) {
					if ($month < 12) {
						$end_month = $month + 1;
						$end_year = $year;
					} else {
						$end_month = 1;
						$end_year = $year + 1;
					}
				} else {
					$end_month = $month;
					$end_year = $year;
				}
				$end_mday = (int) $oRule->end_mday;
			} else {
				$end_year = $end_month = $end_mday = $end_hour = null;
			}
		} else if ($oRule->period === 'W' && isset($oRule->wday) && strlen($oRule->wday)) {
			/* 某周的某一天 */
			if ($wday === (int) $oRule->wday) {
				/* 计算时间 */
				if (empty($oRule->hour)) {
					$hour = 0;
				} else {
					if ($hour < (int) $oRule->hour) {
						/* 在上一周的指定时间 */
						$mday -= 7;
						$week--;
					}
					$hour = (int) $oRule->hour;
				}
			} else if ($wday > (int) $oRule->wday) {
				// 在同一周
				$mday -= $wday - (int) $oRule->wday;
				$hour = empty($oRule->hour) ? 0 : (int) $oRule->hour;
			} else {
				// 在上一周
				$mday = $mday - 7 + ((int) $oRule->wday - $wday);
				$hour = empty($oRule->hour) ? 0 : (int) $oRule->hour;
				$week--;
			}
			/* 轮次结束时间 */
			if (isset($oRule->end_wday) && strlen($oRule->end_wday)) {
				$end_hour = empty($oRule->end_hour) ? 0 : (int) $oRule->end_hour;
				if ($oRule->wday == $oRule->end_wday) {
					if ($hour < $end_hour) {
						$end_mday = $mday + ((int) $oRule->end_wday - (int) $oRule->wday);
					} else {
						$end_mday = $mday + 7 - ((int) $oRule->wday - (int) $oRule->end_wday);
					}
				} else if ($oRule->wday > $oRule->end_wday) {
					$end_mday = $mday + 7 - ((int) $oRule->wday - (int) $oRule->end_wday);
				} else {
					$end_mday = $mday + ((int) $oRule->end_wday - (int) $oRule->wday);
				}
				$end_year = $year;
				$end_month = $month;
			} else {
				$end_year = $end_month = $end_mday = $end_hour = null;
			}
		} else if ($oRule->period === 'D' && isset($oRule->hour) && strlen($oRule->hour)) {
			/* 每天指定时间 */
			if ($hour < (int) $oRule->hour) {
				/* 前一天的指定时间 */
				$mday--;
				if ($wday === (int) 0) {
					$wday = 6;
				} else {
					$wday--;
				}
			}
			$hour = (int) $oRule->hour;
			if (isset($oRule->notweekend) && $oRule->notweekend === true) {
				/* 不包含周六、日 */
				if ($wday === (int) 0) {
					$mday -= 2;
					$wday = 5;
				} else if ($wday === (int) 6) {
					$mday--;
					$wday = 5;
				}
			}
			/* 轮次的结束时间 */
			if (isset($oRule->end_hour) && strlen($oRule->end_hour)) {
				$end_hour = empty($oRule->end_hour) ? 0 : (int) $oRule->end_hour;
				if ($hour >= $end_hour) {
					$end_mday = $mday + 1;
				} else {
					$end_mday = $mday;
				}
				$end_month = $month;
				$end_year = $year;
			} else {
				$end_year = $end_month = $end_mday = $end_hour = null;
			}
		} else {
			isset($oRule->hour) && $hour = (int) $oRule->hour;
			$end_hour = empty($oRule->end_hour) ? 0 : (int) $oRule->end_hour;
			$end_month = $month;
			$end_year = $year;
			$end_mday = $mday + 1;
		}

		$startAt = mktime($hour, 0, 0, $month, $mday, $year);
		$endAt = $end_hour === null ? 0 : mktime($end_hour, 0, 0, $end_month, $end_mday, $end_year);

		return [$startAt, $endAt];
	}
	/**
	 * 根据指定规则计算轮次的开始结束时间
	 */
	private function _timeByIntervalRule($oRule) {
		$current = time();
		/* 计算匹配的轮次开始时间 */
		$roundInterval = 0; // 两个轮次之间间隔多少秒
		switch ($oRule->next->unit) {
		case 'week':
			$roundInterval = (int) $oRule->next->interval * 7 * 86400;
			break;
		case 'day':
			$roundInterval = (int) $oRule->next->interval * 86400;
			break;
		case 'hour':
			$roundInterval = (int) $oRule->next->interval * 3600;
			break;
		}

		if ($current < $oRule->start_at) {
			$current = $oRule->start_at;
		}

		$howManyRounds = intval(($current - (int) $oRule->start_at) / $roundInterval);

		$startAt = (int) $oRule->start_at + ($howManyRounds * $roundInterval);

		/* 计算匹配轮次的结束时间 */
		if (!empty($oRule->end->unit) && !empty($oRule->end->interval)) {
			$endInterval = 0;
			switch ($oRule->end->unit) {
			case 'week':
				$endInterval = (int) $oRule->end->interval * 7 * 86400;
				break;
			case 'day':
				$endInterval = (int) $oRule->end->interval * 86400;
				break;
			case 'hour':
				$endInterval = (int) $oRule->end->interval * 3600;
				break;
			}
			$endAt = $startAt + $endInterval;
		} else {
			$endAt = $startAt + $roundInterval - 1;
		}

		return [$startAt, $endAt];
	}
	/**
	 * 检查传入的定时规则
	 *
	 * @param object $rules
	 */
	public function checkCron(&$rules) {
		foreach ($rules as $oRule) {
			if ($oRule->pattern === 'period') {
				switch ($oRule->period) {
				//1-28 日期
				case 'M':
					if (empty($oRule->mday)) {return [false, '请设置定时轮次每月的开始日期！'];}
					//if (empty($oRule->end_mday)) {return [false, '请设置定时轮次每月的结束日期！'];}
					if (empty($oRule->hour)) {return [false, '请设置定时轮次每月开始日期的几点开始！'];}
					break;
				// 0-6 周几
				case 'W':
					if (!isset($oRule->wday)) {return [false, '请设置定时轮次每周几开始！'];}
					//if (!isset($oRule->end_wday)) {return [false, '请设置定时轮次每周几结束！'];}
					if (empty($oRule->hour)) {return [false, '请设置定时轮次每周几的几点开始！'];}
					break;
				// 0-23 几点
				default:
					if (empty($oRule->hour)) {return [false, '请设置定时轮次每天的几点开始！'];}
					//if (!isset($oRule->end_hour)) {return [false, '请设置定时轮次几点结束！'];}
					break;
				}
			} else if ($oRule->pattern === 'interval') {

			}
			if (!isset($oRule->id)) {
				$oRule->id = uniqid();
			}
			$oRule->name = $this->readableCronName($oRule);
		}

		return [true];
	}
	/**
	 * 生成规则的名称
	 */
	public function readableCronName($oRule) {
		$WeekdayZh = ['日', '一', '二', '三', '四', '五', '六'];
		$name = '';
		if ($oRule->pattern === 'period') {
			switch ($oRule->period) {
			//1-28 日期
			case 'M':
				if (isset($oRule->mday)) {
					$name = '每月，' . $oRule->mday . '号';
					if (isset($oRule->hour)) {
						$name .= $oRule->hour . '点开始';
						if (isset($oRule->end_mday)) {
							if ($oRule->end_mday !== $oRule->mday) {
								$name .= '，每月' . $oRule->end_mday . '号';
								if (isset($oRule->end_hour) && $oRule->end_hour > 0) {
									$name .= $oRule->end_hour . '点结束';
								}
							} else {
								if (isset($oRule->end_hour) && $oRule->end_hour > 0) {
									$name .= $oRule->end_hour . '点结束';
								}
							}
						}
					}
				}
				break;
			// 0-6 周几
			case 'W':
				if (isset($oRule->wday) && strlen($oRule->wday)) {
					$name = '每周' . $WeekdayZh[$oRule->wday];
					if (isset($oRule->hour)) {
						$name .= $oRule->hour . '点开始';
						if (isset($oRule->end_wday) && strlen($oRule->end_wday)) {
							if ($oRule->end_wday !== $oRule->wday) {
								$name .= '，每周' . $WeekdayZh[$oRule->end_wday];
								if (isset($oRule->end_hour) && $oRule->end_hour > 0) {
									$name .= $oRule->end_hour . '点结束';
								}
							} else {
								if (isset($oRule->end_hour) && $oRule->end_hour > 0) {
									$name .= $oRule->end_hour . '点结束';
								}
							}
						}
					}
				}
				break;
			// 0-23 几点
			case 'D':
				if (isset($oRule->hour)) {
					$name = '每天，' . $oRule->hour . '点开始';
					if (isset($oRule->end_hour) && $oRule->end_hour > 0) {
						$name .= '，' . $oRule->end_hour . '点结束';
					}
				}
				break;
			}
		} else if ($oRule->pattern === 'interval') {

		}

		return $name;
	}
}