<?php
namespace mp;

require_once dirname(__FILE__) . "/mp_controller.php";
/**
 *
 */
class relay extends mp_controller {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';

		return $rule_action;
	}
	/**
	 * 获得定义的转发接口
	 */
	public function get_action() {
		$relays = $this->model('mp\relay')->byMpid($this->mpid);

		return new \ResponseData($relays);
	}
	/**
	 * 添加转发接口
	 */
	public function add_action() {
		$r = array();
		$r['mpid'] = $this->mpid;
		$r['title'] = '新转发接口';

		$r = $this->model('mp\relay')->add($r);

		return new \ResponseData($r);
	}
	/**
	 * 更新转发接口
	 */
	public function update_action($id) {
		$nv = $this->getPostJson();

		$rst = $this->model()->update(
			'xxt_mprelay',
			(array) $nv,
			"id='$id'"
		);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function remove_action($id) {
		$rst = $this->model()->update(
			'xxt_mprelay',
			array('state' => 0),
			"mpid='$this->mpid' and id='$id'"
		);

		return new \ResponseData($rst);
	}
}