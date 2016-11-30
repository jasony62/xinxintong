<?php
namespace site\coin;
/**
 * 站点内积分规则
 */
class rule_model extends \TMS_MODEL {
	/**
	 * 根据素材过滤器获得
	 */
	public function byMatterFilter($filter) {
		$q = [
			'*',
			'xxt_coin_rule',
			"matter_filter='$filter'",
		];

		$rules = $this->query_objs_ss($q);

		return $rules;
	}
}