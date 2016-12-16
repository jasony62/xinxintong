<?php
namespace pl\fe\site\user;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/*
 * 投稿活动主控制器
 */
class coin extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/user');
		exit;
	}
	/**
	 *
	 */
	public function get_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelRule = $this->model('site\coin\rule');

		$filter = 'ID:' . $site;

		$rules = $modelRule->byMatterFilter($filter);

		return new \ResponseData($rules);
	}
	/**
	 *
	 */
	public function logs_action($site, $page = 1, $size = 30) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelLog = $this->model('site\coin\log');

		$logs = $modelLog->bySite($site, $page, $size);

		return new \ResponseData($logs);
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