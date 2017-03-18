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
		$page = isset($options['page']) ? $options['page'] : null;

		$q = [
			$fields,
			'xxt_enroll_round',
			"siteid='$siteId' and aid='$aid'",
		];
		$state && $q[2] .= " and state in($state)";

		$q2 = ['o' => 'create_at desc'];

		!empty($page) && $q2['r'] = ['o' => ($page->num - 1) * $page->size, 'l' => $page->size];
		$rounds = $this->query_objs_ss($q, $q2);

		return $rounds;
	}
	/**
	 * 获得指定登记活动的当前轮次
	 *
	 * @param $siteId
	 * @param $aid
	 *
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
	 * 获得指定登记活动中启用状态的轮次
	 *
	 * 登记活动只能有一个启用状态的轮次
	 * 如果登记活动设置了轮次定时生成规则，需要检查是否需要自动生成轮次
	 *
	 * @param object $app
	 *
	 */
	public function getActive($app, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		if (isset($app->roundCron->enabled) && $app->roundCron->enabled === 'Y') {
			$round = false;
		} else {
			$q = [
				$fields,
				'xxt_enroll_round',
				["siteid" => $app->siteid, "aid" => $app->id, "state" => 1],
			];
			$round = $this->query_obj_ss($q);
		}

		return $round;
	}
}