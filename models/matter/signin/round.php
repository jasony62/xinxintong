<?php
namespace matter\signin;

class round_model extends \TMS_MODEL {
	/**
	 *
	 * @param string $siteId
	 * @param string $appId
	 *
	 */
	public function &byId($roundId, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = array(
			$fields,
			'xxt_signin_round',
			"rid='$roundId'",
		);
		$round = $this->query_obj_ss($q);

		return $round;
	}
	/**
	 *
	 * @param string $siteId
	 * @param string $appId
	 *
	 */
	public function &byApp($siteId, $appId, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$state = isset($options['state']) ? $options['state'] : false;

		$q = array(
			$fields,
			'xxt_signin_round',
			"siteid='$siteId' and aid='$appId'",
		);
		$state && $q[2] .= " and state in($state)";

		$q2 = array('o' => 'create_at desc');

		$rounds = $this->query_objs_ss($q, $q2);

		return $rounds;
	}
	/**
	 * 获得当前轮次
	 * 已经开始的，且开始时间最完的
	 *
	 * @param string $siteId
	 * @param string $appId
	 *
	 */
	public function getActive($siteId, $appId) {
		$current = time();
		$q = array(
			'*',
			'xxt_signin_round',
			"siteid='$siteId' and aid='$appId'",
		);
		/*开始时间*/
		$q[2] .= " and (start_at=0 || start_at<$current)";
		/*开始最晚的*/
		$q2 = array(
			'o' => 'start_at desc',
			'r' => array('o' => 0, 'l' => 1),
		);

		$rounds = $this->query_objs_ss($q, $q2);

		return count($rounds) === 1 ? $rounds[0] : false;
	}
}