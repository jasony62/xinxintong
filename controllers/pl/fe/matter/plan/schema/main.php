<?php
namespace pl\fe\matter\plan\schema;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/*
 * 计划任务活动主控制器
 */
class main extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/plan/frame');
		exit;
	}
}