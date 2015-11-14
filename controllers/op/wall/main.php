<?php
namespace op\wall;

require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 信息墙
 */
class main extends \member_base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 信息墙大屏幕页面
	 *
	 * $mpid
	 * $wall
	 *
	 */
	public function index_action($mpid, $wall) {
		$model = $this->model('app\wall');
		$w = $model->byId($wall, 'title');
		\TPL::assign('title', $w->title);

		$params = array();
		\TPL::assign('params', $params);

		\TPL::output('/op/wall/page');
		exit;
	}
	/**
	 *
	 */
	public function pageGet_action($mpid, $wall) {
		// page
		$page = $this->model('app\wall\page')->byType('op', $wall);
		if (empty($page)) {
			return new \ResponseError('没有获得页面定义');
		}
		$page = $page[0];
		$model = $this->model('app\wall');
		$wall = $model->byId($wall, 'title');

		$params = array(
			'wall' => $wall,
			'page' => $page,
		);

		return new \ResponseData($params);
	}
	/**
	 *
	 */
	public function messageList_action($mpid, $wall) {
		$model = $this->model('app\wall');
		$last = $this->getGet('last', 0);
		$m = $model->approvedMessages($mpid, $wall, $last);
		return new \ResponseData($m);
	}
}