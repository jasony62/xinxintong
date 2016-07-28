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
	public function do_action() {
		$model = $this->model();

		$q = ['enroll_key', 'xxt_enroll_record', "data is null"];
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
				$data->{$cd->name} = $cd->value;
			}
			$data = $model->toJson($data);

			$model->update('xxt_enroll_record', ['data' => $data], "enroll_key='{$record->enroll_key}'");
		}

		return new \ResponseData('ok');
	}
}