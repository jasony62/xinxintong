<?php
namespace matter\merchant;
/**
 *
 */
class catelog_model extends \TMS_MODEL {
	/**
	 * @param string $id
	 */
	public function &byId($id, $options = array()) {
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'N';
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = array(
			$fields,
			'xxt_merchant_catelog c',
			"id=$id",
		);
		$cate = $this->query_obj_ss($q);
		if ($cate && $cascaded === 'Y') {
			$cascaded = $this->cascaded($id);
			$cate->properties = $cascaded->properties;
			$cate->propValues = isset($cascaded->propValues) ? $cascaded->propValues : array();
			$cate->orderProperties = isset($cascaded->orderProperties) ? $cascaded->orderProperties : array();
			$cate->feedbackProperties = isset($cascaded->feedbackProperties) ? $cascaded->feedbackProperties : array();
		}

		return $cate;
	}
	/**
	 * 指定商铺下的所有分类
	 *
	 * @param int $shopId
	 */
	public function &byShopId($shopId, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$state = isset($options['state']) ? $options['state'] : array();

		$q = array(
			$fields,
			'xxt_merchant_catelog',
			"sid=$shopId",
		);
		isset($state['disabled']) && $q[2] .= " and disabled='" . $state['disabled'] . "'";
		isset($state['active']) && $q[2] .= " and active='" . $state['active'] . "'";

		$q2 = array('o' => 'create_at desc');

		$catelogs = $this->query_objs_ss($q, $q2);

		foreach ($catelogs as &$cate) {
			$cascaded = $this->cascaded($cate->id);
			$cate->properties = $cascaded->properties;
			$cate->propValues = isset($cascaded->propValues) ? $cascaded->propValues : array();
			$cate->orderProperties = isset($cascaded->orderProperties) ? $cascaded->orderProperties : array();
			$cate->feedbackProperties = isset($cascaded->feedbackProperties) ? $cascaded->feedbackProperties : array();
		}

		return $catelogs;
	}
	/**
	 * $id catelog's id
	 */
	public function &cascaded($id) {
		$cascaded = new \stdClass;
		/**
		 * properties
		 */
		$q = array(
			'*',
			'xxt_merchant_catelog_property',
			"cate_id=$id and disabled='N'",
		);
		$properties = $this->query_objs_ss($q);

		$cascaded->properties = $properties;
		/**
		 * property-value
		 */
		if (!empty($properties)) {
			$propValues = new \stdClass;
			$q = array(
				'*',
				'xxt_merchant_catelog_property_value',
				"cate_id=$id",
			);
			$pValues = $this->query_objs_ss($q);
			if ($pValues) {
				foreach ($pValues as $pv) {
					$propValues->{$pv->prop_id}[] = $pv;
				}
			}

			$cascaded->propValues = $propValues;
		} else {
			$cascaded->propValues = array();
		}
		/**
		 * order properties
		 */
		$q = array(
			'*',
			'xxt_merchant_order_property',
			"cate_id=$id and disabled='N'",
		);
		$orderProperties = $this->query_objs_ss($q);

		$cascaded->orderProperties = $orderProperties;
		/**
		 * feedback properties
		 */
		$q = array(
			'*',
			'xxt_merchant_order_feedback_property',
			"cate_id=$id",
		);
		$properties = $this->query_objs_ss($q);

		$cascaded->feedbackProperties = $properties;

		return $cascaded;
	}
	/**
	 * $id property's id
	 */
	public function &valuesById($id, $assoPropVid = null) {
		$q = array(
			'*',
			'xxt_merchant_catelog_property_value v',
			"prop_id=$id",
		);

		if ($assoPropVid !== null) {
			$prop = \TMS_APP::M('app\merchant\property')->byId($id);

			$w = " and exists (select 1 from xxt_merchant_product p where";
			$w .= " p.cate_id=$prop->cate_id";
			$w .= " and p.prop_value like concat('%\"$id\":\"',v.id,'\"%')";
			$w .= " and p.prop_value like '%:\"$assoPropVid\"%'";
			$w .= ")";

			$q[2] .= $w;
		}

		$values = $this->query_objs_ss($q);

		return $values;
	}
	/**
	 * @param int $skuId
	 */
	public function &skuById($skuId, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : 'id,sid,cate_id,name,can_autogen,autogen_rule,has_validity,require_pay,seq';

		$q = array(
			$fields,
			'xxt_merchant_catelog_sku s',
			"id=$skuId",
		);

		$sku = $this->query_obj_ss($q);

		return $sku;
	}
	/**
	 * @param string $catelogId
	 */
	public function &skus($catelogId, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = array(
			$fields,
			'xxt_merchant_catelog_sku s',
			"cate_id=$catelogId",
		);
		$q[2] .= " and disabled<>'Y'";
		$q2 = array(
			'o' => 'seq',
		);

		$skus = $this->query_objs_ss($q, $q2);

		return $skus;
	}
	/**
	 * 定义分类下的sku
	 *
	 * @param string @siteId
	 * @param string @shopId
	 * @param string @catelogId
	 * @param object @data
	 */
	public function &defineSku($siteId, $shopId, $catelogId, $data) {
		$sku = new \stdClass;

		$current = time();
		$uid = \TMS_CLIENT::get_client_uid();
		$lastSeq = $this->getSkuLastSeq($catelogId);
		empty($lastSeq) && $lastSeq = -1;

		$sku->siteid = $siteId;
		$sku->sid = $shopId;
		$sku->cate_id = $catelogId;
		$sku->creater = $uid;
		$sku->create_at = $current;
		$sku->reviser = $uid;
		$sku->modify_at = $current;
		$sku->name = $data->name;
		$sku->has_validity = isset($data->has_validity) ? $data->has_validity : 'N';
		$sku->require_pay = isset($data->require_pay) ? $data->require_pay : 'N';
		$sku->seq = $lastSeq + 1;

		$sku->id = $this->insert('xxt_merchant_catelog_sku', (array) $sku, true);

		return $sku;
	}
	/**
	 * @param int $skuId
	 */
	public function removeSku($skuId) {
		$options = array(
			'fields' => 'used',
		);
		$sku = $this->skuById($skuId, $options);
		if ($sku->used === 'Y') {
			$rst = $this->update('xxt_merchant_catelog_sku', array('disabled' => 'Y'), "id=$skuId");
		} else {
			$rst = $this->delete('xxt_merchant_catelog_sku', "id=$skuId");
		}

		return $rst;
	}
	/**
	 *
	 * @param int $skuId
	 */
	public function useSku($skuId) {
		$rst = $this->update(
			'xxt_merchant_catelog_sku',
			array('used' => 'Y'),
			"id=$skuId"
		);

		return $rst;
	}
	/**
	 *
	 */
	private function getSkuLastSeq($catelogId) {
		$q = array(
			'max(seq)',
			'xxt_merchant_catelog_sku',
			"cate_id=$catelogId",
		);
		$seq = $this->query_val_ss($q);

		return $seq;
	}
	/**
	 *
	 * @param int @catelogId
	 */
	public function remove($catelogId) {
		/*properties*/
		$this->delete('xxt_merchant_catelog_property_value', "cate_id=$catelogId");
		$this->delete('xxt_merchant_catelog_property', "cate_id=$catelogId");
		/*skus*/
		$this->delete('xxt_merchant_catelog_sku_value', "cate_id=$catelogId");
		$this->delete('xxt_merchant_catelog_sku', "cate_id=$catelogId");
		/*order properties*/
		$this->delete('xxt_merchant_order_property', "cate_id=$catelogId");
		/*order feedback properties*/
		$this->delete('xxt_merchant_order_feedback_property', "cate_id=$catelogId");
		/**/
		$rst = $this->delete('xxt_merchant_catelog', "id=$catelogId");

		return $rst;
	}
	/**
	 *
	 * @param int @catelogId
	 */
	public function refer($catelogId) {
		$rst = $this->update(
			'xxt_merchant_catelog',
			array('used' => 'Y'),
			"id=$catelogId"
		);

		return $rst;
	}
	/**
	 *
	 * @param int @catelogId
	 */
	public function disable($catelogId) {
		$rst = $this->update(
			'xxt_merchant_catelog',
			array('disabled' => 'Y', 'active' => 'N'),
			"id=$catelogId"
		);

		return $rst;
	}
	/**
	 * 自动生成指定分类sku下的sku
	 *
	 * @param object $cateSku
	 * @param int $beginAt 有效期开始时间
	 * @param int $endAt 有效期结束时间
	 *
	 */
	public function &autogenByCateSku($cateSku, $beginAt, $endAt) {
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
		while ($start < $last) {
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