<?php
namespace pl\fe\template;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 平台模版库
 */
class platform extends \pl\fe\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 获得模板列表
	 *
	 * @param string $matterType
	 * @param string $scenario
	 * @param int $page
	 * @param int $size
	 *
	 */
	public function list_action($matterType, $scenario = null, $page = 1, $size = 20) {
		$modelTmpl = $this->model('matter\template');
		$matterType = $modelTmpl->escape($matterType);

		$q = [
			'*',
			"xxt_template",
			"visible_scope='P' and matter_type='$matterType'",
		];
		if (!empty($scenario)) {
			$q[2] .= " and scenario='$scenario'";
		}
		$q2 = [
			'o' => 'put_at desc',
			'r' => ['o' => ($page - 1) * $size, 'l' => $size],
		];

		$templates = $modelTmpl->query_objs_ss($q, $q2);
		$q[0] = "count(*)";
		$total = $modelTmpl->query_val_ss($q);

		return new \ResponseData(['templates' => $templates, 'total' => $total]);
	}
}