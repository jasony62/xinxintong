<?php
namespace pl\fe\matter\mission;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 项目控制器
 */
class coworker extends \pl\fe\matter\base {
	/**
	 * 任务下的合作人
	 *
	 * @param string $site
	 * @param int $mission mission's id
	 */
	public function list_action($site, $mission) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$options = [
			'fields' => 'inviter,invite_at,coworker,coworker_label,join_at',
			'excludeOwner' => 'Y',
			'excludeAdmin' => 'Y',
		];
		$coworkers = $this->model('matter\mission\acl')->byMission($mission, $options);

		if (!empty($coworkers)) {
			$modelAcnt = $this->model('account');
			foreach ($coworkers as &$coworker) {
				$account = $modelAcnt->byId($coworker->coworker, ['fields' => 'nickname']);
				$coworker->account = $account;
			}
		}

		return new \ResponseData($coworkers);
	}
	/**
	 * 任务下的合作人
	 *
	 * @param string $site
	 * @param int $mission mission's id
	 */
	public function mine_action($site, $mission) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$options = [
			'fields' => 'inviter,invite_at,coworker,coworker_label,join_at',
		];
		$coworkers = $this->model('matter\mission\acl')->byUser($user, $mission, $options);

		if (!empty($coworkers)) {
			$modelAcnt = $this->model('account');
			foreach ($coworkers as &$coworker) {
				$account = $modelAcnt->byId($coworker->coworker, ['fields' => 'nickname']);
				$coworker->account = $account;
			}
		}

		return new \ResponseData($coworkers);
	}
	/**
	 * 增加项目合作人
	 *
	 * @param string $site
	 * @param int $mission mission's id
	 */
	public function add_action($site, $mission, $label) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelMis = $this->model('matter\mission');
		$mission = $modelMis->byId($mission, ['cascaded' => 'N']);

		$modelAcnt = $this->model('account');
		$account = $modelAcnt->getAccountByAuthedId($label);
		if (!$account) {
			return new \ResponseError('指定的账号不是注册账号，请先注册！');
		}
		/**
		 * has joined?
		 */
		$modelAcl = $this->model('matter\mission\acl');
		$acl = $modelAcl->byCoworker($mission->id, $account->uid);
		if ($acl) {
			return new \ResponseError('该账号已经是合作人，不能重复添加！');
		}
		/*加入ACL*/
		$coworker = new \stdClass;
		$coworker->id = $account->uid;
		$coworker->label = $account->email;
		$acl = $modelAcl->add($user, $mission, $coworker);
		$acl->account = (object) ['nickname' => $account->nickname];

		return new \ResponseData($acl);
	}
	/**
	 * 删除项目的合作人
	 *
	 * @param string $site
	 * @param int $mission mission's id
	 */
	public function remove_action($site, $mission, $coworker) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model()->delete(
			'xxt_mission_acl',
			"mission_id='$mission' and coworker='$coworker'"
		);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function makeInvite_action($site, $mission) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTsk = $this->model('task\token');

		$name = "share.mission:$mission";
		$params = new \stdClass;
		$params->site = $site;
		$params->mission = $mission;
		$params->_version = 1;

		$code = $modelTsk->makeTask($site, $user, $name, $params, 1800);

		$url = '/rest/pl/fe/matter/mission/invite?code=' . $code;

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

		if (!empty($task->params->mission)) {
			$mission = $this->model('matter\mission')->byId($task->params->mission, ['fields' => 'title']);
			$task->mission = $mission;
		}

		return new \ResponseData($task);
	}
	/**
	 * 被邀请参与项目的人接受邀请
	 *
	 * @param string $code 邀请码
	 *
	 */
	public function acceptInvite_action($code) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$account = $this->model('account')->byId($user->id, ['fields' => 'email']);
		/**
		 * 检查邀请码，获取任务
		 */
		$mdoelTsk = $this->model('task\token');
		$task = $mdoelTsk->taskByCode($code);
		if (!$task) {
			return new \ResponseError('邀请不存在或已经过期，请检查邀请码是否正确。');
		}
		$mdoelTsk->closeTask($user, $code);

		$mission = $this->model('matter\mission')->byId($task->params->mission);
		/**
		 * has joined?
		 */
		$modelAcl = $this->model('matter\mission\acl');
		$acl = $modelAcl->byCoworker($mission->id, $user->id);
		if ($acl) {
			return new \ResponseError('该账号已经是合作人，不能重复添加！');
		}
		/**
		 * 加入ACL
		 */
		$coworker = new \stdClass;
		$coworker->id = $user->id;
		$coworker->label = $account->email;
		$acl = $modelAcl->add($user, $mission, $coworker);

		return new \ResponseData($acl);
	}
}