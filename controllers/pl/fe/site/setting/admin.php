<?php
namespace pl\fe\site\setting;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 管理站点管理员控制器
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
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelAdm = $this->model('site\admin');

		$admins = $modelAdm->bySite($site);

		return new \ResponseData($admins);
	}
	/**
	 * 添加站点管理员
	 */
	public function add_action($site, $ulabel = null, $authapp = '', $autoreg = 'N') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		if (empty($ulabel) && defined('TMS_APP_ADDON_EXTERNAL_ORG')) {
			return new \ResponseData(array('externalOrg' => TMS_APP_ADDON_EXTERNAL_ORG));
		}

		$model = $this->model('account');
		$account = $model->getAccountByAuthedId($ulabel);

		if (!$account) {
			if ($autoreg !== 'Y') {
				return new \ResponseError('指定的账号不是注册账号，请先注册！');
			} else {
				$account = $model->authed_from($ulabel, $authapp, '0.0.0.0', $ulabel);
			}
		}
		/**
		 * exist?
		 */
		$modelAdm = $this->model('site\admin');
		if ($admin = $modelAdm->byUid($site, $account->uid)) {
			return new \ResponseError('该账号已经是系统管理员，不能重复添加！');
		}

		$modelAdm->insert(
			'xxt_site_admin',
			[
				'siteid' => $site,
				'uid' => $account->uid,
				'ulabel' => $ulabel,
				'creater' => $user->id,
				'creater_name' => $user->name,
				'create_at' => time(),
			],
			false
		);
		/**
		 * 对已经存在的资源进行授权。
		 * @todo 这部分代码是否应该改为用队列实现？
		 */
		$coworker = new \stdClass;
		$coworker->id = $account->uid;
		$coworker->label = $ulabel;
		$this->model('matter\mission\acl')->addSiteAdmin($site, $user, $coworker);

		return new \ResponseData(['uid' => $account->uid, 'ulabel' => $ulabel]);
	}
	/**
	 * 删除站点管理员
	 */
	public function remove_action($site, $uid) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model()->delete(
			'xxt_site_admin',
			"siteid='$site' and uid='$uid'"
		);
		/**
		 * 取消对资源的授权。
		 * @todo 这部分代码是否应该改为用队列实现？
		 */
		$modelAcl = $this->model('matter\mission\acl');
		$coworker = new \stdClass;
		$coworker->id = $uid;
		$modelAcl->removeSiteAdmin($site, $coworker);

		return new \ResponseData($rst);
	}
}