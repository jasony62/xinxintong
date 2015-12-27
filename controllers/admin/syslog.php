<?php
namespace admin;

class syslog extends \TMS_CONTROLLER {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';

		return $rule_action;
	}
	/**
	 *
	 */
	public function list_action($page = 1, $size = 50) {
		$model = $this->model();

		$q = array(
			'id,mpid,create_at,method,data',
			'xxt_log',
		);
		$q2 = array(
			'o' => 'create_at desc',
			'r' => array(
				'o' => ($page - 1) * $size,
				'l' => $size,
			),
		);
		if ($logs = $model->query_objs_ss($q, $q2)) {
			$q[0] = 'count(*)';
			$total = $model->query_val_ss($q);
		} else {
			$total = 0;
		}

		return new \ResponseData(array('logs' => $logs, 'total' => $total));
	}
	/**
	 *
	 */
	public function remove_action($id) {
		$rst = $this->model()->delete('xxt_log', "id=$id");
		return new \ResponseData($rst);
	}
}