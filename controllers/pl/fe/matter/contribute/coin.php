<?php
namespace pl\fe\matter\contribute;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 投稿活动主控制器
 */
class coin extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/contribute/frame');
		exit;
	}
	/**
	 *
	 */
	public function get_action($site, $app) {
		$model = $this->model('coin\rule');

		$prefix = 'app.contribute,' . $app . '.';
		$rules = $model->byPrefix($site, $prefix);

		return new \ResponseData($rules);
	}
	/**
	 *
	 */
	public function save_action($site) {
		$result = array();
		$model = $this->model();
		$rules = $this->getPostJson();
		foreach ($rules as $rule) {
			if (empty($rule->id) && $rule->delta != 0) {
				$rule->siteid = $site;
				$id = $model->insert('xxt_coin_rule', $rule, true);
				$result[$rule->act] = $id;
			} else {
				$model->update('xxt_coin_rule', array('delta' => $rule->delta), "id=$rule->id");
			}
		}
		return new \ResponseData($result);
	}
}