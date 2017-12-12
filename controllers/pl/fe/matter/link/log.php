<?php
namespace pl\fe\matter\link;

require_once dirname(dirname(__FILE__)) . '/main_base.php';
/*
 * 登记活动日志控制器
 */
class log extends \pl\fe\matter\main_base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/link/frame');
		exit;
	}
	/**
	 * 查询日志
	 *
	 */
	public function list_action($id, $page = 1, $size = 30) {
		$modelLog = $this->model('matter\log');

		$reads = $modelLog->listUserMatterOp($id, 'link', [], $page, $size);

		return new \ResponseData($reads);
	}
}