<?php
namespace app\merchant;

require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 商品库存
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
	public function byProduct_action($mpid, $product, $beginAt = 0, $endAt = 0, $autogen = 'N') {
		$user = $this->getUser($mpid);
		$modelSku = $this->model('app\merchant\sku');

		$state = array(
			'disabled' => 'N',
			'active' => 'Y',
		);

		$options = array(
			'state' => $state,
			'beginAt' => $beginAt,
			'endAt' => $endAt,
		);

		$skus = $modelSku->byProduct($product, $options);

		if ($autogen === 'Y' && $beginAt != 0 && $endAt != 0) {
			$skus = $this->_autogen($user->openid, $product, $beginAt, $endAt, $skus);
		}

		return new \ResponseData($skus);
	}
	/**
	 *
	 * @param string $mpid
	 * @param string $ids splited by comma
	 *
	 * @return
	 */
	public function list_action($mpid, $ids) {
		$modelSku = $this->model('app\merchant\sku');
		$skus = $modelSku->byIds($ids);

		/*按分类和商品进行分组*/
		$catelogs = array();
		if (!empty($skus)) {
			$modelCate = $this->model('app\merchant\catelog');
			$modelProd = $this->model('app\merchant\product');
			$cateFields = 'id,sid,name,pattern,pages';
			$prodFields = 'id,sid,cate_id,name,main_img,img,detail_text,detail_text,prop_value,buy_limit,sku_info';
			foreach ($skus as &$sku) {
				if (!isset($catelogs[$sku->cate_id])) {
					/*catelog*/
					$catelog = $modelCate->byId($sku->cate_id, array('fields' => $cateFields, 'cascaded' => 'Y'));
					$catelog->pages = isset($catelog->pages) ? json_decode($catelog->pages) : new \stdClass;
					$catelog->products = array();
					$catelogs[$catelog->id] = &$catelog;
					/*product*/
					$product = $modelProd->byId($sku->prod_id, array('cascaded' => 'N', 'fields' => $prodFields, 'catelog' => $catelog));
					$product->skus = array();
					$catelog->products[$product->id] = $product;
				} else {
					$catelog = &$catelogs[$sku->cate_id];
					if (!isset($catelog->products[$sku->prod_id])) {
						$product = $modelProd->byId($sku->prod_id, array('cascaded' => 'N', 'fields' => $prodFields, 'catelog' => $catelog));
						$product->skus = array();
						$catelog->products[$product->id] = $product;
					} else {
						$product = $catelog->products[$sku->prod_id];
					}
				}
				$product->skus[] = $sku;
			}
		}

		return new \ResponseData($catelogs);
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
	private function &_autogen($creater, $productId, $beginAt, $endAt, $existedSkus) {
		$modelCate = $this->model('app\merchant\catelog');

		$merged = $existedSkus;
		$catelog = $modelCate->byProductId($productId);
		$cateSkus = $modelCate->skus($catelog->id);
		foreach ($cateSkus as $cs) {
			if ($cs->can_autogen === 'Y') {
				$newSkus = $this->_autogenByCateSku($cs, $beginAt, $endAt);
				foreach ($newSkus as $ns) {
					if (!$this->isSkuExisted($existedSkus, $ns)) {
						$gened = array(
							'mpid' => $cs->mpid,
							'sid' => $cs->sid,
							'cate_id' => $cs->cate_id,
							'cate_sku_id' => $cs->id,
							'prod_id' => $productId,
							'create_at' => time(),
							'creater' => $creater,
							'creater_src' => 'F',
							'sku_value' => '{}',
							'ori_price' => $ns->price,
							'price' => $ns->price,
							'quantity' => $ns->quantity,
							'validity_begin_at' => $ns->validity_begin_at,
							'validity_end_at' => $ns->validity_end_at,
							'product_code' => '',
							'used' => 'Y',
							'active' => 'Y',
						);
						$skuId = $this->model()->insert('xxt_merchant_product_sku', $gened, true);
						$merged[] = $this->model('app\merchant\sku')->byId($skuId);
					}
				}
			}
		}

		return $merged;
	}
	/**
	 * 检查sku是否已经存在
	 */
	private function isSkuExisted($existedSkus, $checkedSku) {
		foreach ($existedSkus as $existed) {
			if ($existed->validity_begin_at == $checkedSku->validity_begin_at && $existed->validity_end_at == $checkedSku->validity_end_at) {
				return true;
			}
		}
		return false;
	}
	/**
	 * 自动生成指定分类sku下的sku
	 *
	 * @param object $cateSku
	 * @param int $beginAt 有效期开始时间
	 * @param int $endAt 有效期结束时间
	 *
	 */
	private function &_autogenByCateSku($cateSku, $beginAt, $endAt) {
		/*计算生成规则*/
		$rules = $cateSku->autogen_rule;
		$rules = json_decode($rules);
		$first = $this->_cronNextPoint($beginAt, $rules->crontab);
		$next = $this->_cronNextPoint($first + 60, $rules->crontab);
		$step = $next - $first;
		$last = $this->_cronLastPoint($endAt, $rules->crontab);
		//die('f:' . $first . ':' . date('ymd H:i', $first) . ',s:' . $step . ',l:' . $last . ':' . date('ymd H:i', $last));
		/*生成sku*/
		$skus = array();
		$start = $first;
		while ($start <= $last) {
			$sku = new \stdClass;
			$sku->quantity = isset($rules->count) ? $rules->count : 1;
			$sku->validity_begin_at = $start;
			$sku->validity_end_at = $start + (isset($rules->duration) ? $rules->duration * 60 : 1);
			$sku->price = isset($rules->price) ? $rules->price : 1;
			$skus[] = $sku;
			$start += $step;
		}

		return $skus;
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
					$earliest = $earliest - (intval(date('G', $earliest)) * 3600) - (intval(date('i', $earliest)) * 60) - intval(date('s', $earliest)) + 86399;
				}
				break;
			case 'G':
				$offset = ($currentPart - $earliestPart + ($currentPart < $earliestPart ? 24 : 0)) * 3600;
				if ($offset) {
					$earliest -= $offset;
					$earliest = $earliest - (intval(date('i', $earliest)) * 60) - intval(date('s', $earliest)) + 3599;
				}
				break;
			case 'i':
				$offset = ($currentPart - $earliestPart + ($currentPart < $earliestPart ? 60 : 0)) * 60;
				if ($offset) {
					$earliest = $earliest - $offset - intval(date('s', $earliest)) + 59;
				}

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