<?php
namespace pl\fe\site\user;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 站点用户管理控制器
 */
class profile extends \pl\fe\base {
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
	public function get_action($site, $userid) {
		$model = $this->model();

		$result = array();
		$q = array(
			'uid,nickname,headimgurl,reg_time',
			'xxt_site_account',
			"uid='{$userid}'",
		);
		if ($user = $model->query_obj_ss($q)) {
			$result['user'] = $user;
			/* members */
			$members = $this->model('site\user\member')->byUser($site, $userid);
			$result['members'] = $members;
		}

		return new \ResponseData($result);
	}
	/**
	 *
	 */
	public function memberAdd_action($site, $userid, $schema) {
		$posted = $this->getPostJson();
		$schema = $this->model('site\user\memberschema')->byId($schema);

		$rst = $this->model('site\user\member')->create($site, $userid, $schema, $posted);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}
		$member = $rst[1];

		return new \ResponseData($member);
	}
}