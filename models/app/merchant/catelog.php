<?php
namespace app\merchant;
/**
 *
 */
class catelog_model extends \TMS_MODEL {
	/**
	 * @param string $id
	 */
	public function &byId($id, $cascaded = "N") {
		$q = array(
			'*',
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
	public function &byShopId($shopId, $state = array()) {
		$q = array(
			'*',
			'xxt_merchant_catelog c',
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
	 *
	 * @param int $productId
	 */
	public function &byProductId($productId) {
		$q = array(
			'*',
			'xxt_merchant_catelog c',
			"exists(select 1 from xxt_merchant_product p where p.id=$productId and p.cate_id=c.id)",
		);

		if ($catelog = $this->query_obj_ss($q)) {
			$cascaded = $this->cascaded($catelog->id);
			$catelog->properties = $cascaded->properties;
			$catelog->propValues = isset($cascaded->propValues) ? $cascaded->propValues : array();
			$catelog->orderProperties = isset($cascaded->orderProperties) ? $cascaded->orderProperties : array();
			$catelog->feedbackProperties = isset($cascaded->feedbackProperties) ? $cascaded->feedbackProperties : array();
		}

		return $catelog;
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
			"cate_id=$id",
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
		}
		/**
		 * order properties
		 */
		$q = array(
			'*',
			'xxt_merchant_order_property',
			"cate_id=$id",
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
	public function &skuById($skuId) {
		$q = array(
			'*',
			'xxt_merchant_catelog_sku s',
			"id=$skuId",
		);

		$sku = $this->query_obj_ss($q);

		return $sku;
	}
	/**
	 * @param string $catelogId
	 */
	public function &skus($catelogId) {
		$q = array(
			'*',
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
	 * @param string @mpid
	 * @param string @shopId
	 * @param string @catelogId
	 * @param object @data
	 */
	public function &defineSku($mpid, $shopId, $catelogId, $data) {
		$sku = new \stdClass;

		$current = time();
		$uid = \TMS_CLIENT::get_client_uid();
		$lastSeq = $this->getSkuLastSeq($catelogId);
		empty($lastSeq) && $lastSeq = -1;

		$sku->mpid = $mpid;
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
		$sku = $this->skuById($skuId);
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
}