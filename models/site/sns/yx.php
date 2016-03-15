<?php
namespace site\sns;
/**
 * 易信公众号
 */
class yx_model extends \TMS_MODE {
	/**
	 * 站点绑定的公众号
	 */
	public function &bySite($siteid) {
		$q = array(
			'*',
			'xxt_site_yx',
			"siteid='$siteid'",
		);
		$yx = $this->query_obj_ss($q);

		return $yx;
	}
}