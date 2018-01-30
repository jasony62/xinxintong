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
	 * @param object $oUser
	 */
	public function &byAcl($oUser, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
		$limit = isset($aOptions['limit']) ? $aOptions['limit'] : (object) ['page' => 1, 'size' => 20];
		$q = [
			$fields,
			'xxt_mission m',
			"m.state=1",
		];
		if (isset($aOptions['bySite'])) {
			$bySite = $aOptions['bySite'];
			if ($bySite === '_coworker') {
				$q[2] .= " and exists(select 1 from xxt_mission_acl a where a.coworker='{$oUser->id}' and coworker_role='C' and a.last_invite='Y' and a.mission_id=m.id)";
			} else {
				$q[2] .= " and m.siteid='{$bySite}'";
				$q[2] .= " and exists(select 1 from xxt_mission_acl a where a.coworker='{$oUser->id}' and a.last_invite='Y' and a.mission_id=m.id)";
			}
		}
		if (isset($aOptions['byTitle'])) {
			$q[2] .= " and m.title like '%{$aOptions['byTitle']}%'";
		}
		if (isset($aOptions['byStar']) && $aOptions['byStar'] === 'Y') {
			$q[2] .= " and exists(select 1 from xxt_account_topmatter t where t.matter_type='mission' and t.matter_id=m.id and userid='{$oUser->id}')";
		}
		if (!empty($aOptions['byTags'])) {
			foreach ($aOptions['byTags'] as $tag) {
				$q[2] .= " and m.matter_mg_tag like '%" . $this->escape($tag->id) . "%'";
			}
		}
		$q2 = [
			'o' => 'm.create_at desc',
			'r' => ['o' => ($limit->page - 1) * $limit->size, 'l' => $limit->size],
		];

		if ($missions = $this->query_objs_ss($q, $q2)) {
			foreach ($missions as $oMission) {
				$oMission->type = 'mission';
				/* 项目是否已经星标 */
				$qStar = [
					'id',
					'xxt_account_topmatter',
					['matter_id' => $oMission->id, 'matter_type' => 'mission', 'userid' => $oUser->id],
				];
				if ($oStar = $this->query_obj_ss($qStar)) {
					$oMission->star = $oStar->id;
				}
				/* 项目下活动的数量 */
				$qMatterNum = ['select matter_type,scenario,count(*) matter_num from xxt_mission_matter where mission_id=' . $oMission->id . ' group by matter_type,scenario'];
				$matterNums = $this->query_objs($qMatterNum);
				$oMatterNums = new \stdClass;
				$oMatterNums->num = 0;
				foreach ($matterNums as $oMn) {
					switch ($oMn->matter_type) {
					case 'enroll':
						if (!isset($oMatterNums->enroll)) {
							$oMatterNums->enroll = new \stdClass;
							$oMatterNums->enroll->num = 0;
						}
						if (!empty($oMn->scenario)) {
							$oMatterNums->enroll->{$oMn->scenario} = (int) $oMn->matter_num;
						}
						$oMatterNums->enroll->num += (int) $oMn->matter_num;
						break;
					case 'group':
						if (!isset($oMatterNums->group)) {
							$oMatterNums->group = new \stdClass;
							$oMatterNums->group->num = 0;
						}
						if (!empty($oMn->scenario)) {
							$oMatterNums->group->{$oMn->scenario} = (int) $oMn->matter_num;
						}
						$oMatterNums->group->num += (int) $oMn->matter_num;
						break;
					default:
						$oMatterNums->{$oMn->matter_type} = (int) $oMn->matter_num;
					}
					if (!in_array($oMn->matter_type, ['news', 'channel', 'link'])) {
						$oMatterNums->num += (int) $oMn->matter_num;
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
	public function addMatter($user, $siteId, $missionId, $oMatter, $aOptions = []) {
		$relation = [
			'siteid' => $siteId,
			'mission_id' => $missionId,
			'phase_id' => isset($oMatter->mission_phase_id) ? $oMatter->mission_phase_id : '',
			'matter_id' => $oMatter->id,
			'matter_type' => $oMatter->type,
			'matter_title' => $this->escape($oMatter->title),
			'scenario' => isset($oMatter->scenario) ? $oMatter->scenario : '',
			'start_at' => isset($oMatter->start_at) ? $oMatter->start_at : 0,
			'end_at' => isset($oMatter->end_at) ? $oMatter->end_at : 0,
			'creater' => $user->id,
			'creater_name' => $this->escape($user->name),
			'creater_src' => $user->src,
			'create_at' => time(),
			'is_public' => isset($aOptions['is_public']) ? $aOptions['is_public'] : 'Y',
		];

		$this->insert('xxt_mission_matter', $relation, false);

		return true;
	}
	/**
	 * 更新项目中的素材信息
	 */
	public function updateMatter($missionId, $oMatter) {

		$relation = [
			'matter_title' => $this->escape($oMatter->title),
			'phase_id' => isset($oMatter->mission_phase_id) ? $oMatter->mission_phase_id : '',
			'scenario' => isset($oMatter->scenario) ? $oMatter->scenario : '',
			'start_at' => isset($oMatter->start_at) ? $oMatter->start_at : 0,
			'end_at' => isset($oMatter->end_at) ? $oMatter->end_at : 0,
		];
		$rst = $this->update(
			'xxt_mission_matter',
			$relation,
			['mission_id' => $missionId, 'matter_id' => $oMatter->id, 'matter_type' => $oMatter->type]
		);

		return $rst;
	}
	/**
	 * 从项目中删除素材
	 */
	public function removeMatter($missionId, $oMatter) {
		$rst = $this->delete(
			'xxt_mission_matter',
			['mission_id' => $missionId, 'matter_id' => $oMatter->id, 'matter_type' => $oMatter->type]
		);

		return $rst;
	}
	/**
	 *
	 */
	public function &byMatter($siteId, $matterId, $matterType, $aOptions = array()) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';

		$q = array(
			$fields,
			'xxt_mission m',
			"exists(select 1 from xxt_mission_matter mm where m.id=mm.mission_id and siteid='$siteId' and matter_id='$matterId' and matter_type='$matterType')",
		);

		$mission = $this->query_obj_ss($q);

		return $mission;
	}
}