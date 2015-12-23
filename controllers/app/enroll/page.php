<?php
namespace app\enroll;

include_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 登记活动数据定义
 */
class page extends \member_base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 获得登记项定义
	 *
	 * @param string $mpid
	 * @param string $aid
	 */
	public function schemaGet_action($mpid, $aid) {
		$schema = $this->model('app\enroll\page')->schemaByApp($aid);

		return new \ResponseData($schema);
	}
}