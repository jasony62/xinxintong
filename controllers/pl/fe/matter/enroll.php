<?php
namespace pl\fe\matter;

require_once dirname(__FILE__) . '/base.php';
/*
 * 文章控制器
 */
class enroll extends base {
	/**
	 *
	 */
	protected function getMatterType() {
		return 'enroll';
	}
	/**
	 * 返回单图文视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
}