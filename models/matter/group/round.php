<?php
namespace matter\group;

class round_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function &byId($id) {
		$q = array(
			'*',
			'xxt_group_round',
			"round_id='$id'",
		);
		$round = $this->query_obj_ss($q);

		return $round;
	}
	/**
	 * 创建轮次
	 */
	public function &create($app, $prototype = array()) {
		$targets = isset($prototype['targets']) ? $this->toJson($prototype['targets']) : '[]';
		$round = array(
			'aid' => $app,
			'round_id' => uniqid(),
			'create_at' => time(),
			'title' => isset($prototype['title']) ? $prototype['title'] : '新分组',
			'times' => isset($prototype['times']) ? $prototype['times'] : 0,
			'targets' => $targets,
		);
		$this->insert('xxt_group_round', $round, false);

		$round = (object) $round;

		return $round;
	}
	/**
	 * 获得抽奖的轮次
	 *
	 * @param string $app
	 * @param array $options
	 */
	public function &byApp($appId, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = array(
			$fields,
			'xxt_group_round',
			"aid='$appId'",
		);
		$rounds = $this->query_objs_ss($q);

		return $rounds;
	}
	/**
	 * 清除轮次结果
	 *
	 * @param string $appId
	 */
	public function clean($appId) {
		$rst = $this->update(
			'xxt_group_player',
			array(
				'round_id' => 0,
				'round_title' => '',
			),
			"aid='$appId'"
		);

		return $rst;
	}
}