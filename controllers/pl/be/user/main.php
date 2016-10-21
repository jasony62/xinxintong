<?php
namespace pl\be\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 平台注册用户
 */
class main extends \pl\be\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/be/user/main');
		exit;
	}
}