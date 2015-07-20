<?php
namespace app\merchant;
/**
 *
 */
class property_model extends \TMS_MODEL {
	/**
	 * $id
	 */
	public function &byId($id)
	{
		$q = array(
			'*', 
			'xxt_merchant_catelog_property p',
			"id=$id"
		);
		
		$prop = $this->query_obj_ss($q);
		
		return $prop;
	}
}