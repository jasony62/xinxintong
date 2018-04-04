<?php
namespace sns;
/**
 * 微信小程序
 */
class wxa_model extends \TMS_MODEL {
	/**
	 * 站点绑定的公众号
	 */
	public function &bySite($siteId, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = [
			$fields,
			'xxt_site_wxa',
			['siteid' => $siteId],
		];
		$oWma = $this->query_obj_ss($q);

		return $oWma;
	}
	/**
	 * 创建绑定的公众号配置信息
	 */
	public function &create($siteId, $data = []) {
		$data['siteid'] = $this->escape($siteId);

		$this->insert('xxt_site_wxa', $data, false);

		$oWma = $this->bySite($siteId);

		return $oWma;
	}
}