<?php
namespace matter\group;

class round_model extends \TMS_MODEL {
	/**
	 * 获得抽奖的轮次
	 *
	 * @param string $app
	 * @param array $options
	 */
	public function &find($app, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = array(
			$fields,
			'xxt_group_round',
			"aid='$app'",
		);
		$rounds = $this->query_objs_ss($q);

		return $rounds;
	}
}