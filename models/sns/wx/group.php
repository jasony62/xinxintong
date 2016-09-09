<?php
namespace sns\wx;
/**
 * 微信公众号关注用户用户组
 */
class group_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function &bySite($siteId, $options = []) {
		$q = [
			'id,name',
			'xxt_site_wxfangroup',
			["siteid" => $siteId],
		];
		$q2 = ['o' => 'id'];

		$groups = $this->query_objs_ss($q, $q2);

		return $groups;
	}
}