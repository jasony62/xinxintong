<?php
namespace mp;

require_once dirname(__FILE__) . '/mp_controller.php';

class permission extends mp_controller {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';

		return $rule_action;
	}
	/**
	 * 列出已经进行授权的用户
	 */
	public function user_action() {
		$q = array(
			'distinct p.uid,a.authed_id,a.email',
			'xxt_mppermission p,account a',
			"p.mpid='$this->mpid' and p.uid=a.uid",
		);

		if ($aUser = $this->model()->query_objs_ss($q)) {
			foreach ($aUser as &$u) {
				if (empty($u->authed_id)) {
					$u->authed_id = $u->email;
				}

			}
			return new \ResponseData($aUser);
		}

		return new \ResponseData(array());
	}
	/**
	 * 添加一个授权用户
	 *
	 * 要求提供注册用户的email
	 */
	public function addUser_action($authedid = null, $autoreg = 'N', $authapp = '') {
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
			'xxt_mppermission',
			"mpid='$this->mpid' and uid='$account->uid'",
		);
		if ((int) $this->model()->query_val_ss($q) > 0) {
			return new \ResponseError('已经为指定的账号设置了权限，不能重复添加！');
		}

		/**
		 * set initail permissions
		 */
		$otherPermissions = array(
			'mpsetting',
			'mpsetting_setting',
			'mpsetting_feature',
			'mpsetting_customapi',
			'mpsetting_permission',
			'mpsetting_administrator',
			'matter',
			'matter_article',
			'matter_text',
			'matter_news',
			'matter_channel',
			'matter_link',
			'matter_tmplmsg',
			'matter_media',
			'reply',
			'reply_text',
			'reply_menu',
			'reply_qrcode',
			'reply_other',
			'user',
			'user_received',
			'user_send',
			'user_fans',
			'user_member',
			'user_department',
			'user_tag',
			'user_fansgroup',
			'app',
			'app_enroll',
			'app_lottery',
			'app_wall',
			'app_addressbook',
			'app_contribute',
			'app_merchant',
			'analyze',
		);
		foreach ($otherPermissions as $perm) {
			$this->model()->insert('xxt_mppermission',
				array('mpid' => $this->mpid, 'uid' => $account->uid, 'permission' => $perm));
		}

		return new \ResponseData(array('uid' => $account->uid, 'authed_id' => $authedid));
	}
	/**
	 *
	 */
	public function removeUser_action($uid) {
		$rst = $this->model()->delete(
			'xxt_mppermission',
			"mpid='$this->mpid' and uid='$uid'"
		);
		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function updatePerm_action($uid, $perm) {
		$nv = $this->getPostJson();

		$rst = $this->model()->update(
			'xxt_mppermission',
			(array) $nv,
			"mpid='$this->mpid' and uid='$uid' and permission='$perm'"
		);

		return new \ResponseData($rst);
	}
	/**
	 * 获得指定用户的权限
	 */
	public function getRight_action($uid) {
		$q = array(
			'permission,create_p,read_p,update_p,delete_p',
			'xxt_mppermission',
			"mpid='$this->mpid' and uid='$uid'",
		);
		if ($aRight = $this->model()->query_objs_ss($q)) {
			$map = array();
			foreach ($aRight as $right) {
				$permission = $right->permission;
				unset($right->permission);
				$map[$permission] = $right;
			}
			return new \ResponseData($map);
		}
		return new \ResponseData(array());
	}
}