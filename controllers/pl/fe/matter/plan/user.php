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
	 *
	 */
	public function list_action($app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\plan');
		$app = $modelApp->escape($app);

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