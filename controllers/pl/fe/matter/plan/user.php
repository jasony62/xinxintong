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
		$oGroupUsr = $this->model('matter\group\user')->byUser($oGroupApp, $oUser->uid, ['fields' => 'team_id,team_title']);

		if (count($oGroupUsr)) {
			$oGroupUsr = $oGroupUsr[0];
			if (isset($oGroupApp->team->id)) {
				if ($oGroupUsr->team_id === $oGroupApp->team->id) {
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
		if (false === ($oUserP = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$app = $this->escape($app);
		$modelApp = $this->model('matter\plan');

		$oApp = $modelApp->byId($app, ['fields' => 'id,state,siteid,entry_rule,title,summary']);
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
					$oUser->group_id = $oGroup->team_id;
					$oUser->group_title = $oGroup->team_title;
				}
				if (false === $modelUsr->byUser($oApp, $oUser, ['fields' => 'id'])) {
					$users[] = $modelUsr->createOrUpdate($oApp, $oUser);
				}
			}
		}

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($oApp->siteid, $oUserP, $oApp, 'addUser', $oPosted);

		return new \ResponseData($users);
	}
	/**
	 *
	 */
	public function update_action($user) {
		if (false === ($oUserP = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$user = $this->escape($user);
		$modelUsr = $this->model('matter\plan\user');
		$oUser = $modelUsr->byId($user, ['fields' => 'id,aid,start_at']);
		if (false === $oUser) {
			return new \ObjectNotFoundError();
		}

		$oApp = $this->model('matter\plan')->byId($oUser->aid, ['fields' => 'id,siteid,title,summary']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$oPosted = $this->getPostJson();
		$aUpdated = [];
		if (isset($oPosted)) {
			foreach ($oPosted as $prop => $val) {
				switch ($prop) {
				case 'start_at':
					$aUpdated['start_at'] = $val;
					break;
				case 'comment':
					$aUpdated['comment'] = $modelUsr->escape($val);
					break;
				}
			}
			if (count($aUpdated)) {
				$modelUsr->update('xxt_plan_user', $aUpdated, ['id' => $oUser->id]);
			}
		}

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($oApp->siteid, $oUserP, $oApp, 'updateUser', $oPosted);

		return new \ResponseData($aUpdated);
	}
	/**
	 *
	 */
	public function list_action($app, $page = 1, $size = 30) {
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
		if (!empty($page) && !empty($size)) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}
		$users = $modelUsr->query_objs_ss($q, $q2);
		$oEntryRule = $oApp->entryRule;
		if (!empty($oEntryRule->scope->group) && $oEntryRule->scope->group === 'Y' && !empty($oEntryRule->group->id)) {
			$modelGrpTeam = $this->model('matter\group\team');
			foreach ($users as $oUser) {
				if (!empty($oUser->group_id)) {
					$oGroupRnd = $modelGrpTeam->byId($oUser->group_id, ['fields' => 'title']);
					$oUser->group_title = $oGroupRnd->title;
				}
			}
		}
		$oResult->users = $users;
		$q[0] = 'count(id)';
		$total = (int) $modelUsr->query_val_ss($q);
		$oResult->total = $total;

		return new \ResponseData($oResult);
	}
}