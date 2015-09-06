<?php
namespace app\merchant;
/**
 *
 */
class catelog_model extends \TMS_MODEL {
	/**
	 * $id
	 */
	public function &byId($id) {
		$q = array(
			'*',
			'xxt_merchant_catelog c',
			"id=$id",
		);

		$cate = $this->query_obj_ss($q);

		return $cate;
	}
	/**
	 * $mpid
	 */
	public function &byShopId($shopId) {
		$q = array(
			'*',
			'xxt_merchant_catelog c',
			"sid=$shopId",
		);
		$q2 = array('o' => 'create_at desc');

		$catelogs = $this->query_objs_ss($q, $q2);

		foreach ($catelogs as &$cate) {
			$cascaded = $this->cascaded($cate->id);
			$cate->properties = $cascaded->properties;
			$cate->propValues = isset($cascaded->propValues) ? $cascaded->propValues : array();
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
}
