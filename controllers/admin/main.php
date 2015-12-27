<?php
namespace admin;
/**
 * 系统管理
 */
class main extends \TMS_CONTROLLER {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 *
	 */
	public function index_action() {
		$this->view_action('/admin/main');
	}
	/**
	 * 返回现有注册用户的列表
	 */
	public function user_action($page, $size) {
		$rst = $this->model('account')->getAccount($page, $size);
		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function changeGroup_action($uid, $gid) {
		$ret = $this->model()->update(
			'account_in_group',
			array('group_id' => $gid),
			"account_uid='$uid'"
		);
		return new \ResponseData($ret);
	}
	/**
	 * 返回用户组列表
	 */
	public function group_action() {
		$rst = $this->model('account')->getGroup();
		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function addGroup_action($name) {
		$g['group_name'] = $name;
		$gid = $this->model()->insert('account_group', $g, true);

		$rst = $this->model('account')->getGroup($gid);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function updateGroup_action($gid) {
		$nv = (array) $this->getPostJson();

		$ret = $this->model()->update('account_group', $nv, "group_id=$gid");

		return new \ResponseData($ret);
	}
	/**
	 *
	 */
	public function removeUser_action($uid) {
		$rst = $this->model('account')->remove($uid);

		if ($rst) {
			return new \ResponseData('success');
		} else {
			return new \ResponseError('fail');
		}

	}
	/**
	 *
	 */
	public function removeGroup_action($gid) {
		$q = array('count(*)', 'account_in_group', "group_id=$gid");
		if ((int) $this->model()->query_val_ss($q) != 0) {
			return new \ResponseError('用户组包含用户，不允许删除！');
		}

		$ret = $this->model()->delete('account_group', "group_id=$gid");

		return new \ResponseData($ret);
	}
}