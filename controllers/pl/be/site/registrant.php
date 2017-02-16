<?php
namespace pl\be\site;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 平台站点注册用户
 */
class registrant extends \pl\be\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/be/site/user');
		exit;
	}
	/**
	 *
	 */
	public function list_action($site, $page = 1, $size = 30) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$model = $this->model();

		$result = [];
		$q = [
			'unionid,uname,nickname,reg_time',
			'xxt_site_registration',
		];
		$q2['o'] = 'reg_time desc';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;
		if ($users = $model->query_objs_ss($q, $q2)) {
			$result['users'] = $users;
			$q[0] = 'count(*)';
			$total = (int) $model->query_val_ss($q);
			$result['total'] = $total;
		} else {
			$result['users'] = [];
			$result['total'] = 0;
		}

		return new \ResponseData($result);
	}
	/**
	 * 重置用户口令
	 */
	public function resetPwd_action() {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$data = $this->getPostJson();

		$modelReg = $this->model('site\user\registration');

		$user = $modelReg->byId($data->unionid);
		if (empty($user->salt)) {
			return new \ResponseError('用户没有设置过口令，不允许重置口令');
		}

		$rst = $modelReg->changePwd($user->uname, $data->password, $user->salt);

		return new \ResponseData($rst);
	}
}