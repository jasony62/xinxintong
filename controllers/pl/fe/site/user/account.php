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
		$posted = $this->getPostJson();
		$nickname = empty($posted->nickname) ? '' : $posted->nickname;
		$result = array();

		$q = array(
			'uid,nickname,headimgurl,reg_time,ufrom,coin,unionid',
			'xxt_site_account',
			"siteid='$site'",
		);
		$q[2] .= " and nickname like '%$nickname%'";
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
}