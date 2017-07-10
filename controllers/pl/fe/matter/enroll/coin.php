<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记活动积分管理控制器
 */
class coin extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 *
	 */
	public function rules_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelRule = $this->model('site\coin\rule');

		$filter = 'ID:' . $app;

		$rules = $modelRule->byMatterFilter($filter);

		return new \ResponseData($rules);
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
			'cl.act,cl.occur_at,cl.userid,cl.nickname,cl.delta,cl.total,e.user_total_coin',
			'xxt_coin_log cl,xxt_enroll_user e',
			"cl.matter_type='enroll' and cl.matter_id='{$app}' and e.aid = cl.matter_id and e.userid = cl.userid and e.rid = 'ALL'",
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
	/**
	 *
	 */
	public function saveRules_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$newRules = [];
		$model = $this->model();
		$rules = $this->getPostJson();

		foreach ($rules as $rule) {
			if (empty($rule->id) && ($rule->actor_delta != 0)) {
				$rule->siteid = $site;
				$id = $model->insert('xxt_coin_rule', $rule, true);
				$newRules[$rule->act] = $id;
			} else {
				$model->update(
					'xxt_coin_rule',
					[
						'actor_delta' => $rule->actor_delta,
					],
					"id=$rule->id"
				);
			}
		}

		return new \ResponseData($newRules);
	}
}