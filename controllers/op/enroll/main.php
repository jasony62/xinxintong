<?php
namespace op\enroll;

require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 登记活动运营
 */
class main extends \member_base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 *
	 */
	public function index_action($aid, $page) {
		$options = array('cascaded' => 'N');
		$app = $this->model('app\enroll')->byId($aid, $options);

		\TPL::assign('title', $app->title);
		\TPL::output('/op/enroll/page');
		exit;
	}
	/**
	 *
	 */
	public function pageGet_action($mpid, $aid, $page) {
		$options = array('cascaded' => 'N');
		$app = $this->model('app\enroll')->byId($aid, $options);

		$page = $this->model('app\enroll\page')->byName($aid, $page);
		$params = array(
			'page' => $page,
		);
		if ($app->multi_rounds === 'Y') {
			$params['activeRound'] = $this->model('app\enroll\round')->getLast($mpid, $aid);
		}

		return new \ResponseData($params);
	}
}