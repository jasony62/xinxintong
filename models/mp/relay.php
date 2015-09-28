<?php
namespace mp;
/**
 *
 */
class relay_model extends \TMS_MODEL {
	/**
	 * 获得定义的转发接口
	 */
	public function &byMpid($mpid) {
		$q = array(
			'*',
			'xxt_mprelay r',
			"r.mpid='$mpid' and state=1",
		);
		!($mprelays = $this->query_objs_ss($q)) && $mprelays = array();

		return $mprelays;
	}
	/**
	 * 获得定义的转发接口
	 */
	public function &byId($id) {
		$q = array(
			'*',
			'xxt_mprelay r',
			"r.id='$id'",
		);
		$mprelay = $this->query_obj_ss($q);

		return $mprelay;
	}
	/**
	 * 添加转发接口
	 */
	public function add($aRelay) {
		$rid = $this->insert('xxt_mprelay', $aRelay, true);
		$q = array(
			'*',
			'xxt_mprelay r',
			"r.id='$rid'",
		);
		$relay = $this->query_obj_ss($q);

		return $relay;
	}
}