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
	 *
	 */
	public function do_action($site = null, $app = null, $ek = null, $force = 'N') {
		$model = $this->model();

		$q = ['enroll_key', 'xxt_enroll_record'];
		$q[2] = $force === 'N' ? "data is null" : '1=1';
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
				'name,value',
				'xxt_enroll_record_data',
				"enroll_key='{$record->enroll_key}'",
			];
			$cds = $model->query_objs_ss($qc);

			$data = new \stdClass;
			foreach ($cds as $cd) {
				if ($cd->name === 'member') {
					$data->{$cd->name} = json_decode($cd->value);
				} else {
					$data->{$cd->name} = $model->escape($cd->value);
				}
			}
			$data = $model->toJson($data);

			$model->update('xxt_enroll_record', ['data' => $data], "enroll_key='{$record->enroll_key}'");
		}

		return new \ResponseData('ok');
	}
	/**
	 * 分组的data字段
	 */
	public function do20161005_action($site = null, $app = null, $ek = null, $force = 'N') {
		$model = $this->model();

		$q = ['enroll_key', 'xxt_group_user'];
		$q[2] = $force === 'N' ? "data is null" : '1=1';
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
				'name,value',
				'xxt_group_user_data',
				"enroll_key='{$record->enroll_key}'",
			];
			$cds = $model->query_objs_ss($qc);

			$data = new \stdClass;
			foreach ($cds as $cd) {
				if ($cd->name === 'member') {
					$data->{$cd->name} = json_decode($cd->value);
				} else {
					$data->{$cd->name} = $model->escape($cd->value);
				}
			}
			$data = $model->toJson($data);

			$model->update('xxt_group_user', ['data' => $data], "enroll_key='{$record->enroll_key}'");
		}

		return new \ResponseData('ok');
	}
}