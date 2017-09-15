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
	public function &byMission($missionId, $matterType = null, $options = [], $verbose = 'Y') {
		$fields = isset($options['fields']) ? $options['fields'] : 'id,matter_id,matter_title,matter_type,is_public,seq,create_at,start_at,end_at,scenario,phase_id';

		$q = [
			$fields,
			'xxt_mission_matter',
			["mission_id" => $missionId],
		];
		!empty($matterType) && $q[2]['matter_type'] = $matterType;

		$q2 = ['o' => 'seq,create_at desc'];
		$mms = $this->query_objs_ss($q, $q2);

		if ($verbose === 'Y' && count($mms)) {
			$matters = [];
			$modelByType = new \stdClass;
			foreach ($mms as &$mm) {
				if (isset($modelByType->{$mm->matter_type})) {
					$modelMat = $modelByType->{$mm->matter_type};
				} else {
					$modelMat = $this->model('matter\\' . $mm->matter_type);
				}
				if (in_array($mm->matter_type, ['enroll', 'signin', 'group'])) {
					$fields = 'siteid,id,title,summary,pic,create_at,creater_name,data_schemas,op_short_url_code';
					if (in_array($mm->matter_type, ['enroll', 'signin'])) {
						$fields .= ',start_at,end_at';
					}
					if (in_array($mm->matter_type, ['enroll'])) {
						$fields .= ',end_submit_at,rp_short_url_code,can_coin,entry_rule';
					}
					if (in_array($mm->matter_type, ['enroll', 'group'])) {
						$fields .= ',scenario';
					}
					//
					$options2 = ['fields' => $fields, 'cascaded' => 'N'];
				} else {
					$fields = 'siteid,id,title,summary,pic,create_at,creater_name';
					$options2 = ['fields' => $fields, 'cascaded' => 'N'];
				}

				if (isset($options['mission_phase_id'])) {
					$options2['where'] = array('mission_phase_id' => $options['mission_phase_id']);
				}

				if ($oMatter = $modelMat->byId($mm->matter_id, $options2)) {
					/* 是否开放了运营者链接 */
					if (isset($options['op_short_url_code']) && $options['op_short_url_code'] === true && empty($oMatter->op_short_url_code)) {
						continue;
					}
					if (isset($options['is_public']) && $options['is_public'] !== $mm->is_public) {
						continue;
					}
					$oMatter->_pk = $mm->id;
					$oMatter->is_public = $mm->is_public;
					$oMatter->seq = $mm->seq;
					if ($mm->matter_type === 'signin') {
						if (!isset($modelSigRnd)) {
							$modelSigRnd = $this->model('matter\signin\round');
						}
						$oMatter->rounds = $modelSigRnd->byApp($oMatter->id);
					}
					$matters[] = $oMatter;
				}
			}

			return $matters;
		}

		return $mms;
	}
}