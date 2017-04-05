<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 用户关注的信息
 */
class subscribe extends \site\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/site/fe/user/subscribe/main');
		exit;
	}
	/**
	 *
	 *
	 */
	public function list_action($site, $page = 1, $size = 10) {
		$user = $this->who;
		$result = new \stdClass;

		$modelSub = $this->model('site\user\subscription');
		$page = ['at' => $page, 'size' => $size];

		$result = $modelSub->byUser($user->uid, $site, ['page' => $page]);

		return new \ResponseData($result);
	}
}