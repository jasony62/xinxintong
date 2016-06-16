<?php
namespace pl\sns;
/**
 * 微信公众号
 */
class wx_model extends \TMS_MODEL {
	/**
	 * 平台绑定的公众号
	 */
	public function &byPl($options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = array(
			$fields,
			'xxt_pl_wx',
		);
		$wx = $this->query_obj_ss($q);

		return $wx;
	}
}