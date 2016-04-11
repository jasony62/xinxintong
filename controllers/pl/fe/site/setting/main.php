<?php
namespace pl\fe\site\setting;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 *
 */
class main extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/setting');
		exit;
	}
}