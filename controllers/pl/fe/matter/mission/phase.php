<?php
namespace pl\fe\matter\mission;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 项目控制器
 */
class phase extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/mission/frame');
		exit;
	}
	/**
	 * 任务下的阶段
	 *
	 * @param string $site
	 * @param int $mission mission's id
	 */
	public function list_action($mission) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$phases = $this->model('matter\mission\phase')->byMission($mission);

		return new \ResponseData($phases);
	}
	/**
	 * 增加项目阶段
	 *
	 * @param string $site
	 * @param int $mission mission's id
	 */
	public function create_action($mission) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelMis = $this->model('matter\mission');
		$mission = $modelMis->byId($mission, 'id,siteid,title,summary,pic');

		$proto = $this->getPostJson();
		$phaseId = uniqid();
		$current = time();

		$newPhase = array();
		$newPhase['siteid'] = $mission->siteid;
		$newPhase['mission_id'] = $mission->id;
		$newPhase['phase_id'] = $phaseId;
		$newPhase['title'] = isset($proto->title) ? $proto->title : '新阶段';
		$newPhase['start_at'] = isset($proto->start_at) ? $proto->start_at : $current;
		$newPhase['end_at'] = isset($proto->end_at) ? $proto->end_at : $current + 86400;

		$newPhase['id'] = $this->model()->insert('xxt_mission_phase', $newPhase, true);

		/*更新项目状态*/
		$this->model()->update(
			'xxt_mission',
			['multi_phase' => 'Y'],
			"id='{$mission->id}'"
		);

		return new \ResponseData($newPhase);
	}
	/**
	 *
	 *
	 * @param int $mission mission's id
	 * @param int id phase's id
	 */
	public function update_action($mission, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$data = $this->getPostJson();

		$rst = $this->model()->update(
			'xxt_mission_phase',
			$data,
			["phase_id" => $id]
		);

		return new \ResponseData($rst);
	}
	/**
	 * 删除项目的阶段
	 *
	 * @param int $mission mission's id
	 * @param int id phase's id
	 */
	public function remove_action($mission, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();

		$rst = $model->delete(
			'xxt_mission_phase',
			["phase_id" => $id]
		);

		/* 是否还有项目阶段 */
		$count = (int) $model->query_val_ss([
			'count(*)',
			'xxt_mission_phase',
			["id" => $mission],
		]);
		if ($count === 0) {
			$model->update(
				'xxt_mission',
				['multi_phase' => 'N'],
				["id" => $mission]
			);
		}

		return new \ResponseData($rst);
	}
}