<?php
namespace matter\contribute;
/**
 * 投稿活动角色
 */
class role_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function &users($siteId, $cid, $role) {
		/**
		 * 直接指定
		 */
		$q = array(
			'c.id,c.identity,c.idsrc,c.label',
			'xxt_contribute_user c',
			"c.siteid='$siteId' and c.cid='$cid' and role='$role'",
		);
		$users = $this->query_objs_ss($q);

		return $users;
	}
}