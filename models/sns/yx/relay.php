<?php
namespace sns\yx;
/**
 * 消息转发
 */
class relay_model extends \TMS_MODEL {
	/**
	 * 获得定义的转发接口
	 */
	public function &bySite($siteId) {
		$q = array(
			'*',
			'xxt_call_relay_yx',
			"siteid='$siteId' and state=1",
		);
		$relays = $this->query_objs_ss($q);

		return $relays;
	}
	/**
	 * 获得定义的转发接口
	 */
	public function &byId($id) {
		$q = array(
			'*',
			'xxt_call_relay_yx',
			"id='$id'",
		);
		$relay = $this->query_obj_ss($q);

		return $relay;
	}
	/**
	 * 添加转发接口
	 */
	public function &add($aRelay) {
		$rid = $this->insert('xxt_call_relay_yx', $aRelay, true);
		$q = array(
			'*',
			'xxt_call_relay_yx r',
			"r.id='$rid'",
		);
		$relay = $this->byId($rid);

		return $relay;
	}
}