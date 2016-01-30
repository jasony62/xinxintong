<?php
namespace mp\matter;

require_once dirname(dirname(__FILE__)) . '/mp_controller.php';

class tag extends \mp\mp_controller {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';
		$rule_action['actions'][] = 'index';
		return $rule_action;
	}
	/**
	 * 根据资源类型获得已有的标签
	 * @param string $resType
	 * @param int 标签的分类
	 */
	public function index_action($resType, $subType = 0) {
		$tags = $this->model('tag')->get_tags($this->mpid, $resType, $subType);

		return new \ResponseData($tags);
	}
}