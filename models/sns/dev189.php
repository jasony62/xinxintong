<?php
namespace sns;
/**
 * 微信公众号
 */
class dev_model extends \TMS_MODEL {
	/**
	 * 站点绑定的公众号
	 */
	public function &byId($id, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = array(
			$fields,
			'xxt_account_third',
			['id' => $id]
		);
		$dev = $this->query_obj_ss($q);

		return $dev;
	}
}