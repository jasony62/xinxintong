<?php
namespace app\merchant;
/**
 *
 */
class sku_model extends \TMS_MODEL {
	/**
	 *
	 * @param string $id
	 */
	public function &byId($id, $cascaded = 'Y') {
		$q = array(
			'*',
			'xxt_merchant_product_sku s',
			"id=$id",
		);
		$sku = $this->query_obj_ss($q);
		if ($sku && $cascaded === 'Y') {
			$modelCate = \TMS_MODEL::M('app\merchant\catelog');
			$sku->cateSku = $modelCate->skuById($sku->cate_sku_id);
		}

		return $sku;
	}
	/**
	 *
	 * @param int $product
	 *
	 */
	public function &byProduct($product, $state = array()) {
		/**
		 * sku
		 */
		$q = array(
			'*',
			'xxt_merchant_product_sku',
			"prod_id=$product",
		);
		isset($state['disabled']) && $q[2] .= " and disabled='" . $state['disabled'] . "'";
		isset($state['active']) && $q[2] .= " and active='" . $state['active'] . "'";

		$skus = $this->query_objs_ss($q);

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
	 *
	 * @param int $skuId
	 */
	public function refer($skuId) {
		$rst = $this->update(
			'xxt_merchant_product_sku',
			array('used' => 'Y'),
			"id=$skuId"
		);

		return $rst;
	}
}