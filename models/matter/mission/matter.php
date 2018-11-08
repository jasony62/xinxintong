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
	 *
	 * @param int $missionId
	 * @param mixed $matterType string/array
	 * @param array $options
	 *
	 */
	public function byMission($missionId, $matterType = null, $aOptions = [], $verbose = 'Y') {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : 'id,matter_id,matter_title,matter_type,is_public,seq,create_at,start_at,end_at,scenario';

		$q = [
			$fields,
			'xxt_mission_matter',
			["mission_id" => $missionId],
		];

		/* 按类型过滤 */
		!empty($matterType) && $q[2]['matter_type'] = $matterType;
		/* 按是用户是否可见过滤 */
		if (!empty($aOptions['is_public'])) {
			$q[2]['is_public'] = $aOptions['is_public'];
		}
		/* 按名称过滤 */
		if (!empty($aOptions['byTitle'])) {
			$q[2]["matter_title"] = (object) ['op' => 'like', 'pat' => '%' . $aOptions['byTitle'] . '%'];
		}
		/* 按开始结束时间过滤 */
		if (!empty($aOptions['byTime'])) {
			switch ($aOptions['byTime']) {
			case 'running':
				$q[2]["start_at"] = (object) ['op' => 'between', 'pat' => [1, time()]];
				$q[2]["end_at"] = (object) ['op' => 'not between', 'pat' => [1, time()]];
				break;
			case 'pending':
				$q[2]["start_at"] = (object) ['op' => '>', 'pat' => time()];
				break;
			case 'over':
				$q[2]["end_at"] = (object) ['op' => 'between', 'pat' => [1, time()]];
				break;
			}
		}
		/* 按场景过滤 */
		if (!empty($aOptions['byScenario'])) {
			$q[2]['scenario'] = $aOptions['byScenario'];
		}

		$q2 = ['o' => 'create_at desc'];
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
					$fields = 'siteid,mission_id,id,title,summary,pic,create_at,creater_name,data_schemas,start_at,end_at';
					if (in_array($mm->matter_type, ['enroll'])) {
						$fields .= ',can_coin,entry_rule,round_cron,sync_mission_round';
					}
					if (in_array($mm->matter_type, ['enroll', 'group'])) {
						$fields .= ',scenario';
					}
					//
					$options2 = ['fields' => $fields, 'cascaded' => 'N'];
				} else if (in_array($mm->matter_type, ['wall'])) {
					$fields = 'siteid,id,title,summary,pic,create_at,creater_name,start_at,end_at';
					$options2 = ['fields' => $fields];
				} else if ($mm->matter_type === 'memberschema') {
					$fields = 'siteid,id,title,create_at,start_at,end_at,url';
					$options2 = ['fields' => $fields, 'cascaded' => 'N'];
				} else {
					$fields = 'siteid,id,title,summary,pic,create_at,creater_name';
					$options2 = ['fields' => $fields, 'cascaded' => 'N'];
				}

				if ($oMatter = $modelMat->byId($mm->matter_id, $options2)) {
					$oMatter->_pk = $mm->id;
					$oMatter->is_public = $mm->is_public;
					$oMatter->seq = $mm->seq;
					if ($mm->matter_type === 'signin') {
						if (!isset($modelSigRnd)) {
							$modelSigRnd = $this->model('matter\signin\round');
						}
						$oMatter->rounds = $modelSigRnd->byApp($oMatter->id);
					}
					if (in_array($mm->matter_type, ['enroll', 'signin'])) {
						$oMatter->opData = $modelMat->opData($oMatter, true);
					}
					if (in_array($mm->matter_type, ['memberschema', 'link', 'channel'])) {
						$oMatter->entryUrl = $modelMat->getEntryUrl($oMatter->siteid, $oMatter->id);
					}
					$matters[] = $oMatter;
				}
			}

			return $matters;
		}

		return $mms;
	}
	/**
	 * 项目中的推荐内容
	 */
	public function agreed($oApp, $objUnit, $oAgreedObj, $agreedResult) {
		if (empty($oApp->id) || empty($oApp->siteid) || empty($oApp->mission_id) || empty($oApp->type)) {
			return [false, '参数不完整'];
		}
		if (!in_array($objUnit, ['D', 'R'])) {
			return [false, '参数不完整'];
		}
		if (!in_array($agreedResult, ['Y', 'N', 'A'])) {
			$agreedResult = '';
		}
		$dbPk = [
			'matter_id' => $oApp->id,
			'matter_type' => $oApp->type,
			'obj_unit' => $objUnit,
			'obj_key' => $oAgreedObj->enroll_key,
		];
		if ($objUnit === 'D') {
			if (empty($oAgreedObj->id)) {
				return [false, '参数不完整'];
			}
			$dbPk['obj_data_id'] = $oAgreedObj->id;
		}

		$current = time();

		$oExisted = $this->query_obj_ss(['*', 'xxt_mission_agreed', $dbPk]);
		if ($oExisted) {
			if ($agreedResult !== 'Y') {
				$this->delete('xxt_mission_agreed', $dbPk);
			}
		} else {
			if ($agreedResult === 'Y') {
				/* 只有推荐时才需要记录 */
				$oNewLog = new \stdClass;
				$oNewLog->siteid = $oApp->siteid;
				$oNewLog->mission_id = $oApp->mission_id;
				$oNewLog->matter_id = $oApp->id;
				$oNewLog->matter_type = $oApp->type;
				$oNewLog->obj_unit = $objUnit;
				$oNewLog->obj_key = $oAgreedObj->enroll_key;
				$oNewLog->op_at = $current;
				if ($objUnit === 'D') {
					$oNewLog->obj_data_id = $oAgreedObj->id;
				}
				$oNewLog->id = $this->insert('xxt_mission_agreed', $oNewLog, true);

				return [true, $oNewLog];
			}
		}

		return [true];
	}
}