<?php
namespace matter\mission;
/**
 *
 */
class phase_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function &byMission($missionId, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = array(
			$fields,
			'xxt_mission_phase',
			"mission_id='$missionId'",
		);
		$q2 = array(
			'o' => 'start_at',
		);
		$phases = $this->query_objs_ss($q, $q2);

		return $phases;
	}
}