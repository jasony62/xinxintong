<?php
namespace pl\fe\matter\mission;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 项目控制器
 */
class phase extends \pl\fe\matter\base {
	/**
	 * 任务下的阶段
	 *
	 * @param string $site
	 * @param int $mission mission's id
	 */
	public function list_action($site, $mission) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$phase = $this->model('matter\mission\phase')->byMission($mission);

		return new \ResponseData($phase);
	}
	/**
	 *
	 *
	 * @param string $site
	 * @param int $mission mission's id
	 */
	public function create_action($site, $mission) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$proto = $this->getPostJson();
		$phaseId = uniqid();
		$current = time();

		$newPhase = array();
		$newPhase['siteid'] = $site;
		$newPhase['mission_id'] = $mission;
		$newPhase['phase_id'] = $phaseId;
		$newPhase['title'] = isset($proto->title) ? $proto->title : '新阶段';
		$newPhase['start_at'] = isset($proto->start_at) ? $proto->start_at : $current;
		$newPhase['end_at'] = isset($proto->end_at) ? $proto->end_at : $current + 86400;

		$newPhase['id'] = $this->model()->insert('xxt_mission_phase', $newPhase, true);

		/*更新项目状态*/
		$this->model()->update(
			'xxt_mission',
			array('multi_phase' => 'Y'),
			"siteid='$site' and id='$mission'"
		);

		return new \ResponseData($newPhase);
	}
	/**
	 *
	 *
	 * @param string $site
	 * @param int $mission mission's id
	 */
	public function update_action($site, $mission, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$data = $this->getPostJson();

		$rst = $this->model()->update(
			'xxt_mission_phase',
			$data, "siteid='$site' and phase_id='$id'"
		);

		return new \ResponseData($rst);
	}
	/**
	 *
	 *
	 * @param string $site
	 * @param int $mission mission's id
	 */
	public function remove_action($site, $mission, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model()->delete(
			'xxt_mission_phase',
			"siteid='$site' and phase_id='$id'"
		);

		return new \ResponseData($rst);
	}
}