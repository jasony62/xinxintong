<?php
namespace site;
/**
 * 站点管理员
 */
class admin_model extends \TMS_MODEL {
	/**
	 *
	 */
	private function &_queryBy($where, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = array(
			$fields,
			'xxt_site_admin',
			$where,
		);

		$admins = $this->query_objs_ss($q);

		return $admins;
	}
	/**
	 *
	 */
	public function &byUid($siteId, $uid, $options = []) {
		$admin = $this->_queryBy("siteid='$siteId' and uid='$uid'", $options);

		$admin = count($admin) === 1 ? $admin[0] : false;

		return $admin;
	}
	/**
	 *
	 * $siteId
	 */
	public function &bySite($siteId, $options = []) {
		$where = "siteid='$siteId'";

		$admins = $this->_queryBy($where, $options);

		return $admins;
	}
}