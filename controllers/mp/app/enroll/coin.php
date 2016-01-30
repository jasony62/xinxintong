<?php
namespace mp\app\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 积分规则
 */
class coin extends \mp\app\app_base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';

		return $rule_action;
	}
	/**
	 *
	 */
	public function index_action() {
		$this->view_action('/mp/app/enroll/detail');
	}
	/**
	 *
	 */
	public function get_action($aid) {
		$model = $this->model('coin\rule');

		$prefix = 'app.enroll,' . $aid . '.';
		$rules = $model->byPrefix($this->mpid, $prefix);

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