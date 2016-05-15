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
	 *
	 * @param string $siteId
	 * @param string $appId
	 *
	 */
	public function getLast($siteId, $appId) {
		$q = array(
			'*',
			'xxt_signin_round',
			"siteid='$siteId' and aid='$appId'",
		);
		$q2 = array(
			'o' => 'create_at desc',
			'r' => array('o' => 0, 'l' => 1),
		);
		$rounds = $this->query_objs_ss($q, $q2);

		return count($rounds) === 1 ? $rounds[0] : false;
	}
	/**
	 * 获得启用状态的轮次
	 * 一个签到活动只有一个启用状态的轮次
	 *
	 * @param string $siteId
	 * @param string $appId
	 *
	 */
	public function getActive($siteId, $appId) {
		$q = array(
			'*',
			'xxt_signin_round',
			"siteid='$siteId' and aid='$appId' and state=1",
		);

		$round = $this->query_obj_ss($q);

		return $round;
	}
}