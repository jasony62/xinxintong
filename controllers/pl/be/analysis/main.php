<?php
namespace pl\be\analysis;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 数据分析
 */
class main extends \pl\be\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/be/analysis/main');
		exit;
	}
}