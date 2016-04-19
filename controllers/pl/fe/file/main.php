<?php
namespace pl\fe\file;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 文件主控制器
 */
class main extends \pl\fe\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/file/frame');
		exit;
	}
}