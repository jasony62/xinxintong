<?php
namespace pl\be\log;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 平台日志
 */
class sys extends \pl\be\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/be/log/main');
		exit;
	}
	/**
	 *
	 */
	public function list_action($page = 1, $size = 50) {
		$model = $this->model();

		$q = [
			'id,mpid,create_at,method,data',
			'xxt_log',
		];
		$q2 = [
			'o' => 'create_at desc',
			'r' => [
				'o' => ($page - 1) * $size,
				'l' => $size,
			],
		];
		if ($logs = $model->query_objs_ss($q, $q2)) {
			$q[0] = 'count(*)';
			$total = $model->query_val_ss($q);
		} else {
			$total = 0;
		}

		return new \ResponseData(['logs' => $logs, 'total' => $total]);
	}
	/**
	 *
	 */
	public function remove_action($id) {
		$rst = $this->model()->delete('xxt_log', "id=$id");
		return new \ResponseData($rst);
	}
}