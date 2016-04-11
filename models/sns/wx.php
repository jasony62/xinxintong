<?php
namespace sns;
/**
 * 微信公众号
 */
class wx_model extends \TMS_MODEL {
	/**
	 * 站点绑定的公众号
	 */
	public function &bySite($siteid, $fields = '*') {
		$q = array(
			$fields,
			'xxt_site_wx',
			"siteid='$siteid'",
		);
		$wx = $this->query_obj_ss($q);

		return $wx;
	}
	/**
	 * 创建绑定的公众号配置信息
	 */
	public function &create($siteid) {
		$wx = array(
			'siteid' => $siteid,
		);
		$this->insert('xxt_site_wx', $wx, false);

		$wx = $this->bySite($siteid);

		return $wx;
	}
}