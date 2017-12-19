<?php
namespace pl\fe\matter\article;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记活动积分管理控制器
 */
class coin extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action($id) {
		$access = $this->accessControlUser('article', $id);
		if ($access[0] === false) {
			die($access[1]);
		}

		\TPL::output('/pl/fe/matter/article/frame');
		exit;
	}
	/**
	 *
	 */
	public function rules_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelRule = $this->model('site\coin\rule');

		$filter = 'ID:' . $id;

		$rules = $modelRule->byMatterFilter($filter);

		return new \ResponseData($rules);
	}
	/**
	 *
	 */
	public function logs_action($site, $id, $page = 1, $size = 30) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelLog = $this->model('site\coin\log');
		$matter = new \stdClass;
		$matter->id = $id;
		$matter->type = 'article';

		$logs = $modelLog->byMatter($matter, $page, $size);

		return new \ResponseData($logs);
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