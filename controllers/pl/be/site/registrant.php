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
	public function list_action($page = 1, $size = 30) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$filter = $this->getPostJson();

		$model = $this->model();
		$result = [];
		$q = [
			'r.uid,r.email,r.nickname,r.reg_time,r.last_login,r.forbidden,s.name "site_name"',
			'account r,xxt_site s',
			"r.authed_from='xxt_site' and r.from_siteid=s.id",
		];
		if (!empty($filter->uname)) {
			$q[2] .= " and r.uname like '%{$filter->uname}%'";
		}
		$q2['o'] = 'r.reg_time desc';
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

		$user = $modelReg->byId($data->uid);
		if (empty($user->salt)) {
			return new \ResponseError('用户没有设置过口令，不允许重置口令');
		}

		$rst = $modelReg->changePwd($user->uname, $data->password, $user->salt);

		return new \ResponseData($rst);
	}
	/**
	 * 禁用站点用户注册帐号
	 */
	public function forbide_action() {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$user = $this->getPostJson();

		$this->model()->update('account', ['forbidden' => '1'], ['uid' => $user->uid]);

		return new \ResponseData('ok');
	}
	/**
	 * 激活被禁用的站点用户注册帐号
	 */
	public function active_action() {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$user = $this->getPostJson();

		$this->model()->update('account', ['forbidden' => '0'], ['uid' => $user->uid]);

		return new \ResponseData('ok');
	}
}