<?php
namespace coin;
/**
 *
 */
class rule_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function byMpid($mpid, $act = null) {
		$q = array(
			'*',
			'xxt_coin_rule',
			"mpid='$mpid'",
		);
		if (!empty($act)) {
			$q[2] .= " and act='$act'";
			$rules = $this->query_obj_ss($q);
		} else {
			$rules = $this->query_objs_ss($q);
		}

		return $rules;
	}
}