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
	/**
	 * 在任务中添加素材
	 */
	public function addMatter($user, $mpid, $missionId, $matter) {
		$relation = array(
			'mpid' => $mpid,
			'mission_id' => $missionId,
			'matter_id' => $matter->id,
			'matter_type' => $matter->type,
			'creater' => $user->id,
			'creater_name' => $user->name,
			'creater_src' => $user->src,
			'create_at' => time(),
		);
		$this->insert('xxt_mission_matter', $relation, false);

		return true;
	}
	/**
	 *
	 */
	public function removeMatter($mpid, $matterId, $matterType) {
		$rst = $this->delete(
			'xxt_mission_matter',
			"mpid='$mpid' and matter_id='$matterId' and matter_type='$matterType'"
		);
		return $rst;
	}
	/**
	 *
	 */
	public function &mattersById($mpid, $id, $options = array()) {
		$matters = array();
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = array(
			$fields,
			'xxt_mission_matter',
			"mpid='$mpid' and mission_id=$id",
		);
		$mms = $this->query_objs_ss($q);
		foreach ($mms as &$mm) {
			$matter = \TMS_APP::M('matter\\' . $mm->matter_type)->byId($mm->matter_id);
			$matter->type = $mm->matter_type;
			$matters[] = $matter;
		}

		return $matters;
	}
}