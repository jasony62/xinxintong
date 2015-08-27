<?php
namespace app\enroll;

include_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 登记活动
 */
class base extends \member_base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 *
	 */
	protected function canAccessObj($mpid, $aid, $member, $authapis, $act) {
		return $this->model('acl')->canAccessMatter($mpid, 'enroll', $aid, $member, $authapis);
	}
}
