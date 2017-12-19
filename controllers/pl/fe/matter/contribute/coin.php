<?php
namespace pl\fe\matter\contribute;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 投稿活动主控制器
 */
class coin extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action($id) {
		$access = $this->accessControlUser('contribute', $id);
		if ($access[0] === false) {
			die($access[1]);
		}

		\TPL::output('/pl/fe/matter/contribute/frame');
		exit;
	}
	/**
	 *
	 */
	public function get_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelRule = $this->model('site\coin\rule');

		$filter = 'ENTRY:contribute,' . $app;

		$rules = $modelRule->byMatterFilter($filter);

		return new \ResponseData($rules);
	}
	/**
	 *
	 */
	public function save_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$newRules = [];
		$model = $this->model();
		$rules = $this->getPostJson();

		foreach ($rules as $rule) {
			if (empty($rule->id) && ($rule->actor_delta != 0 || $rule->creator_delta != 0)) {
				$rule->siteid = $site;
				$id = $model->insert('xxt_coin_rule', $rule, true);
				$newRules[$rule->act] = $id;
			} else {
				$model->update(
					'xxt_coin_rule',
					[
						'actor_delta' => $rule->actor_delta,
						'creator_delta' => $rule->creator_delta,
					],
					"id=$rule->id"
				);
			}
		}

		return new \ResponseData($newRules);
	}
}