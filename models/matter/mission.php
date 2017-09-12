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
	 * 获得访问入口url
	 */
	public function getEntryUrl($siteId, $id) {
		$url = "http://" . APP_HTTP_HOST;
		$url .= "/rest/site/fe/matter/mission";
		if ($siteId === 'platform') {
			if ($oMission = $this->byId($id, ['cascaded' => 'N'])) {
				$url .= "?site={$oMission->siteid}&mission=" . $id;
			} else {
				$url = "http://" . APP_HTTP_HOST;
			}
		} else {
			$url .= "?site={$siteId}&mission=" . $id;
		}

		return $url;
	}
	/**
	 * 获得访问入口url
	 */
	public function getOpUrl($siteId, $id) {
		$url = "http://" . APP_HTTP_HOST;
		$url .= "/rest/site/op/matter/mission";
		$url .= "?site={$siteId}&mission=" . $id;

		return $url;
	}
	/**
	 * 获得项目定义
	 */
	public function &byId($id, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : '';
		$q = [
			$fields,
			$this->table(),
			["id" => $id],
		];
		if (($oMission = $this->query_obj_ss($q))) {
			$oMission->type = 'mission';
			if (!empty($oMission->matter_mg_tag)) {
				$oMission->matter_mg_tag = json_decode($oMission->matter_mg_tag);
			}
			if ($fields === '*' || false !== strpos($fields, 'entry_rule')) {
				if (empty($oMission->entry_rule)) {
					$oMission->entry_rule = new \stdClass;
					$oMission->entry_rule->scope = 'none';
				} else {
					$oMission->entry_rule = json_decode($oMission->entry_rule);
				}
			}
			if (isset($oMission->siteid) && isset($oMission->id)) {
				$oMission->entryUrl = $this->getEntryUrl($oMission->siteid, $oMission->id);
				$oMission->opUrl = $this->getOpUrl($oMission->siteid, $oMission->id);
			}
			if (!empty($cascaded)) {
				$cascaded = explode(',', $cascaded);
				$modelCode = $this->model('code\page');
				foreach ($cascaded as $field) {
					if ($field === 'header_page_name' && isset($oMission->header_page_name) && isset($oMission->siteid)) {
						$oMission->header_page = $modelCode->lastPublishedByName($oMission->siteid, $oMission->header_page_name, ['fields' => 'id,html,css,js']);
					} else if ($field === 'footer_page_name' && isset($oMission->footer_page_name) && isset($oMission->siteid)) {
						$oMission->footer_page = $modelCode->lastPublishedByName($oMission->siteid, $oMission->footer_page_name, ['fields' => 'id,html,css,js']);
					} else if ($field === 'phase') {
						$oMission->phases = $this->model('matter\mission\phase')->byMission($id);
					}
				}
			}
		}

		return $oMission;
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
		if (isset($options['byTitle'])) {
			$q[2] .= " and title like '%{$options['byTitle']}%'";
		}

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
			'xxt_mission_acl mission,xxt_site site,xxt_mission m',
			"mission.coworker='{$user->id}' and mission.state=1 and mission.last_invite='Y' and mission.siteid=site.id and mission.mission_id = m.id and m.state=1",
		];
		if (isset($options['bySite'])) {
			$q[2] .= " and mission.siteid='{$options['bySite']}'";
		}
		if (isset($options['byTitle'])) {
			$q[2] .= " and mission.title like '%{$options['byTitle']}%'";
		}
		if (!empty($options['byTags'])) {
			foreach ($options['byTags'] as $tag) {
				$q[2] .= " and m.matter_mg_tag like '%" . $this->escape($tag->id) . "%'";
			}
		}
		$q2 = [
			'o' => 'mission.invite_at desc',
			'r' => ['o' => ($limit->page - 1) * $limit->size, 'l' => $limit->size],
		];

		if ($missions = $this->query_objs_ss($q, $q2)) {
			/* 项目下活动的数量 */
			foreach ($missions as &$oMission) {
				$qMatterNum = ['select matter_type,count(*) matter_num from xxt_mission_matter where mission_id=' . $oMission->mission_id . ' group by matter_type'];
				$matterNums = $this->query_objs($qMatterNum);
				$oMatterNums = new \stdClass;
				$oMatterNums->num = 0;
				foreach ($matterNums as $oMn) {
					$oMatterNums->{$oMn->matter_type} = (int) $oMn->matter_num;
					if (!in_array($oMn->matter_type, ['article', 'news', 'channel', 'link'])) {
						$oMatterNums->num += $oMatterNums->{$oMn->matter_type};
					}
				}
				$oMission->matter = $oMatterNums;
			}
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
			'phase_id' => isset($matter->mission_phase_id) ? $matter->mission_phase_id : '',
			'matter_id' => $matter->id,
			'matter_type' => $matter->type,
			'matter_title' => $this->escape($matter->title),
			'scenario' => isset($matter->scenario) ? $matter->scenario : '',
			'start_at' => isset($matter->start_at) ? $matter->start_at : 0,
			'end_at' => isset($matter->end_at) ? $matter->end_at : 0,
			'creater' => $user->id,
			'creater_name' => $this->escape($user->name),
			'creater_src' => $user->src,
			'create_at' => time(),
		);
		$this->insert('xxt_mission_matter', $relation, false);

		return true;
	}
	/**
	 * 更新项目中的素材信息
	 */
	public function updateMatter($missionId, $matter) {

		$relation = [
			'matter_title' => $this->escape($matter->title),
			'phase_id' => isset($matter->mission_phase_id) ? $matter->mission_phase_id : '',
			'scenario' => isset($matter->scenario) ? $matter->scenario : '',
			'start_at' => isset($matter->start_at) ? $matter->start_at : 0,
			'end_at' => isset($matter->end_at) ? $matter->end_at : 0,
		];
		$rst = $this->update(
			'xxt_mission_matter',
			$relation,
			['mission_id' => $missionId, 'matter_id' => $matter->id, 'matter_type' => $matter->type]
		);

		return $rst;
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