<?php
namespace pl\fe\matter\plan;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 记录活动积分管理控制器
 */
class coin extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action($id) {

		\TPL::output('/pl/fe/matter/plan/frame');

		exit;
	}
	/**
	 *
	 */
	public function rules_action($app) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}
		$modelPlan = $this->model('matter\plan');
		if (false === ($oApp = $modelPlan->byId($app, ['fields' => 'id,siteid']))) {
			return new \ResponseError();
		}

		$modelRule = $this->model('site\coin\rule');

		$filter = 'ID:' . $oApp->id;
		$rules = $modelRule->byMatterFilter($filter, ['fields' => 'id,act,actor_delta,actor_overlap,matter_type']);

		return new \ResponseData($rules);
	}
	/**
	 *
	 */
	public function saveRules_action($app) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}
		$modelPlan = $this->model('matter\plan');
		if (false === ($oApp = $modelPlan->byId($app, ['fields' => 'id,siteid']))) {
			return new \ResponseError();
		}

		$newRules = [];
		$rules = $this->getPostJson();

		foreach ($rules as $rule) {
			if (empty($rule->id)) {
				if ($rule->actor_delta != 0) {
					$rule->siteid = $oApp->siteid;
					$rule->matter_type = 'plan';
					$rule->matter_filter = 'ID:' . $oApp->id;
					$id = $modelPlan->insert('xxt_coin_rule', $rule, true);
					$newRules[$rule->act] = $id;
				}
			} else {
				$modelPlan->update(
					'xxt_coin_rule',
					[
						'actor_delta' => $rule->actor_delta,
						'actor_overlap' => $rule->actor_overlap,
					],
					['id' => $rule->id]
				);
			}
		}

		return new \ResponseData($newRules);
	}
	/**
	 *
	 */
	public function logs_action($site, $app, $page = 1, $size = 30) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$result = new \stdClass;
		$model = $this->model();
		$app = $model->escape($app);
		$q = [
			'cl.act,cl.occur_at,cl.userid,cl.nickname,cl.delta,cl.total,p.coin',
			'xxt_coin_log cl,xxt_plan_user p',
			"cl.matter_type='plan' and cl.matter_id='{$app}' and p.aid = cl.matter_id and p.userid = cl.userid",
		];
		/**
		 * 分页数据
		 */
		$q2 = [
			'o' => 'cl.occur_at desc,cl.id desc',
			'r' => [
				'o' => (($page - 1) * $size),
				'l' => $size,
			],
		];

		$result->logs = $model->query_objs_ss($q, $q2);

		$q[0] = 'count(*)';
		$result->total = $model->query_val_ss($q);

		return new \ResponseData($result);
	}
}