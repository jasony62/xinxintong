<?php
namespace pl\fe\site\user;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/*
 * 投稿活动主控制器
 */
class main extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/frame');
		exit;
	}
}