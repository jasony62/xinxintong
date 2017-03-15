<?php
namespace pl\fe\template;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 站点模板库管理控制器
 */
class site extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/template');
		exit;
	}
	/**
	 * 获得模板列表
	 *
	 * @param string $matterType
	 * @param int $page
	 * @param int $size
	 *
	 */
	public function list_action($site, $matterType = null, $scenario = null, $scope = 'S', $page = 1, $size = 20) {
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();

		if (in_array($scope, ['P', 'S'])) {
			$q = [
				'*',
				"xxt_template t",
				"t.visible_scope = '$scope' and t.state = 1 and t.pub_version <> ''"
			];
		} else if (in_array($scope, ['favor', 'purchase'])) {
			$q = [
				'*',
				"xxt_template t",
			];
			if ($scope === 'favor') {
				$site = $model->escape($site);
				$q[2] = "exists(select 1 from xxt_template_order o where o.favor='Y' and o.siteid = '$site' and t.id=o.template_id) and t.state = 1";
			} else {
				$q[2] = "exists(select 1 from xxt_template_order o where o.purchase='Y' and o.buyer = '".$loginUser->id."' and t.id=o.template_id) and t.state = 1";
			}
		}
		if(!empty($matterType)){
			$matterType = $model->escape($matterType);
			$q[2] .= " and t.matter_type = '$matterType'";
		}
		if (!empty($scenario)) {
			$scenario = $model->escape($scenario);
			$q[2] .= " and t.scenario = '$scenario'";
		}
		if ($scope === 'S') {
			$site = $model->escape($site);
			$q[2] .= " and t.siteid = '$site'";
		}

		$q2 = [
			'r' => ['o' => ($page - 1) * $size, 'l' => $size],
		];

		$q2['o'] = 't.put_at desc';

		$orders = $model->query_objs_ss($q, $q2);
		$q[0] = "count(*)";
		$total = $model->query_val_ss($q);

		return new \ResponseData(['templates' => $orders, 'total' => $total]);
	}
}