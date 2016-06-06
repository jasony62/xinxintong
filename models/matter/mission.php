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
	public function &byId($id, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : '';
		$q = array(
			$fields,
			$this->table(),
			"id='$id'",
		);
		if (($mission = $this->query_obj_ss($q)) && !empty($cascaded)) {
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

		return $mission;
	}
	/**
	 *
	 */
	public function &bySite($siteId, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$limit = isset($options['limit']) ? $options['limit'] : (object) array('page' => 1, 'size' => 20);
		$q = array(
			$fields,
			'xxt_mission',
			"siteid='$siteId'",
		);
		$q2 = array(
			'o' => 'modify_at desc',
			'r' => array('o' => ($limit->page - 1) * $limit->size, 'l' => $limit->size),
		);

		if ($missions = $this->query_objs_ss($q, $q2)) {
			$q[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q);
			$result = array('missions' => $missions, 'total' => $total);
		} else {
			$result = array('missions' => $missions, 'total' => 0);
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