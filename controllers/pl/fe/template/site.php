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
				"xxt_template",
				["visible_scope" => $scope, 'state' => 1],
			];
		} else if (in_array($scope, ['favor', 'purchase'])) {
			$q = [
				'*',
				"xxt_template_order",
			];
			if ($scope === 'favor') {
				$q[2] = ["favor" => 'Y'];
			} else {
				$q[2] = ["purchase" => 'Y'];
			}
		}
		if(!empty($matterType)){
			$q[2]['matter_type'] = $matterType;
		}
		if (!empty($scenario)) {
			$q[2]['scenario'] = $scenario;
		}
		if ($scope === 'S' || $scope === 'favor') {
			$q[2]['siteid'] = $site;
		}
		if($scope === 'purchase'){
			$q[2]['buyer'] = $loginUser->id;
		}

		$q2 = [
			'r' => ['o' => ($page - 1) * $size, 'l' => $size],
		];
		if (in_array($scope, ['P', 'S'])) {
			$q2['o'] = 'put_at desc';
		} else if ($scope === 'favor') {
			$q2['o'] = 'favor_at desc';
		} else if ($scope === 'purchase') {
			$q2['o'] = 'purchase_at desc';
		}

		$orders = $model->query_objs_ss($q, $q2);
		$q[0] = "count(*)";
		$total = $model->query_val_ss($q);

		return new \ResponseData(['templates' => $orders, 'total' => $total]);
	}
}