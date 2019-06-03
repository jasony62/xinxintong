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
	 * 添加用户组
	 */
	public function add_action() {
		$modelAcnt = $this->model('account');

		$q = ['max(group_id)', 'account_group', '1=1'];
		$maxId = (int) $modelAcnt->query_val_ss($q);
		$newGroupId = $maxId + 1;

		$oPosted = $this->getPostJson();

		$g['group_id'] = $newGroupId;
		$g['group_name'] = empty($oPosted->name) ? '新用户组' : $oPosted->name;

		$modelAcnt->insert('account_group', $g, false);

		$oNewGroup = $modelAcnt->getGroup($newGroupId);

		return new \ResponseData($oNewGroup);
	}
	/**
	 * 更新用户组
	 */
	public function update_action($gid) {
		$nv = $this->getPostJson();

		$ret = $this->model()->update('account_group', $nv, ['group_id' => $gid]);

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