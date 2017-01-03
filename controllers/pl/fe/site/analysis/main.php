<?php
namespace pl\fe\site\analysis;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 团队运行统计管理控制器
 */
class main extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/analysis');
		exit;
	}
}