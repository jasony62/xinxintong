<?php
namespace pl\fe\site\user;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 站点用户管理控制器
 */
class main extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/user');
		exit;
	}
	/**
	 *
	 */
	public function member_action() {
		\TPL::output('/pl/fe/site/user');
		exit;
	}
	/**
	 * 用户访问详情列表
	 */
	public function readList_action($site, $uid, $page = 1, $size = 12) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();
		$q = [
			'*',
			'xxt_log_matter_read',
			['siteid' => $site, 'userid' => $uid],
		];
		$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		$q2['o'] = ['read_at desc'];

		$matters = $model->query_objs_ss($q, $q2);

		$result = new \stdClass;
		$result->matters = $matters;
		if (empty($matters)) {
			$result->total = 0;
		} else {
			$q[0] = 'count(*)';
			$result->total = $model->query_val_ss($q);
		}

		return new \responseData($result);
	}
}