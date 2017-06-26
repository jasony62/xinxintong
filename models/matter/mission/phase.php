<?php
namespace matter\mission;
/**
 *
 */
class phase_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function &byMission($missionId, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = [
			$fields,
			'xxt_mission_phase',
			"mission_id='$missionId'",
		];
		$q2 = [
			'o' => 'start_at',
		];
		$phases = $this->query_objs_ss($q, $q2);

		return $phases;
	}
}