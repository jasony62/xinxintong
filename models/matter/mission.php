<?php
namespace matter;

require_once dirname(__FILE__) . '/app_base.php';
/**
 *
 */
class mission_model extends app_base {
	/**
	 *
	 */
	protected function table() {
		return 'xxt_mission';
	}
	/**
	 *
	 */
	public function getTypeName() {
		return 'mission';
	}
	/**
	 *
	 */
	public function &byId($id, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : '';
		$q = [
			$fields,
			$this->table(),
			["id" => $id],
		];
		if (($mission = $this->query_obj_ss($q))) {
			$mission->type = 'mission';
			if (!empty($cascaded)) {
				$cascaded = explode(',', $cascaded);
				$modelCode = \TMS_APP::M('code\page');
				foreach ($cascaded as $field) {
					if ($field === 'header_page_name' && $mission->header_page_name) {
						$mission->header_page = $modelCode->lastPublishedByName($mission->siteid, $mission->header_page_name, array('fields' => 'id,html,css,js'));
					} else if ($field === 'footer_page_name' && $mission->footer_page_name) {
						$mission->footer_page = $modelCode->lastPublishedByName($mission->siteid, $mission->footer_page_name, array('fields' => 'id,html,css,js'));
					} else if ($field === 'phase') {
						$mission->phases = \TMS_APP::M('matter\mission\phase')->byMission($id);
					}
				}
			}
		}

		return $mission;
	}
	/**
	 *
	 */
	public function &bySite($siteId, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$limit = isset($options['limit']) ? $options['limit'] : (object) ['page' => 1, 'size' => 20];

		$q = [
			$fields,
			'xxt_mission',
			"siteid='$siteId' and state=1",
		];
		$q2 = [
			'o' => 'modify_at desc',
		];
		if ($limit) {
			$q2['r'] = ['o' => ($limit->page - 1) * $limit->size, 'l' => $limit->size];
		}

		if ($missions = $this->query_objs_ss($q, $q2)) {
			$q[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q);
			$result = ['missions' => $missions, 'total' => $total];
		} else {
			$result = ['missions' => $missions, 'total' => 0];
		}

		return $result;
	}
	/**
	 * 根据用户和访问控制列表返回任务
	 *
	 * @param object $user
	 */
	public function &byAcl(&$user, $options = array()) {
		$fields = 'mission.*,site.name site_name';
		$limit = isset($options['limit']) ? $options['limit'] : (object) array('page' => 1, 'size' => 20);
		$q = [
			$fields,
			'xxt_mission_acl mission,xxt_site site',
			"mission.coworker='{$user->id}' and mission.state=1 and mission.last_invite='Y' and mission.siteid=site.id",
		];
		if (isset($options['bySite'])) {
			$q[2] .= " and mission.siteid='{$options['bySite']}'";
		}
		if (isset($options['byTitle'])) {
			$q[2] .= " and mission.title like '%{$options['byTitle']}%'";
		}
		$q2 = [
			'o' => 'mission.invite_at desc',
			'r' => ['o' => ($limit->page - 1) * $limit->size, 'l' => $limit->size],
		];

		if ($missions = $this->query_objs_ss($q, $q2)) {
			$q[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q);
			$result = ['missions' => $missions, 'total' => $total];
		} else {
			$result = ['missions' => [], 'total' => 0];
		}

		return $result;
	}
	/**
	 * 获得用户参与的项目所属的团队
	 */
	public function siteByAcl(&$user) {
		$q = [
			'id,name,creater,creater_name,create_at',
			'xxt_site site',
			"exists(select 1 from xxt_mission_acl mission where site.id=mission.siteid and mission.coworker='{$user->id}' and mission.state=1 and mission.last_invite='Y')",
		];
		$q2 = [
			'o' => 'site.name',
		];
		if ($sites = $this->query_objs_ss($q, $q2)) {
			$q[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q);
			$result = ['sites' => $sites, 'total' => $total];
		} else {
			$result = ['sites' => [], 'total' => 0];
		}

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
	 * 从项目中删除素材
	 */
	public function removeMatter($matterId, $matterType) {
		$rst = $this->delete(
			'xxt_mission_matter',
			"matter_id='$matterId' and matter_type='$matterType'"
		);

		return $rst;
	}
	/**
	 *
	 */
	public function &mattersById($id, $matterType = null, $options = array()) {
		$matters = [];
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_mission_matter',
			["mission_id" => $id],
		];
		!empty($matterType) && $q[2]['matter_type'] = $matterType;
		$q2 = ['o' => 'create_at desc'];
		$mms = $this->query_objs_ss($q, $q2);

		foreach ($mms as &$mm) {
			if ($matter = \TMS_APP::M('matter\\' . $mm->matter_type)->byId($mm->matter_id)) {
				$matter->type = $mm->matter_type;
				$matters[] = $matter;
			}
		}

		return $matters;
	}
	/**
	 *
	 */
	public function &byMatter($siteId, $matterId, $matterType, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = array(
			$fields,
			'xxt_mission m',
			"exists(select 1 from xxt_mission_matter mm where m.id=mm.mission_id and siteid='$siteId' and matter_id='$matterId' and matter_type='$matterType')",
		);

		$mission = $this->query_obj_ss($q);

		return $mission;
	}
}