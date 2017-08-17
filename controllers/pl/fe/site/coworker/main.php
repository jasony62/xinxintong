<?php
namespace pl\fe\site\coworker;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 团队成员管理控制器
 */
class main extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/coworker');
		exit;
	}
	/**
	 * 创建团队管理员邀请链接
	 *
	 */
	public function makeInvite_action($site) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelSite = $this->model('site');
		$modelTsk = $this->model('task\token');

		$oSite = $modelSite->byId($site);

		$title = "share.site:{$oSite->id}";
		$params = new \stdClass;
		$params->site = $site;
		$params->creater = $oSite->creater;
		$params->invitor = $oUser->name;
		$params->name = $oSite->name;
		$params->_version = 1;

		$code = $modelTsk->makeTask($site, $oUser, $title, $params, 1800);

		$url = '/rest/pl/fe/site/invite?code=' . $code;

		return new \ResponseData($url);
	}
	/**
	 * 查看邀请
	 *
	 * @param string $code 邀请码
	 *
	 */
	public function invite_action($code) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		/**
		 * 检查邀请码，获取任务
		 */
		$mdoelTsk = $this->model('task\token');
		$task = $mdoelTsk->taskByCode($code);
		if (!$task) {
			return new \ResponseError('邀请不存在或已经过期，请检查邀请码是否正确。');
		}

		if (!empty($task->params->site)) {
			$data = $this->model('site')->byId($task->params->site);
			$task->data = $data;
		}

		return new \ResponseData($task);
	}
	/**
	 * 被邀请参与团队的人接受邀请
	 * 邀请任务不能随便关闭 因为同一个邀请链接 其他人也会用到
	 * 建议：等邀请码过期删掉即可
	 * @param string $code 邀请码
	 *
	 */
	public function acceptInvite_action($code) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$account = $this->model('account')->byId($user->id);
		/**
		 * 检查邀请码，获取任务
		 */
		$mdoelTsk = $this->model('task\token');
		$oTask = $mdoelTsk->taskByCode($code);

		if (!$oTask) {
			return new \ResponseError('邀请不存在或已经过期，请检查邀请码是否正确。');
		}
		if (empty($oTask->params)) {
			return new \ResponseError('邀请任务参数错误。');
		}
		$site = $oTask->params->site;
		//$mdoelTsk->closeTask($user, $code);

		/**
		 * exist?
		 */
		$modelAdm = $this->model('site\admin');
		$admin = new \stdClass;
		$admin->uid = $account->uid;
		$admin->ulabel = $account->nickname;
		$admin->siteid = $site;
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
		$coworker->label = $account->nickname;
		$this->model('matter\mission\acl')->addSiteAdmin($site, $user, $coworker);

		return new \ResponseData($admin);
	}
}