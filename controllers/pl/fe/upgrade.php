<?php
namespace pl\fe;

class upgrade extends \TMS_CONTROLLER {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'do';

		return $rule_action;
	}
	/**
	 * 当前用户可见的所有公众号
	 */
	public function do_action($site = null, $app = null, $ek = null, $force = 'N') {
		$model = $this->model();

		$q = ['enroll_key', 'xxt_signin_record'];
		$q[2] = $force === 'N' ? "signin_log is null" : '1=1';
		if (!empty($site)) {
			$site = $model->escape($site);
			$q[2] .= " and siteid='$site'";
		}
		if (!empty($app)) {
			$site = $model->escape($app);
			$q[2] .= " and aid='$app'";
		}
		if (!empty($ek)) {
			$site = $model->escape($app);
			$q[2] .= " and enroll_key='$ek'";
		}
		$records = $model->query_objs_ss($q);

		foreach ($records as $record) {
			$qc = [
				'rid,signin_at',
				'xxt_signin_log',
				"enroll_key='{$record->enroll_key}'",
			];
			$logs = $model->query_objs_ss($qc);

			$data = new \stdClass;
			foreach ($logs as $log) {
				$data->{$log->rid} = $log->signin_at;
			}
			$data = $model->toJson($data);

			$model->update('xxt_signin_record', ['signin_log' => $data], "enroll_key='{$record->enroll_key}'");
		}

		return new \ResponseData('ok');
	}
}