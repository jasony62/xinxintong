<?php
namespace coin;
/**
 *
 */
class rule_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function byMpid($mpid, $act = null, $objId = null) {
		$q = array(
			'*',
			'xxt_coin_rule',
			"mpid='$mpid'",
		);
		if (!empty($act)) {
			$q[2] .= " and act='$act'";
			if (!empty($objId)) {
				$q[2] .= " and objid='$objId'";
				$rules = $this->query_obj_ss($q);
				if (!$rules) {
					$q[2] = preg_replace('/objid=\'.+?\'/', "objid='*'", $q[2]);
					$rules = $this->query_obj_ss($q);
				}
			} else {
				$rules = $this->query_obj_ss($q);
			}
		} else {
			$rules = $this->query_objs_ss($q);
		}

		return $rules;
	}
	/**
	 *
	 */
	public function byPrefix($mpid, $prefix) {
		$q = array(
			'*',
			'xxt_coin_rule',
			"mpid='$mpid' and act like '$prefix%'",
		);

		$rules = $this->query_objs_ss($q);

		return $rules;
	}
}