<?php
namespace pl\be\setting;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 平台日志
 */
class main extends \pl\be\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/be/setting/main');
		exit;
	}
}