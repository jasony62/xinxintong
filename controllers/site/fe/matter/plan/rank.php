<?php
namespace site\fe\matter\plan;

include_once dirname(__FILE__) . '/base.php';
/**
 * 计划活动
 */
class rank extends base {
	/**
	 *
	 */
	public function byUser_action($app) {
		$modelApp = $this->model('matter\plan');
		$app = $modelApp->escape($app);

		$oApp = $modelApp->byId($app, ['fields' => 'id,state,siteid,entry_rule']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$aResult = $this->checkEntryRule($oApp);
		if (false === $aResult[0]) {
			return new \ResponseError($aResult[1]);
		}

		$oCriteria = $this->getPostJson();
		if (empty($oCriteria->orderby)) {
			$oCriteria = new \stdClass;
			$oCriteria->orderby = 'task_num';
		}
		$modelUsr = $this->model('matter\plan\user');
		$q = [
			'id,nickname,userid,group_id,start_at,last_enroll_at,task_num,score,coin',
			'xxt_plan_user',
			['aid' => $oApp->id],
		];
		switch ($oCriteria->orderby) {
			case 'score':
				$q2 = ['o' => 'score desc'];
				break;
			case 'coin':
				$q2 = ['o' => 'coin desc'];
				break;
			default:
				$q2 = ['o' => 'task_num desc'];
				break;
		}

		$oUsers = $modelUsr->query_objs_ss($q, $q2);

		return new \ResponseData($oUsers);
	}
}