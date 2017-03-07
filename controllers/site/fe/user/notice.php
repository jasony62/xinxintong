<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 用户收到的通知
 */
class notice extends \site\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/site/fe/user/notice/main');
		exit;
	}
	/**
	 *
	 *
	 */
	public function list_action($page = 1, $size = 10) {
		$result = new \stdClass;

		return new \ResponseData($result);
	}
}