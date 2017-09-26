<?php
namespace pl\fe\matter\mission;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 项目控制器
 */
class coworker extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/mission/frame');
		exit;
	}
	/**
	 * 任务下的合作人
	 *
	 * @param int $mission mission's id
	 */
	public function list_action($mission) {
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
	 * @param int $mission mission's id
	 */
	public function mine_action($mission) {
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
	 * @param int $mission mission's id
	 */
	public function add_action($mission, $label) {
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
		$mission = $modelMis->escape($mission);
		$coworker = new \stdClass;
		$coworker->id = $account->uid;
		$coworker->label = $account->email;
		$acl = $modelAcl->add($user, $mission, $coworker);
		$acl->account = (object) ['nickname' => $account->nickname];

		return new \ResponseData($acl);
	}
	/**
	 * 删除项目的合作人
	 * 如果被删除的是团队管理员，修改用户的角色
	 *
	 * @param int $mission mission's id
	 */
	public function remove_action($mission, $coworker) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelMis = $this->model('matter\mission');
		$oMission = $modelMis->byId($mission, ['cascaded' => 'N']);

		$modelAdm = $this->model('site\admin');
		if ($modelAdm->byUid($oMission->siteid, $coworker)) {
			$rst = $modelMis->update(
				'xxt_mission_acl',
				['coworker_role' => 'A'],
				"mission_id='$mission' and coworker='$coworker' and coworker_role='C'"
			);
		} else {
			$rst = $modelMis->delete(
				'xxt_mission_acl',
				"mission_id='$mission' and coworker='$coworker' and coworker_role='C'"
			);
		}

		return new \ResponseData($rst);
	}
	/**
	 *
	 * @param int $mission mission's id
	 */
	public function makeInvite_action($mission) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelMis = $this->model('matter\mission');
		$modelTsk = $this->model('task\token');

		$mission = $modelMis->byId($mission, ['cascaded' => 'N']);

		$name = "share.mission:{$mission->id}";
		$params = new \stdClass;
		$params->site = $mission->siteid;
		$params->mission = $mission->id;
		$params->_version = 1;

		$code = $modelTsk->makeTask($mission->siteid, $user, $name, $params, 1800);

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
	/**
	 * 移交项目
	 */
	public function transferMission_action($site, $mission, $label) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelMis = $this->model('matter\mission');
		$oMission = $modelMis->byId($mission);
		if ($user->id !== $oMission->creater) {
			return new \ResponseError('只有创建者才有此权限');
		}

		$label = $modelMis->escape($label);
		$oNewOwner = $this->model('account')->getAccountByAuthedId($label);
		if (!$oNewOwner) {
			return new \ResponseError('指定的账号不是注册账号，请先注册！');
		}
		if ($oNewOwner->uid === $oMission->creater) {
			return new \ResponseError('用户已是项目创建者');
		}

		/* modifier */
		$nv = new \stdClass;
		$nv->modifier = $user->id;
		$nv->modifier_src = $user->src;
		$nv->modifier_name = $modelMis->escape($user->name);
		$nv->modify_at = time();
		$nv->creater = $oNewOwner->uid;
		$nv->creater_name = $oNewOwner->nickname;
		$rst = $modelMis->update(
			'xxt_mission',
			$nv,
			"id='$oMission->id'"
		);
		if ($rst) {
			//修改原作者作为管理员的权限
			if ($this->model('site\admin')->byUid($oMission->siteid, $oMission->creater)) {
				$modelMis->update(
					'xxt_mission_acl',
					['coworker_role' => 'A'],
					['mission_id' => $oMission->id, 'coworker_role' => 'O', "last_invite" => 'Y']
				);
			} else {
				$modelMis->update(
					'xxt_mission_acl',
					['coworker_role' => 'C'],
					['mission_id' => $oMission->id, 'coworker_role' => 'O', "last_invite" => 'Y']
				);
			}
			$modelAcl = $this->model('matter\mission\acl');
			$acl = $modelAcl->byCoworker($oMission->id, $oNewOwner->uid);
			if (!$acl) {
				/*加入ACL*/
				$oMission->creater = $oNewOwner->uid;
				$oMission->creater_name = $oNewOwner->nickname;
				$coworker = new \stdClass;
				$coworker->id = $oNewOwner->uid;
				$coworker->label = $oNewOwner->nickname;
				$modelAcl->add($user, $oMission, $coworker, 'O');
			} else {
				//修改原作者作为管理员的权限
				$modelMis->update(
					'xxt_mission_acl',
					['coworker_role' => 'O'],
					["mission_id" => $oMission->id, "coworker" => $oNewOwner->uid, "last_invite" => 'Y']
				);
			}

			$modelMis->update(
				'xxt_mission_acl',
				['creater' => $oNewOwner->uid, 'creater_name' => $modelMis->escape($oNewOwner->nickname)],
				['mission_id' => $oMission->id]
			);

			/*记录操作日志*/
			$this->model('matter\log')->matterOp($site, $user, $oMission, 'transfer');
		}

		return new \ResponseData($rst);
	}
}