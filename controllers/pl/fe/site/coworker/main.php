<?php
namespace pl\fe\site\coworker;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 团队成员管理控制器
 */
class main extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/coworker');
		exit;
	}
}