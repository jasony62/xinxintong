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
	public function list_action($page = 1, $size = 10) {
		$user = $this->who;
		if (!isset($user->unionid)) {
			return new \ResponseError('请登录后再进行该操作');
		}

		$modelSub = $this->model('site\user\subscription');
		$page = ['at' => $page, 'size' => $size];

		$result = $modelSub->byUser($user->unionid, null, ['page' => $page]);

		return new \ResponseData($result);
	}
	/**
	 *
	 */
	public function count_action($after = null) {
		$user = $this->who;
		if (!isset($user->unionid)) {
			return new \ResponseError('请登录后再进行该操作');
		}

		$modelSub = $this->model('site\user\subscription');

		$options = [];
		if (!empty($after)) {
			$options['afterAt'] = (int) $after;
		}
		$result = $modelSub->countByUser($user->unionid, $options);

		return new \ResponseData($result);
	}
}