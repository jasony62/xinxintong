<?php
namespace op\enroll;

require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 登记活动轮次查询
 */
class round extends \member_base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 *
	 */
	public function list_action($mpid, $aid) {
		$modelRun = $this->model('app\enroll\round');
		$options = array(
			'fields' => 'rid,title',
			'state' => '1,2',
		);
		$rounds = $modelRun->byApp($mpid, $aid, $options);

		return new \ResponseData($rounds);
	}
}