<?php
namespace sns;
/**
 * 微信企业号
 */
class qy_model extends \TMS_MODEL {
	/**
	 * 站点绑定的公众号
	 */
	public function &bySite($siteid, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = array(
			$fields,
			'xxt_site_qy',
			"siteid='$siteid'",
		);
		$qy = $this->query_obj_ss($q);

		return $qy;
	}
	/**
	 * 创建绑定的公众号配置信息
	 */
	public function &create($site,$data=[]) {
		$data['siteid'] = $site;
		$this->insert('xxt_site_qy', $data, false);

		$qy = $this->bySite($site);

		return $qy;
	}
}