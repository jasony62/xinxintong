<?php
namespace sns;
/**
 * 微信企业号
 */
class qy_model extends \TMS_MODEL {
	/**
	 * 站点绑定的公众号
	 */
	public function &bySite($siteid, $fields = '*') {
		$q = array(
			'*',
			'xxt_site_qy',
			"siteid='$siteid'",
		);
		$qy = $this->query_obj_ss($q);

		return $qy;
	}
	/**
	 * 创建绑定的公众号配置信息
	 */
	public function &create($siteid) {
		$qy = array(
			'siteid' => $siteid,
		);
		$this->insert('xxt_site_qy', $qy, false);

		$qy = $this->bySite($siteid);

		return $qy;
	}
}