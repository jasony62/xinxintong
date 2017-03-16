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
	public function &byMission($missionId, $matterType = null, $options = []) {
		$matters = [];
		$fields = isset($options['fields']) ? $options['fields'] : 'id,matter_id,matter_type,is_public,seq,create_at';

		$q = [
			$fields,
			'xxt_mission_matter',
			["mission_id" => $missionId],
		];
		!empty($matterType) && $q[2]['matter_type'] = $matterType;

		$q2 = ['o' => 'seq,create_at desc'];
		$mms = $this->query_objs_ss($q, $q2);

		if (count($mms)) {
			$modelByType = new \stdClass;
			foreach ($mms as &$mm) {
				if (isset($modelByType->{$mm->matter_type})) {
					$modelMat = $modelByType->{$mm->matter_type};
				} else {
					$modelMat = $this->model('matter\\' . $mm->matter_type);
				}
				if (in_array($mm->matter_type, ['enroll', 'signin', 'group'])) {
					$fields = 'siteid,id,title,summary,pic,data_schemas,op_short_url_code';
					if (in_array($mm->matter_type, ['enroll'])) {
						$fields .= ',rp_short_url_code';
					}
					if (in_array($mm->matter_type, ['enroll', 'group'])) {
						$fields .= ',scenario';
					}
				} else {
					$fields = 'siteid,id,title,summary,pic';
				}
				if ($matter = $modelMat->byId($mm->matter_id, ['fields' => $fields, 'cascaded' => 'N'])) {
					/* 是否开放了运营者链接 */
					if (isset($options['op_short_url_code']) && $options['op_short_url_code'] === true && empty($matter->op_short_url_code)) {
						continue;
					}
					if (isset($options['is_public']) && $options['is_public'] !== $mm->is_public) {
						continue;
					}
					$matter->_pk = $mm->id;
					$matter->is_public = $mm->is_public;
					$matter->seq = $mm->seq;
					if ($mm->matter_type === 'signin') {
						if (!isset($modelSigRnd)) {
							$modelSigRnd = $this->model('matter\signin\round');
						}
						$matter->rounds = $modelSigRnd->byApp($matter->id);
					}
					$matters[] = $matter;
				}
			}
		}

		return $matters;
	}
}