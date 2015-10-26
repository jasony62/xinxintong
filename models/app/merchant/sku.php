<?php
namespace app\merchant;
/**
 * 库存
 */
class sku_model extends \TMS_MODEL {
	/**
	 *
	 * @param string $id
	 */
	public function &byId($id, $options = array()) {
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cateSku = isset($options['cateSku']) ? $options['cateSku'] : false;

		$q = array(
			$fields,
			'xxt_merchant_product_sku',
			"id=$id",
		);
		$sku = $this->query_obj_ss($q);
		if ($sku) {
			if ($cascaded === 'Y') {
				if ($cateSku === false) {
					$modelCate = \TMS_MODEL::M('app\merchant\catelog');
					$cateSku = $modelCate->skuById($sku->cate_sku_id);
				}
				$sku->cateSku = $cateSku;
			}
		}

		return $sku;
	}
	/**
	 * 根据ID获得sku的列表
	 *
	 * @param string $ids
	 */
	public function byIds($ids, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : 'id,sid,cate_id,cate_sku_id,icon_url,ori_price,price,prod_id,product_code,quantity,sku_value,validity_begin_at,validity_end_at';
		$q = array(
			$fields,
			'xxt_merchant_product_sku s',
			"id in ($ids)",
		);
		$q2 = array('o' => 'validity_begin_at');

		$skus = $this->query_objs_ss($q, $q2);
		if (!empty($skus)) {
			$cateSkus = array();
			$modelCate = \TMS_MODEL::M('app\merchant\catelog');
			foreach ($skus as &$sku) {
				if (!isset($cateSkus[$sku->cate_sku_id])) {
					$cateSkus[$sku->cate_sku_id] = $modelCate->skuById($sku->cate_sku_id);
				}
				$sku->cateSku = $cateSkus[$sku->cate_sku_id];
			}
		}

		return $skus;
	}
	/**
	 * 获得指定产品下符合条件的sku
	 *
	 * @param int $productId
	 * @param array options
	 *
	 */
	public function &byProduct($productId, $options = array()) {
		/**
		 * sku
		 */
		$q = array(
			'*',
			'xxt_merchant_product_sku',
			"prod_id=$productId",
		);
		/*根据sku的状态*/
		if (isset($options['state'])) {
			$state = $options['state'];
			isset($state['disabled']) && $q[2] .= " and disabled='" . $state['disabled'] . "'";
			isset($state['active']) && $q[2] .= " and active='" . $state['active'] . "'";
		}
		/*根据sku的有效期*/
		if (isset($options['beginAt']) && $options['beginAt']) {
			$q[2] .= " and validity_begin_at>=" . $options['beginAt'];
		}
		if (isset($options['endAt']) && $options['endAt']) {
			$q[2] .= " and validity_begin_at<=" . $options['endAt'];
		}
		$q2 = array('o' => 'validity_begin_at');

		$skus = $this->query_objs_ss($q, $q2);
		if (!empty($skus)) {
			$modelCate = \TMS_MODEL::M('app\merchant\catelog');
			foreach ($skus as &$sku) {
				$sku->cateSku = $modelCate->skuById($sku->cate_sku_id);
			}
		}

		return $skus;
	}
	/**
	 * 删除库存
	 *
	 * @param int $skuId
	 */
	public function remove($skuId) {
		$sku = $this->byId($skuId, 'N');
		if ($sku->used === 'Y') {
			$rst = $this->update('xxt_merchant_product_sku', array('disabled' => 'Y'), "id=$skuId");
		} else {
			$rst = $this->delete('xxt_merchant_product_sku', "id=$skuId");
		}

		return $rst;
	}
	/**
	 * 订购sku
	 *
	 * @param int $skuId
	 * @param int $count
	 */
	public function order($skuId, $count) {
		$sql = 'update xxt_merchant_product_sku';
		$sql .= " set used='Y',quantity=quantity-$count";
		$sql .= " where id=$skuId";

		$rst = $this->update($sql);

		return $rst;
	}
}