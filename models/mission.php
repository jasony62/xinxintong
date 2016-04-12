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
	public function &bySite($siteId, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = array(
			$fields,
			'xxt_mission',
			"siteid='$siteId'",
		);
		$q2 = array('o' => 'modify_at desc');

		$missions = $this->query_objs_ss($q, $q2);

		$result = array('missions' => $missions);

		return $result;
	}
	/**
	 * 在任务中添加素材
	 */
	public function addMatter($user, $siteId, $missionId, $matter) {
		$relation = array(
			'siteid' => $siteId,
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
	public function removeMatter($siteId, $matterId, $matterType) {
		$rst = $this->delete(
			'xxt_mission_matter',
			"siteid='$siteId' and matter_id='$matterId' and matter_type='$matterType'"
		);
		return $rst;
	}
	/**
	 *
	 */
	public function &mattersById($siteId, $id, $options = array()) {
		$matters = array();
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = array(
			$fields,
			'xxt_mission_matter',
			"siteid='$siteId' and mission_id=$id",
		);
		$q2 = array('o' => 'create_at desc');
		$mms = $this->query_objs_ss($q, $q2);
		foreach ($mms as &$mm) {
			$matter = \TMS_APP::M('matter\\' . $mm->matter_type)->byId($mm->matter_id);
			$matter->type = $mm->matter_type;
			$matters[] = $matter;
		}

		return $matters;
	}
}