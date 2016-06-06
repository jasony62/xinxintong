<?php
namespace matter\mission;
/**
 *
 */
class phase_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function &byMission($siteId, $missionId, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = array(
			$fields,
			'xxt_mission_phase',
			"siteid='$siteId' and mission_id='$missionId'",
		);
		$q2 = array(
			'o' => 'start_at',
		);
		$phases = $this->query_objs_ss($q, $q2);

		return $phases;
	}
}