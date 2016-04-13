<?php
namespace matter\enroll;

class round_model extends \TMS_MODEL {
	/**
	 *
	 * @param string $siteId
	 * @param string $aid
	 */
	public function &byApp($siteId, $aid, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$state = isset($options['state']) ? $options['state'] : false;

		$q = array(
			$fields,
			'xxt_enroll_round',
			"siteid='$siteId' and aid='$aid'",
		);
		$state && $q[2] .= " and state in($state)";

		$q2 = array('o' => 'create_at desc');

		$rounds = $this->query_objs_ss($q, $q2);

		return $rounds;
	}
	/**
	 *
	 * $siteId
	 * $aid
	 */
	public function getLast($siteId, $aid) {
		$q = array(
			'*',
			'xxt_enroll_round',
			"siteid='$siteId' and aid='$aid'",
		);
		$q2 = array(
			'o' => 'create_at desc',
			'r' => array('o' => 0, 'l' => 1),
		);

		$rounds = $this->query_objs_ss($q, $q2);

		if (count($rounds) === 1) {
			return $rounds[0];
		} else {
			return false;
		}

	}
	/**
	 * 获得启用状态的轮次
	 * 一个登记活动只能有一个启用状态的轮次
	 *
	 * $siteId
	 * $aid
	 */
	public function getActive($siteId, $aid) {
		$q = array(
			'*',
			'xxt_enroll_round',
			"siteid='$siteId' and aid='$aid' and state=1",
		);

		$round = $this->query_obj_ss($q);

		return $round;
	}
}