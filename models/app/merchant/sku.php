<?php
namespace app\merchant;
/**
 *
 */
class sku_model extends \TMS_MODEL {
	/**
	 * @param string $id
	 */
	public function &byId($id) {
		$q = array(
			'*',
			'xxt_merchant_product_sku s',
			"id=$id",
		);

		$sku = $this->query_obj_ss($q);

		return $sku;
	}
	/**
	 * @param int $product
	 */
	public function &byProduct($product) {
		/**
		 * sku
		 */
		$q = array(
			'*',
			'xxt_merchant_product_sku',
			"prod_id=$product",
		);
		$skus = $this->query_objs_ss($q);

		if (!empty($skus)) {
			$modelCate = \TMS_MODEL::M('app\merchant\catelog');
			foreach ($skus as &$sku) {
				$sku->cateSku = $modelCate->skuById($sku->cate_sku_id);
			}
		}

		return $skus;
	}
}