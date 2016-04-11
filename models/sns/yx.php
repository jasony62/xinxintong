<?php
namespace sns;
/**
 * 易信公众号
 */
class yx_model extends \TMS_MODEL {
	/**
	 * 站点绑定的公众号
	 */
	public function &bySite($siteid, $fields = '*') {
		$q = array(
			$fields,
			'xxt_site_yx',
			"siteid='$siteid'",
		);
		$yx = $this->query_obj_ss($q);

		return $yx;
	}
	/**
	 * 创建绑定的公众号配置信息
	 */
	public function &create($siteid) {
		$yx = array(
			'siteid' => $siteid,
		);
		$this->insert('xxt_site_yx', $yx, false);

		$yx = $this->bySite($siteid);

		return $yx;
	}
}