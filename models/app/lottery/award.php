<?php
namespace app\lottery;

require_once dirname(dirname(dirname(__FILE__))) . '/matter/lottery.php';

class award_model extends \matter\lottery_model {
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