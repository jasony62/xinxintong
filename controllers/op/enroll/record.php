<?php
namespace op\enroll;

require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 登记活动记录查询
 */
class record extends \member_base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 *
	 */
	public function list_action($mpid, $aid, $rid = '', $enroller = '') {
		$options = array(
			'page' => 1,
			'size' => 30,
			'rid' => $rid,
			'creater' => $enroller,
		);
		$mdoelRec = $this->model('app\enroll\record');
		$result = $mdoelRec->find($mpid, $aid, $options);

		return new \ResponseData($result);
	}
	/**
	 *
	 */
	public function enrollerList_action($mpid, $aid, $rid = '') {
		$mdoelRec = $this->model('app\enroll\record');
		$result = $mdoelRec->enrollers($aid, $rid);

		return new \ResponseData($result);
	}
}