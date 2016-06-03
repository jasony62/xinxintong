<?php
namespace pl\fe\site\user;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 站点用户管理控制器
 */
class account extends \pl\fe\base {
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
	public function list_action($site, $page = 1, $size = 30) {
		$model = $this->model();

		$result = array();
		$q = array(
			'uid,uname,nickname,reg_time',
			'xxt_site_account',
			"siteid='{$site}' and uname<>''",
		);
		$q2['o'] = 'reg_time desc';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;
		if ($users = $model->query_objs_ss($q, $q2)) {
			$result['users'] = $users;
			$q[0] = 'count(*)';
			$total = (int) $model->query_val_ss($q);
			$result['total'] = $total;
		} else {
			$result['users'] = array();
			$result['total'] = 0;
		}

		return new \ResponseData($result);
	}
	/**
	 * 重置用户口令
	 */
	public function resetPwd_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$data = $this->getPostJson();

		$modelMem = $this->model('site\user\account');

		$user = $modelMem->byId($data->userid);
		if (empty($user->salt)) {
			return new \ResponseError('用户没有设置过口令，不允许重置口令');
		}

		$rst = $modelMem->changePwd($site, $user->uname, $data->password, $user->salt);

		return new \ResponseData($rst);
	}
}