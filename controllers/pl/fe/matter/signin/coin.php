<?php
namespace pl\fe\matter\signin;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 *
 */
class coin extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/signin/frame');
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
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oResult = new \stdClass;
		$model = $this->model();
		$app = $model->escape($app);
		$q = [
			'act,occur_at,userid,nickname,delta,total',
			'xxt_coin_log',
			['matter_type' => 'signin', 'matter_id' => $app],
		];
		/**
		 * 分页数据
		 */
		$q2 = [
			'o' => 'occur_at desc,id desc',
			'r' => [
				'o' => (($page - 1) * $size),
				'l' => $size,
			],
		];

		$oResult->logs = $model->query_objs_ss($q, $q2);

		$q[0] = 'count(*)';
		$oResult->total = $model->query_val_ss($q);

		return new \ResponseData($oResult);
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