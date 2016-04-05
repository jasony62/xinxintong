<?php
namespace matter\lottery;

class award_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function &byId($id, $fields = '*') {
		$q = array(
			$fields,
			'xxt_lottery_award',
			"aid='$id'",
		);
		$award = $this->query_obj_ss($q);

		return $award;
	}
}