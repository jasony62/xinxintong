<?php
namespace mp;

require_once dirname(__FILE__) . '/mp_controller.php';

class coin extends mp_controller {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';

		return $rule_action;
	}
	/**
	 *
	 */
	public function get_action() {
		$model = $this->model('coin\rule');
		$rules = $model->byMpid($this->mpid);

		return new \ResponseData($rules);
	}
	/**
	 *
	 */
	public function save_action() {
		$result = array();
		$model = $this->model();
		$rules = $this->getPostJson();
		foreach ($rules as $rule) {
			if (empty($rule->id) && $rule->delta != 0) {
				$rule->mpid = $this->mpid;
				$id = $model->insert('xxt_coin_rule', $rule, true);
				$result[$rule->act] = $id;
			} else {
				$model->update('xxt_coin_rule', array('delta' => $rule->delta), "id=$rule->id");
			}
		}
		return new \ResponseData($result);
	}
}