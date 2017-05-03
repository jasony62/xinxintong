<?php
namespace matter\merchant;
/**
 *
 */
class shop_model extends \TMS_MODEL {
	/*
		 *
	*/
	public function &byId($id, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = array(
			$fields,
			'xxt_merchant_shop s',
			"id='$id'",
		);

		$shop = $this->query_obj_ss($q);

		return $shop;
	}
	/**
	 * $siteId
	 */
	public function &bySite($siteId) {
		$q = array(
			'*',
			'xxt_merchant_shop s',
			"siteid='$siteId'",
		);
		$q2 = array('o' => 'create_at desc');

		$shops = $this->query_objs_ss($q, $q2);

		return $shops;
	}
	/**
	 *
	 */
	public function &staffAcls($siteId, $shopId, $role) {
		/**
		 * 直接指定
		 */
		$q = array(
			's.id,s.identity,s.idsrc,s.label',
			'xxt_merchant_staff s',
			"s.siteid='$siteId' and s.sid=$shopId and role='$role'",
		);
		$acls = $this->query_objs_ss($q);

		return $acls;
	}
}