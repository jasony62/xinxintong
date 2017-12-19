<?php
namespace site;
/**
 * 站点管理员
 */
class admin_model extends \TMS_MODEL {
	/**
	 *
	 */
	private function &_queryBy($where, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = array(
			$fields,
			'xxt_site_admin',
			$where,
		);

		$admins = $this->query_objs_ss($q);

		return $admins;
	}
	/**
	 * 检查用户是否为站点的管理员
	 */
	public function &byUid($siteId, $uid, $options = []) {
		$admin = $this->_queryBy("siteid='$siteId' and uid='$uid'", $options);

		$admin = count($admin) === 1 ? $admin[0] : false;

		return $admin;
	}
	/**
	 *
	 * $siteId
	 */
	public function &bySite($siteId, $options = []) {
		$where = "siteid='$siteId'";

		$admins = $this->_queryBy($where, $options);

		return $admins;
	}
	/**
	 *
	 * $siteId
	 */
	public function &byRole($siteId, $role, $options = []) {
		$where = "siteid='$siteId' and urole='$role'";

		$admins = $this->_queryBy($where, $options);

		return $admins;
	}
	/**
	 * 添加站点管理员
	 */
	public function add($user, $siteId, $admin) {
		if ($this->byUid($siteId, $admin->uid)) {
			return [false, '该账号已经是系统管理员，不能重复添加！'];
		}
		$this->insert(
			'xxt_site_admin',
			[
				'siteid' => $siteId,
				'uid' => $admin->uid,
				'ulabel' => $admin->ulabel,
				'urole' => isset($admin->urole) ? $this->escape($admin->urole) : 'A',
				'creater' => $user->id,
				'creater_name' => $this->escape($user->name),
				'create_at' => time(),
			],
			false
		);

		return [true];
	}
}