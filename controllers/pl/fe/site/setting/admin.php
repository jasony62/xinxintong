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

		$admins = $modelAdm->byRole($site, 'A');

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
		if (($this->model('site')->byId($site, ['fields' => 'id'])) === false) {
			return new \ResponseError('指定团队不存在');
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
		$admin = new \stdClass;
		$admin->uid = $account->uid;
		$admin->ulabel = $account->nickname;
		$rst = $modelAdm->add($user, $site, $admin);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}
		/**
		 * 对已经存在的资源进行授权。
		 * @todo 这部分代码是否应该改为用队列实现？
		 */
		$coworker = new \stdClass;
		$coworker->id = $account->uid;
		$coworker->label = $ulabel;
		$this->model('matter\mission\acl')->addSiteAdmin($site, $user, $coworker);

		return new \ResponseData($admin);
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
	/**
	 * 移交团队
	 */
	public function transferSite_action($site, $label) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		
		$modelSite = $this->model('site');
		$matter = $modelSite->byId($site, ['fields' => 'creater,id,name as title']);
		if($user->id !== $matter->creater){
			return new \ResponseError('只有创建者才有此权限');
		}

		$label = $modelSite->escape($label);
		$account =$this->model('account')->getAccountByAuthedId($label);
		if (!$account) {
			return new \ResponseError('指定的账号不是注册账号，请先注册！');
		}
		if($account->uid === $matter->creater){
			return new \ResponseError('用户已是团队创建者');
		}

		$rst = $modelSite->update(
			'xxt_site',
			['creater' => $account->uid, 'creater_name' => $account->nickname],
			['id' => $site]
		);
		if($rst){
			//修改原作者作为管理员的权限
			$modelSite->update(
				'xxt_site_admin',
				['urole' => 'A'],
				['siteid' => $site, 'uid' => $matter->creater]
			);

			$modelAdm = $this->model('site\admin');
			$admin = new \stdClass;
			$admin->uid = $account->uid;
			$admin->ulabel = $account->nickname;
			$admin->urole = 'O';
			$adminRst = $modelAdm->add($user, $site, $admin);
			if(!$adminRst[0]){
				//修改作者作为管理员的权限
				$modelSite->update(
					'xxt_site_admin',
					['urole' => 'O'],
					['siteid' => $site, 'uid' => $admin->uid]
				);
			}
			/**
			 * 对已经存在的资源进行授权。
			 * @todo 这部分代码是否应该改为用队列实现？
			 */
			$coworker = new \stdClass;
			$coworker->id = $account->uid;
			$coworker->label = $account->nickname;
			$this->model('matter\mission\acl')->addSiteAdmin($site, $user, $coworker);

			/*记录操作日志*/
			$matter->type = 'site';
			$this->model('matter\log')->matterOp($site, $user, $matter, 'transfer');
		}

		return new \ResponseData($rst);
	}
}