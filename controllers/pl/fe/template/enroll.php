<?php
namespace pl\fe\template;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 登记活动模板管理控制器
 */
class enroll extends \pl\fe\base {
	/**
	 * 
	 */
	public function index_action(){
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
				["visible_scope" => $scope],
			];
		}
		if(!empty($matterType)){
			$q[2]['matter_type'] = $matterType;
		}
		if (!empty($scenario)) {
			$q[2]['scenario'] = $scenario;
		}
		if ($scope === 'S') {
			$q[2]['siteid'] = $site;
		}

		$q2 = [
			'r' => ['o' => ($page - 1) * $size, 'l' => $size],
		];
		if (in_array($scope, ['P', 'S'])) {
			$q2['o'] = 'put_at desc';
		}

		$orders = $model->query_objs_ss($q, $q2);
		$q[0] = "count(*)";
		$total = $model->query_val_ss($q);

		return new \ResponseData(['templates' => $orders, 'total' => $total]);
	}
	/**
	 * 返回一个模板
	 */
	public function get_action($site, $tid){
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$template = $this->model('matter\template')->byId($site, $tid);
		print_r($template);
		return new \ResponseData($template);
	}
}