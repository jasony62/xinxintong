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
		$excludeAdmin = isset($options['excludeAdmin']) ? $options['excludeAdmin'] : 'N';

		$missionId = $this->escape($missionId);
		$q = [
			$fields,
			'xxt_mission_acl',
			"mission_id='$missionId' and last_invite = 'Y'",
		];
		if ($excludeOwner === 'Y') {
			$q[2] .= " and coworker_role<>'O'";
		}
		if ($excludeAdmin === 'Y') {
			$q[2] .= " and coworker_role<>'A'";
		}

		$q2 = ['o' => 'invite_at desc'];

		$acls = $this->query_objs_ss($q, $q2);

		return $acls;
	}
	/**
	 * 用户邀请过的合作者
	 *
	 * @param int $missionId
	 * @param object $user
	 * @param array $options
	 */
	public function &byUser(&$user, $missionId, $options) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$missionId = $this->escape($missionId);
		$q = [
			$fields,
			'xxt_mission_acl',
			"inviter='{$user->id}' and coworker_role='C' and mission_id<>{$missionId} and last_invite='Y'",
		];
		$q2 = ['o' => 'invite_at desc'];

		$acls = $this->query_objs_ss($q, $q2);

		return $acls;
	}
	/**
	 * 指定项目的对指定用户的记录
	 */
	public function &byCoworker($missionId, $coworkerId, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = [
			$fields,
			'xxt_mission_acl',
			["mission_id" => $missionId, "coworker" => $coworkerId, "last_invite" => 'Y', "state" => 1],
		];

		$acl = $this->query_obj_ss($q);

		return $acl;
	}
	/**
	 * 给项目添加合作者
	 *
	 * @param object $inviter
	 * @param object $mission
	 * @param object $coworker
	 * @param string $role Coworker:合作者，Owner：项目创建人，Admin：站点管理员
	 */
	public function &add(&$inviter, &$mission, &$coworker, $role = 'C') {
		/* 检查邀请人是否邀请过合作人 */
		$q = ['id', 'xxt_mission_acl', ['mission_id' => $mission->id, 'coworker' => $coworker->id, 'last_invite' => 'Y']];
		if ($log = $this->query_obj_ss($q)) {
			$this->update('xxt_mission_acl', ['last_invite' => 'N'], ['id' => $log->id]);
		}
		/* 新建邀请记录 */
		$current = time();
		$acl = new \stdClass;
		$acl->siteid = $mission->siteid;
		$acl->mission_id = $mission->id;
		$acl->title = $this->escape($mission->title);
		$acl->summary = $this->escape($mission->summary);
		$acl->pic = $mission->pic;
		$acl->creater = $mission->creater;
		$acl->create_at = $mission->create_at;
		$acl->creater_name = $this->escape($mission->creater_name);
		$acl->inviter = $inviter->id;
		$acl->inviter_label = $this->escape($inviter->name);
		$acl->invite_at = $current;
		$acl->coworker = $coworker->id;
		$acl->coworker_label = $this->escape($coworker->label);
		$acl->coworker_role = $role;
		$acl->join_at = $current;
		$acl->last_invite = 'Y';
		$acl->state = $mission->state;

		$acl->id = $this->insert('xxt_mission_acl', $acl, true);

		return $acl;
	}
	/**
	 * 给站点的管理员添加权限
	 */
	public function addSiteAdmin($siteId, &$inviter, $coworkers = null, $missions = null) {
		if ($coworkers === null) {
			$coworkers = [];
			$modelAdm = \TMS_APP::M('site\admin');
			$admins = $modelAdm->bySite($siteId);
			foreach ($admins as $admin) {
				$coworker = new \stdClass;
				$coworker->id = $admin->uid;
				$coworker->label = $admin->ulabel;
				$coworkers[] = $coworker;
			}
		} else {
			is_object($coworkers) && $coworkers = [$coworkers];
		}

		if ($missions === null) {
			$modelMis = \TMS_APP::M('matter\mission');
			$missions = $modelMis->bySite($siteId, ['limit' => false]);
			$missions = $missions['missions'];
		} else {
			is_object($missions) && $missions = [$missions];
		}
		/*加入ACL*/
		foreach ($coworkers as $coworker) {
			foreach ($missions as $mission) {
				if ($coworker->id !== $inviter->id) {
					$this->add($inviter, $mission, $coworker, 'A');
				}
			}
		}

		return true;
	}
	/**
	 *
	 */
	public function updateMission($mission) {
		$acl = new \stdClass;
		$acl->title = $this->escape($mission->title);
		$acl->summary = $this->escape($mission->summary);
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
			"mission_id='{$mission->id}' and coworker='{$coworker->id}'"
		);

		return $rst;
	}
	/**
	 *
	 */
	public function removeMission(&$mission) {
		$rst = $this->delete(
			'xxt_mission_acl',
			"mission_id='{$mission->id}'"
		);

		return $rst;
	}
	/**
	 * 删除站点管理员
	 */
	public function removeSiteAdmin($siteId, $coworker) {
		$rst = $this->delete(
			'xxt_mission_acl',
			"siteid='{$siteId}' and coworker='{$coworker->id}' and coworker_role='A'"
		);

		return $rst;
	}
}