<?php
namespace pl\fe\matter\plan;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 计划任务活动主控制器
 */
class user extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/plan/frame');
		exit;
	}
	/**
	 * 获得当前用户所属分组活动分组
	 */
	private function _getUserGroup($oApp, $oUser) {
		$oEntryRule = $oApp->entryRule;
		if (empty($oEntryRule->scope->group) || $oEntryRule->scope->group !== 'Y' || empty($oEntryRule->group->id)) {
			return null;
		}

		$oGroup = new \stdClass;
		/* 限分组用户访问 */
		$oGroupApp = $oEntryRule->group;
		$oGroupUsr = $this->model('matter\group\player')->byUser($oGroupApp, $oUser->uid, ['fields' => 'round_id,round_title']);

		if (count($oGroupUsr)) {
			$oGroupUsr = $oGroupUsr[0];
			if (isset($oGroupApp->round->id)) {
				if ($oGroupUsr->round_id === $oGroupApp->round->id) {
					return $oGroupUsr;
				}
			} else {
				return $oGroupUsr;
			}
		}

		return null;
	}
	/**
	 *
	 */
	public function add_action($app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$app = $this->escape($app);
		$modelApp = $this->model('matter\plan');

		$oApp = $modelApp->byId($app, ['fields' => 'id,state,siteid,entry_rule']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$oPosted = $this->getPostJson();
		if (empty($oPosted)) {
			return new \ParameterError();
		}

		$users = [];
		if (!empty($oPosted->members)) {
			$modelAct = $this->model('site\user\account');
			$modelUsr = $this->model('matter\plan\user');
			foreach ($oPosted->members as $oMember) {
				$oUser = $modelAct->byId($oMember->userid, ['uid,nickname']);
				/* 用户的分组信息 */
				if ($oGroup = $this->_getUserGroup($oApp, $oUser)) {
					$oUser->group_id = $oGroup->round_id;
					$oUser->group_title = $oGroup->round_title;
				}
				if (false === $modelUsr->byUser($oApp, $oUser, ['fields' => 'id'])) {
					$users[] = $modelUsr->createOrUpdate($oApp, $oUser);
				}
			}
		}

		return new \ResponseData($users);
	}
	/**
	 *
	 */
	public function list_action($app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$app = $this->escape($app);
		$modelApp = $this->model('matter\plan');

		$oApp = $modelApp->byId($app, ['fields' => 'id,state,entry_rule']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$oResult = new \stdClass;
		$modelUsr = $this->model('matter\plan\user');
		$q = [
			'id,nickname,userid,group_id,start_at,last_enroll_at,task_num,score,coin,comment',
			'xxt_plan_user',
			['aid' => $oApp->id],
		];
		$q2 = ['o' => 'last_enroll_at desc'];

		$users = $modelUsr->query_objs_ss($q, $q2);
		$oEntryRule = $oApp->entryRule;
		if (!empty($oEntryRule->scope->group) && $oEntryRule->scope->group === 'Y' && !empty($oEntryRule->group->id)) {
			$modelGrpRnd = $this->model('matter\group\round');
			foreach ($users as $oUser) {
				if (!empty($oUser->group_id)) {
					$oGroupRnd = $modelGrpRnd->byId($oUser->group_id, ['fields' => 'title']);
					$oUser->group_title = $oGroupRnd->title;
				}
			}
		}
		$oResult->users = $users;

		return new \ResponseData($oResult);
	}
}