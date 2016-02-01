<?php
class mission_model extends TMS_MODEL {
	/**
	 *
	 *
	 * $param string $id
	 *
	 * return
	 */
	public function byId($id, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = array(
			$fields,
			'xxt_mission',
			"id='$id'",
		);
		$mission = $this->query_obj_ss($q);

		return $mission;
	}
	/**
	 *
	 */
	public function &byMpid($mpid, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = array(
			$fields,
			'xxt_mission',
			"mpid='$mpid'",
		);
		$q2 = array('o' => 'modify_at desc');

		$missions = $this->query_objs_ss($q, $q2);

		$result = array('missions' => $missions);

		return $result;
	}
}