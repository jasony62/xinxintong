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
}