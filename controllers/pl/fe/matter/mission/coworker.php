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
		$acl = $this->model('matter\mission\acl')->add($user, $mission, $coworker);

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
}