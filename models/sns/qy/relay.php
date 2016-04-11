<?php
namespace sns\qy;
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
			'xxt_call_relay_qy',
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
			'xxt_call_relay_qy',
			"id='$id'",
		);
		$relay = $this->query_obj_ss($q);

		return $relay;
	}
	/**
	 * 添加转发接口
	 */
	public function &add($aRelay) {
		$rid = $this->insert('xxt_call_relay_qy', $aRelay, true);
		$q = array(
			'*',
			'xxt_call_relay_qy r',
			"r.id='$rid'",
		);
		$relay = $this->byId($rid);

		return $relay;
	}
}