<?php
namespace mp;

require_once dirname(__FILE__) . '/mp_controller.php';

class syslog extends mp_controller {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';

		return $rule_action;
	}
	/**
	 *
	 */
	public function list_action($page = 1, $size = 30) {
		$model = $this->model();

		$q = array(
			'create_at,method,data',
			'xxt_log',
			"mpid='$this->mpid'",
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
}