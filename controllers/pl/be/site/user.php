<?php
namespace pl\be\site;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 平台站点注册用户
 */
class user extends \pl\be\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/be/site/user');
		exit;
	}
}