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
	 * @param int $shop id
	 * @param int $catelog id
	 * @param int $product id
	 * @param int $beginAt 有效期开始时间
	 * @param int $endAt 有效期结束时间
	 * @param string $autogen 是否自动生成
	 *
	 */
	public function byProduct_action($mpid, $shop, $catelog, $product, $beginAt = 0, $endAt = 0, $autogen = 'N') {
		$user = $this->getUser($mpid);

		/*有效期，缺省为当天*/
		$beginAt === 0 && ($beginAt = mktime(0, 0, 0));
		$endAt === 0 && ($endAt = mktime(23, 59, 59));
		/*sku状态*/
		$state = array(
			'disabled' => 'N',
			'active' => 'Y',
		);

		$options = array(
			'state' => $state,
			'beginAt' => $beginAt,
			'endAt' => $endAt,
		);

		$modelSku = $this->model('app\merchant\sku');
		$cateSkus = $modelSku->byProduct($product, $options);

		if ($autogen === 'Y' && $beginAt != 0 && $endAt != 0) {
			$q = array(
				'1',
				'xxt_merchant_product_gensku_log',
				"prod_id=$product and begin_at=$beginAt and end_at=$endAt",
			);
			if ('1' !== $modelSku->query_val_ss($q)) {
				$this->_autogen($user->openid, $catelog, $product, $beginAt, $endAt, $cateSkus);
				$modelSku->insert(
					'xxt_merchant_product_gensku_log',
					array(
						'mpid' => $mpid,
						'sid' => $shop,
						'cate_id' => $catelog,
						'prod_id' => $product,
						'creater' => $user->openid,
						'create_at' => time(),
						'begin_at' => $beginAt,
						'end_at' => $endAt,
					),
					false
				);
			}
		}

		return new \ResponseData($cateSkus);
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
			$cateSkuOptions = array(
				'fields' => 'id,name,has_validity,require_pay',
			);
			foreach ($skus as &$sku) {
				if (!isset($catelogs[$sku->cate_id])) {
					/*catelog*/
					$catelog = $modelCate->byId($sku->cate_id, array('fields' => $cateFields, 'cascaded' => 'Y'));
					$catelog->pages = isset($catelog->pages) ? json_decode($catelog->pages) : new \stdClass;
					$catelog->products = array();
					$catelogs[$catelog->id] = &$catelog;
					/*product*/
					$product = $modelProd->byId($sku->prod_id, array('cascaded' => 'N', 'fields' => $prodFields, 'catelog' => $catelog));
					$product->cateSkus = array();
					/*catelog sku*/
					$cateSku = $modelCate->skuById($sku->cate_sku_id, $cateSkuOptions);
					$cateSku->skus = array($sku);
					$product->cateSkus[$cateSku->id] = $cateSku;
					$catelog->products[$product->id] = $product;
				} else {
					$catelog = &$catelogs[$sku->cate_id];
					if (!isset($catelog->products[$sku->prod_id])) {
						$product = $modelProd->byId($sku->prod_id, array('cascaded' => 'N', 'fields' => $prodFields, 'catelog' => $catelog));
						$product->cateSkus = array();
						/*catelog sku*/
						$cateSku = $modelCate->skuById($sku->cate_sku_id, $cateSkuOptions);
						$cateSku->skus = array($sku);
						$product->cateSkus[$cateSku->id] = $cateSku;
						$catelog->products[$product->id] = $product;
					} else {
						$product = $catelog->products[$sku->prod_id];
						if (!isset($product->cateSkus[$sku->cate_sku_id])) {
							/*catelog sku*/
							$cateSku = $modelCate->skuById($sku->cate_sku_id, $cateSkuOptions);
							$cateSku->skus = array($sku);
							$product->cateSkus[$cateSku->id] = $cateSku;
						} else {
							$product->cateSkus[$sku->cate_sku_id]->skus[] = $sku;
						}
					}
				}
			}
		}

		return new \ResponseData($catelogs);
	}
	/**
	 * 自动生成指定商品下的sku
	 *
	 * @param int $catelogId
	 * @param int $productId
	 * @param int $beginAt 有效期开始时间
	 * @param int $endAt 有效期结束时间
	 * @param string $autogen 是否自动生成
	 *
	 */
	private function _autogen($creater, $catelogId, $productId, $beginAt, $endAt, &$existedCateSkus) {
		$modelCate = $this->model('app\merchant\catelog');
		$modelSku = $this->model('app\merchant\sku');
		$cateSkuOptions = array(
			'fields' => 'id,name,has_validity,require_pay,can_autogen',
		);
		$cateSkus = $modelCate->skus($catelogId, $cateSkuOptions);
		foreach ($cateSkus as $cs) {
			if ($cs->can_autogen === 'Y') {
				$merged = array();
				$existedCateSku = empty($existedCateSkus[$cs->id]) ? false : $existedCateSkus[$cs->id];
				$newSkus = $this->_autogenByCateSku($cs, $beginAt, $endAt);
				foreach ($newSkus as $ns) {
					if (false === $existedCateSku || !$this->_isSkuExisted($existedCateSku, $ns)) {
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
							'has_validity' => $cs->has_validity,
							'validity_begin_at' => $ns->validity_begin_at,
							'validity_end_at' => $ns->validity_end_at,
							'product_code' => '',
							'used' => 'Y',
							'active' => 'Y',
						);
						$skuId = $this->model()->insert('xxt_merchant_product_sku', $gened, true);
						$merged[] = $modelSku->byId($skuId);
					}
				}
				if (!empty($merged)) {
					if ($existedCateSku) {
						$existedCateSku->skus = array_merge($ecs->skus, $merge);
					} else {
						$cs->skus = $merged;
						unset($cs->can_autogen);
						$existedCateSkus[$cs->id] = $cs;
					}
				}
			}
		}

		return true;
	}
	/**
	 * 检查sku是否已经存在
	 */
	private function _isSkuExisted($existedCateSku, $checkedSku) {
		foreach ($existedCateSku->skus as $existed) {
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