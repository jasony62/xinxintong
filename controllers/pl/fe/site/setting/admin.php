<?php
namespace pl\fe\site\setting;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 *
 */
class admin extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/setting');
		exit;
	}
	/**
	 * 站点管理员
	 */
	public function list_action($site) {
		$user = $this->accountUser();
		if (false === $user) {
			return new \ResponseTimeout();
		}

		$q = array(
			'a.uid,a.authed_id,a.email',
			'xxt_site_admin s,account a',
			"s.siteid='$site' and s.uid=a.uid",
		);
		$admins = $this->model()->query_objs_ss($q);
		foreach ($admins as &$a) {
			if (empty($a->authed_id)) {
				$a->authed_id = $a->email;
			}
		}

		return new \ResponseData($admins);
	}
	/**
	 * 添加站点管理员
	 */
	public function add_action($site, $authedid = null, $authapp = '', $autoreg = 'N') {
		$user = $this->accountUser();
		if (false === $user) {
			return new \ResponseTimeout();
		}

		if (empty($authedid) && defined('TMS_APP_ADDON_EXTERNAL_ORG')) {
			return new \ResponseData(array('externalOrg' => TMS_APP_ADDON_EXTERNAL_ORG));
		}

		$model = $this->model('account');
		$account = $model->getAccountByAuthedId($authedid);

		if (!$account) {
			if ($autoreg !== 'Y') {
				return new \ResponseError('指定的账号不是注册账号，请先注册！');
			} else {
				$account = $model->authed_from($authedid, $authapp, '0.0.0.0', $authedid);
			}
		}
		/**
		 * exist?
		 */
		$q = array(
			'count(*)',
			'xxt_site_admin',
			"siteid='$site' and uid='{$account->uid}'",
		);
		if ((int) $this->model()->query_val_ss($q) > 0) {
			return new \ResponseError('该账号已经是系统管理员，不能重复添加！');
		}

		$this->model()->insert(
			'xxt_site_admin',
			array(
				'siteid' => $site,
				'uid' => $account->uid,
				'creater' => $user->id,
				'creater_name' => $user->name,
				'create_at' => time(),
			),
			false
		);

		return new \ResponseData(array('uid' => $account->uid, 'authed_id' => $authedid));
	}
	/**
	 * 删除站点管理员
	 */
	public function remove_action($site, $uid) {
		$user = $this->accountUser();
		if (false === $user) {
			return new \ResponseTimeout();
		}

		$rst = $this->model()->delete(
			'xxt_site_admin',
			"siteid='$site' and uid='$uid'"
		);
		return new \ResponseData($rst);
	}
}