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
	public function byUser_action($app, $page = '', $size = '') {
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
			'id,nickname,userid,group_id,start_at,last_enroll_at',
			'xxt_plan_user',
			['aid' => $oApp->id],
		];
		switch ($oCriteria->orderby) {
			case 'score':
				$q[0] .= ',score';
				$q[2]['score'] = new \stdClass;
				$q[2]['score']->op = '>';
				$q[2]['score']->pat = 0;
				$q2 = ['o' => 'score desc,id'];
				break;
			case 'coin':
				$q[0] .= ',coin';
				$q[2]['coin'] = new \stdClass;
				$q[2]['coin']->op = '>';
				$q[2]['coin']->pat = 0;
				$q2 = ['o' => 'coin desc,id'];
				break;
			default:
				$q[0] .= ',task_num';
				$q[2]['task_num'] = new \stdClass;
				$q[2]['task_num']->op = '>';
				$q[2]['task_num']->pat = 0;
				$q2 = ['o' => 'task_num desc,id'];
				break;
		}

		if (!empty($page) && !empty($size)) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}
		$oUsers = $modelUsr->query_objs_ss($q, $q2);

		$data = new \stdClass;
		$data->users = $oUsers;
		$q[0] = "count(id)";
		$data->total = (int) $modelUsr->query_val_ss($q);

		return new \ResponseData($data);
	}
}