<?php
namespace site\sns;
/**
 * 微信企业号
 */
class qy_model extends \TMS_MODE {
	/**
	 * 站点绑定的公众号
	 */
	public function &bySite($siteid) {
		$q = array(
			'*',
			'xxt_site_qy',
			"siteid='$siteid'",
		);
		$qy = $this->query_obj_ss($q);

		return $qy;
	}
}