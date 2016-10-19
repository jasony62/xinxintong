<?php
namespace pl\be\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 平台用户组
 */
class group extends \pl\be\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/be/user/main');
		exit;
	}
	/**
	 * 返回用户组列表
	 */
	public function list_action() {
		$rst = $this->model('account')->getGroup();
		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function add_action($name) {
		$g['group_name'] = $name;
		$gid = $this->model()->insert('account_group', $g, true);

		$rst = $this->model('account')->getGroup($gid);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function update_action($gid) {
		$nv = (array) $this->getPostJson();

		$ret = $this->model()->update('account_group', $nv, "group_id=$gid");

		return new \ResponseData($ret);
	}
	/**
	 *
	 */
	public function remove_action($gid) {
		$q = array('count(*)', 'account_in_group', "group_id=$gid");
		if ((int) $this->model()->query_val_ss($q) != 0) {
			return new \ResponseError('用户组包含用户，不允许删除！');
		}

		$ret = $this->model()->delete('account_group', "group_id=$gid");

		return new \ResponseData($ret);
	}
}