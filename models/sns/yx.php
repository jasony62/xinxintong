<?php
namespace sns;
/**
 * 易信公众号
 */
class yx_model extends \TMS_MODEL {
	/**
	 * 站点绑定的公众号
	 */
	public function &bySite($siteid, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

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
	public function &create($site,$data=[]) {
		$data['siteid'] = $site;
		$this->insert('xxt_site_yx', $data, false);

		$yx = $this->bySite($site);

		return $yx;
	}
}