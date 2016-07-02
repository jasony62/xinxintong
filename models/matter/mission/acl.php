<?php
namespace matter\mission;
/**
 *
 */
class acl_model extends \TMS_MODEL {
	/**
	 * 任务的访问控制列表
	 *
	 * @param int $missionId
	 * @param object $user
	 * @param array $options
	 */
	public function &byMission($missionId, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$excludeOwner = isset($options['excludeOwner']) ? $options['excludeOwner'] : 'N';
		$q = [
			$fields,
			'xxt_mission_acl',
			"mission_id='$missionId'",
		];
		if ($excludeOwner === 'Y') {
			$q[2] .= " and creater<>coworker";
		}

		$q2 = ['o' => 'invite_at desc'];

		$acls = $this->query_objs_ss($q, $q2);

		return $acls;
	}
	/**
	 *
	 */
	public function &byCoworker($missionId, $coworkerId, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = [
			$fields,
			'xxt_mission_acl',
			"mission_id='$missionId' and coworker='$coworkerId'",
		];

		$acl = $this->query_obj_ss($q);

		return $acl;
	}
	/**
	 *
	 */
	public function &add(&$inviter, &$mission, &$coworker) {
		$current = time();
		$acl = new \stdClass;
		$acl->siteid = $mission->siteid;
		$acl->mission_id = $mission->id;
		$acl->title = $mission->title;
		$acl->summary = $mission->summary;
		$acl->pic = $mission->pic;
		$acl->creater = $mission->creater;
		$acl->create_at = $mission->create_at;
		$acl->inviter = $inviter->id;
		$acl->inviter_label = $inviter->name;
		$acl->invite_at = $current;
		$acl->coworker = $coworker->id;
		$acl->coworker_label = $coworker->name;
		$acl->join_at = $current;
		$acl->state = $mission->state;

		$acl->id = $this->insert('xxt_mission_acl', $acl, true);

		return $acl;
	}
	/**
	 *
	 */
	public function updateMission($mission) {
		$acl = new \stdClass;
		$acl->title = $mission->title;
		$acl->summary = $mission->summary;
		$acl->pic = $mission->pic;

		$rst = $this->update(
			'xxt_mission_acl',
			$acl,
			"mission_id='{$mission->id}'"
		);

		return $rst;
	}
	/**
	 *
	 */
	public function removeCoworker(&$mission, &$coworker) {
		$rst = $this->delete(
			'xxt_mission_acl',
			"mission_id='{$mission->id}' and coworker='{$coworker->uid}'"
		);

		return $rst;
	}
}