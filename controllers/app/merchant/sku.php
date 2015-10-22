<?php
namespace app\merchant;

require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 库存
 */
class sku extends \member_base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 获得指定商品下的sku
	 *
	 * @param int $product
	 * @param int $beginAt 有效期开始时间
	 * @param int $endAt 有效期结束时间
	 * @param string $autogen 是否自动生成
	 *
	 */
	public function byProduct_action($product, $beginAt = 0, $endAt = 0, $autogen = 'N') {
		$modelSku = $this->model('app\merchant\sku');

		$state = array(
			'disabled' => 'N',
			'active' => 'Y',
		);

		$options = array(
			'state' => $state,
			'beiginAt' => $beginAt,
			'endAt' => $endAt,
		);

		$skus = $modelSku->byProduct($product, $options);

		if ($autogen === 'Y' && $beginAt != 0 && $endAt != 0) {
			$skus = $this->_autogen($product, $beginAt, $endAt, $skus);
		}

		return new \ResponseData($skus);
	}
	/**
	 * 自动生成指定商品下的sku
	 *
	 * @param int $product
	 * @param int $beginAt 有效期开始时间
	 * @param int $endAt 有效期结束时间
	 * @param string $autogen 是否自动生成
	 *
	 */
	private function &_autogen($productId, $beginAt, $endAt, $skus) {
		$modelCate = $this->model('app\merchant\catelog');

		$newSkus = array();
		$catelog = $modelCate->byProductId($productId);
		$cateSkus = $modelCate->skus($catelog->id);
		foreach ($cateSkus as $cs) {
			if ($cs->can_autogen === 'Y') {
				$newSkus[] = $this->_autogenByCateSku($cs, $beginAt, $endAt, $skus);
			}
		}
		/*合并*/
		if (!empty($newSkus)) {
			foreach ($newSkus as $ns) {
				$skus = array_merge($skus, $ns);
			}
		}

		return $skus;
	}
	/**
	 * 自动生成指定分类sku下的sku
	 *
	 * @param object $cateSku
	 * @param int $beginAt 有效期开始时间
	 * @param int $endAt 有效期结束时间
	 * @param array $skus
	 *
	 */
	private function _autogenByCateSku($cateSku, $beginAt, $endAt, $skus) {
		$rules = $cateSku->autogen_rule;
		$rules = json_decode($rules);
		$first = $this->_cronNextPoint($beginAt, $rules->crontab);
		$next = $this->_cronNextPoint($first + 60, $rules->crontab);
		$step = $next - $first;
		$last = $this->_cronLastPoint($endAt, $rules->crontab);
		echo ('f:' . $first . ',' . date('Ymd H:i', $first));
		echo 's:' . $step;
		die(',l:' . $last . ',' . date('Ymd H:i', $last));
	}
	/**
	 * 获得与指定时间点最近的sku生成时间
	 */
	private function _cronNextPoint($time, $cron, $delimiter = '_') {
		$earliest = $time - intval(date('s', $time));

		$cron_parts = explode($delimiter, $cron);
		if (count($cron_parts) != 5) {
			return false;
		}

		list($min, $hour, $day, $mon, $week) = explode($delimiter, $cron);

		$to_check = array('week' => 'w', 'hour' => 'G', 'min' => 'i', 'day' => 'j', 'mon' => 'n');

		$ranges = array(
			'min' => '0-59',
			'hour' => '0-23',
			'day' => '1-31',
			'mon' => '1-12',
			'week' => '0-6',
		);

		foreach ($to_check as $part => $f) {
			$val = $$part;
			$currentPart = intval(date($f, $earliest));
			$earliestPart = false;
			/*
			For patters like 0-23/2
			 */
			if (strpos($val, '/') !== false) {
				//Get the range and step
				list($range, $steps) = explode('/', $val);
				//Now get the start and stop
				if ($range == '*') {
					$range = $ranges[$part];
				}
				list($start, $stop) = explode('-', $range);
				for ($i = $start; $i <= $stop; $i = $i + $steps) {
					if ($i >= $currentPart) {
						$earliestPart = $i;
						break;
					}
				}
				$earliestPart === false && ($earliestPart = $start);
			}
			/*
			For patters like :
			2
			2,5,8
			2-23
			 */
			else {
				$k = explode(',', $val);
				foreach ($k as $v) {
					if (strpos($v, '-') !== false) {
						list($start, $stop) = explode('-', $v);
						for ($i = $start; $i <= $stop; $i++) {
							if ($i >= $currentPart) {
								$earliestPart = $i;
								break;
							}
						}
						$earliestPart === false && ($earliestPart = $start);
					} else {
						$earliestPart = $v;
					}
				}
			}
			switch ($f) {
			case 'j':
				break;
			case 'n':
				break;
			case 'w':
				$offset = ($earliestPart - $currentPart + ($earliestPart < $currentPart ? 7 : 0)) * 86400;
				if ($offset) {
					$earliest += $offset;
					$earliest = $earliest - (intval(date('G', $earliest)) * 3600) - (intval(date('i', $earliest)) * 60);
				}
				break;
			case 'G':
				$offset = ($earliestPart - $currentPart + ($earliestPart < $currentPart ? 24 : 0)) * 3600;
				if ($offset) {
					$earliest += $offset;
					$earliest = $earliest - (intval(date('i', $earliest)) * 60);
				}
				break;
			case 'i':
				$offset = ($earliestPart - $currentPart + ($earliestPart < $currentPart ? 60 : 0)) * 60;
				$earliest += $offset;
				break;
			}
		}

		return $earliest;
	}
	/**
	 * 获得与指定时间点最近的sku生成时间
	 */
	private function _cronLastPoint($time, $cron, $delimiter = '_') {
		$earliest = $time - intval(date('s', $time));

		$cron_parts = explode($delimiter, $cron);
		if (count($cron_parts) != 5) {
			return false;
		}

		list($min, $hour, $day, $mon, $week) = explode($delimiter, $cron);

		$to_check = array('week' => 'w', 'hour' => 'G', 'min' => 'i', 'day' => 'j', 'mon' => 'n');

		$ranges = array(
			'min' => '0-59',
			'hour' => '0-23',
			'day' => '1-31',
			'mon' => '1-12',
			'week' => '0-6',
		);

		foreach ($to_check as $part => $f) {
			$val = $$part;
			$currentPart = intval(date($f, $earliest));
			$earliestPart = false;
			/*
			For patters like 0-23/2
			 */
			if (strpos($val, '/') !== false) {
				//Get the range and step
				list($range, $steps) = explode('/', $val);
				//Now get the start and stop
				if ($range == '*') {
					$range = $ranges[$part];
				}
				list($start, $stop) = explode('-', $range);
				for ($i = $start; $i <= $stop; $i = $i + $steps) {
					if ($i <= $currentPart) {
						$earliestPart = $i;
					} else {
						break;
					}
				}
				$earliestPart === false && ($earliestPart = $stop);
			}
			/*
			For patters like :
			2
			2,5,8
			2-23
			 */
			else {
				$k = explode(',', $val);
				foreach ($k as $v) {
					if (strpos($v, '-') !== false) {
						list($start, $stop) = explode('-', $v);
						for ($i = $start; $i <= $stop; $i++) {
							if ($i <= $currentPart) {
								$earliestPart = $i;
							} else {
								break;
							}
						}
						$earliestPart === false && ($earliestPart = $stop);
					} else {
						$earliestPart = $v;
					}
				}
			}
			switch ($f) {
			case 'j':
				break;
			case 'n':
				break;
			case 'w':
				$offset = ($currentPart - $earliestPart + ($currentPart < $earliestPart ? 7 : 0)) * 86400;
				if ($offset) {
					$earliest -= $offset;
					$earliest = $earliest - (intval(date('G', $earliest)) * 3600) - (intval(date('i', $earliest)) * 60);
				}
				break;
			case 'G':
				$offset = ($currentPart - $earliestPart + ($currentPart < $earliestPart ? 24 : 0)) * 3600;
				if ($offset) {
					$earliest -= $offset;
					$earliest = $earliest - (intval(date('i', $earliest)) * 60) + 3599;
				}
				break;
			case 'i':
				$offset = ($currentPart - $earliestPart + ($currentPart < $earliestPart ? 60 : 0)) * 60;
				$earliest -= $offset;
				break;
			}
		}

		return $earliest;
	}
	/**
	 * Test if a timestamp matches a cron format or not
	 */
	private function _isTimeCron($time, $cron, $delimiter = '_') {
		$cron_parts = explode(' ', $cron);
		if (count($cron_parts) != 5) {
			return false;
		}

		list($min, $hour, $day, $mon, $week) = explode($delimiter, $cron);

		$to_check = array('min' => 'i', 'hour' => 'G', 'day' => 'j', 'mon' => 'n', 'week' => 'w');

		$ranges = array(
			'min' => '0-59',
			'hour' => '0-23',
			'day' => '1-31',
			'mon' => '1-12',
			'week' => '0-6',
		);

		foreach ($to_check as $part => $c) {
			$val = $$part;
			$values = array();
			/*
			For patters like 0-23/2
			 */
			if (strpos($val, '/') !== false) {
				//Get the range and step
				list($range, $steps) = explode('/', $val);
				//Now get the start and stop
				if ($range == '*') {
					$range = $ranges[$part];
				}
				list($start, $stop) = explode('-', $range);

				for ($i = $start; $i <= $stop; $i = $i + $steps) {
					$values[] = $i;
				}
			}
			/*
			For patters like :
			2
			2,5,8
			2-23
			 */
			else {
				$k = explode(',', $val);
				foreach ($k as $v) {
					if (strpos($v, '-') !== false) {
						list($start, $stop) = explode('-', $v);
						for ($i = $start; $i <= $stop; $i++) {
							$values[] = $i;
						}
					} else {
						$values[] = $v;
					}
				}
			}
			/*check*/
			if (!in_array(date($c, $time), $values) && (strval($val) !== '*')) {
				return false;
			}
		}

		return true;
	}
}