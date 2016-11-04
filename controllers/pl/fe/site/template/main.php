<?php
namespace pl\fe\site\template;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 站点模板管理控制器
 */
class main extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/template');
		exit;
	}
}