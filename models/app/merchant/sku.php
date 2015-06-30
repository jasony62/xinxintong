<?php
namespace app\merchant;
/**
 *
 */
class sku_model extends \TMS_MODEL {
	/**
	 * $id
	 */
	public function &byId($id)
	{
		$q = array(
			'*', 
			'xxt_merchant_product_sku s',
			"id=$id"
		);
		
		$sku = $this->query_obj_ss($q);
		
		return $sku;
	}
}
