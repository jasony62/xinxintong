<?php
namespace pl\fe\matter\mission;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 记录活动积分管理控制器
 */
class coin extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/mission/frame');
		exit;
	}
	/**
	 *
	 */
	public function saveRules_action($mission) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$modelMis = $this->model('matter\mission');
		$oMission = $modelMis->byId($mission);
		if ($oMission === false) {
			return new \ObjectNotFoundError();
		}

		$newRules = [];
		$model = $this->model();
		$rules = $this->getPostJson();

		foreach ($rules as $rule) {
			if (empty($rule->id)) {
				if ($rule->actor_delta != 0) {
					$rule->siteid = $oMission->siteid;
					$rule->matter_filter = 'MISSION:' . $oMission->id;
					$id = $model->insert('xxt_coin_rule', $rule, true);
					$newRules[$rule->act] = $id;
				}
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
	/**
	 *
	 */
	public function rules_action($mission) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$modelMis = $this->model('matter\mission');
		$oMission = $modelMis->byId($mission);
		if ($oMission === false) {
			return new \ObjectNotFoundError();
		}

		$modelRule = $this->model('site\coin\rule');
		$filter = 'MISSION:' . $oMission->id;
		$rules = $modelRule->byMatterFilter($filter, ['fields' => 'id,act,actor_delta,actor_overlap,matter_type']);

		return new \ResponseData($rules);
	}
	/**
	 *
	 */
	public function logs_action($mission, $page = 1, $size = 30) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$result = new \stdClass;

		return new \ResponseData($result);
	}
}