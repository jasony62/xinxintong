<?php
namespace matter\mission;
/**
 * 项目下的素材
 */
class matter_model extends \TMS_MODEL {
	/**
	 * 获得项目下已有素材的数量
	 */
	public function count($missionId, $options = array()) {
		$q = [
			'count(*)',
			'xxt_mission_matter',
			"mission_id='$missionId'",
		];
		$count = (int) $this->query_val_ss($q);

		return $count;
	}
	/**
	 * 获得项目下的所有素材
	 */
	public function &byMission($missionId, $matterType = null, $options = array()) {
		$matters = [];
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_mission_matter',
			["mission_id" => $missionId],
		];
		!empty($matterType) && $q[2]['matter_type'] = $matterType;

		$q2 = ['o' => 'create_at desc'];
		$mms = $this->query_objs_ss($q, $q2);

		foreach ($mms as &$mm) {
			if ($matter = \TMS_APP::M('matter\\' . $mm->matter_type)->byId($mm->matter_id)) {
				/* 是否开放了运营者链接 */
				if (isset($options['op_short_url_code']) && $options['op_short_url_code'] === true && empty($matter->op_short_url_code)) {
					continue;
				}
				$matter->type = $mm->matter_type;
				$matters[] = $matter;
			}
		}

		return $matters;
	}
}