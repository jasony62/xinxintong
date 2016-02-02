<?php
namespace mp\matter;

require_once dirname(__FILE__) . '/matter_ctrl.php';

class inner extends \mp\mp_controller {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';
		return $rule_action;
	}
	/**
	 *
	 */
	public function index_action() {
		$p = array(
			'id,title,name',
			'xxt_inner',
		);
		$replies = $this->model()->query_objs_ss($p);
		return new \ResponseData($replies);
	}
	/**
	 *
	 */
	public function get_action($id = null) {
		if ($id === null) {
			return $this->index_action();
		} else {
			$inner = $this->model('matter\inner')->byId($id);

			return new \ResponseData($inner);
		}
	}
	/**
	 *
	 */
	public function list_action() {
		return $this->index_action();
	}
	/**
	 * 更新
	 *
	 * $id inner's id
	 * $nv pair of name and value
	 */
	public function update_action($id) {
		$nv = (array) $this->getPostJson();

		$rst = $this->model()->update(
			'xxt_inner',
			$nv,
			"mpid='$this->mpid' and id='$id'"
		);

		return new \ResponseData($rst);
	}
}