<?php
namespace matter\enroll;
/**
 * 登记数据评论
 */
class remark_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function byId($id, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_enroll_record_remark',
			['id' => $id],
		];
		if ($oRemark = $this->query_obj_ss($q)) {
			if ($fields === '*' || false !== strpos($fields, 'like_log')) {
				$oRemark->like_log = empty($oRemark->like_log) ? new \stdClass : json_decode($oRemark->like_log);
			}
		}

		return $oRemark;
	}
	/**
	 *
	 */
	public function byUser($oApp, $oUser, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$userid = isset($oUser->uid) ? $oUser->uid : (isset($oUser->userid) ? $oUser->userid : '');
		if (empty($userid)) {
			return false;
		}

		$q = [
			$fields,
			'xxt_enroll_record_remark',
			['aid' => $oApp->id, 'userid' => $userid],
		];
		$remarks = $this->query_objs_ss($q);

		return $remarks;
	}
	/**
	 * 获得指定登记记录的评论
	 */
	public function listByRecord($oUser, $ek, $schemaId, $page = 1, $size = 10, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$result = new \stdClass;
		$q = [
			$fields,
			'xxt_enroll_record_remark',
			"enroll_key='$ek' and schema_id='$schemaId'",
		];
		if (!empty($oUser->uid)) {
			$q[2] .= " and (agreed<>'N' or userid='{$oUser->uid}')";
		}
		if (isset($options['agreed']) && $options['agreed'] === 'Y') {
			$q[2] .= " and agreed='Y'";
		}
		if (isset($options['data_id'])) {
			$rdId = $options['data_id'];
			if (is_array($rdId)) {
				$rdId = implode('","', $options['data_id']);
			}
			$rdId = '("' . $rdId . '")';
			$q[2] .= " and data_id in $rdId";
		}
		
		$q2 = [
			'o' => 'agreed desc,create_at desc',
			'r' => ['o' => ($page - 1) * $size, 'l' => $size],
		];
		$aRemarks = $this->query_objs_ss($q, $q2);
		if (count($aRemarks)) {
			$fnHandlers = [];
			if ($fields === '*' || false !== strpos($fields, 'like_log')) {
				$fnHandlers[] = function (&$oRemark) {
					$oRemark->like_log = empty($oRemark->like_log) ? new \stdClass : json_decode($oRemark->like_log);
				};
			}
			if (count($fnHandlers)) {
				foreach ($aRemarks as &$oRemark) {
					foreach ($fnHandlers as $fnHandler) {
						$fnHandler($oRemark);
					}
				}
			}
		}
		$result->remarks = $aRemarks;

		$q[0] = 'count(*)';
		$result->total = (int) $this->query_val_ss($q);

		return $result;
	}
	/**
	 * 获得指定登记记录的评论
	 */
	public function listByApp($oApp, $page = 1, $size = 10, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$result = new \stdClass;
		$q = [
			$fields,
			'xxt_enroll_record_remark',
			"aid='$oApp->id'",
		];
		/* filter */
		if (isset($options['criteria'])) {
			$oCriteria = $options['criteria'];
			if (isset($oCriteria->enrollee)) {
				$q[2] .= " and enroll_userid='{$oCriteria->enrollee}'";
			}
			if (isset($oCriteria->remarker)) {
				$q[2] .= " and userid='{$oCriteria->remarker}'";
			}
			if (isset($oCriteria->agreed) && strcasecmp($oCriteria->agreed, 'all') !== 0) {
				$q[2] .= " and agreed='{$oCriteria->agreed}'";
			}
		}

		$q2 = [];
		/* orderby */
		if (isset($options['criteria'])) {
			$oCriteria = $options['criteria'];
			if (isset($oCriteria->orderby)) {
				$q2['o'] = $oCriteria->orderby . ' desc';
			} else {
				$q2['o'] = 'create_at desc';
			}
		} else {
			$q2['o'] = 'create_at desc';
		}
		/* pagination */
		$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];

		$aRemarks = $this->query_objs_ss($q, $q2);
		$oAssocRecords = new \stdClass;
		if (count($aRemarks)) {
			$fnHandlers = [];
			if ($fields === '*' || false !== strpos($fields, 'like_log')) {
				$fnHandlers[] = function (&$oRemark) {
					$oRemark->like_log = empty($oRemark->like_log) ? new \stdClass : json_decode($oRemark->like_log);
				};
			}
			/* 处理获得的数据 */
			$cachedData = new \stdClass;
			$modelRec = $this->model('matter\enroll\record');
			foreach ($aRemarks as &$oRemark) {
				foreach ($fnHandlers as $fnHandler) {
					$fnHandler($oRemark);
				}
				if (!isset($oAssocRecords->{$oRemark->enroll_key})) {
					$oRecord = $modelRec->byId($oRemark->enroll_key, ['fields' => 'userid,enroll_at,nickname,data']);
					$oAssocRecords->{$oRemark->enroll_key} = $oRecord;
				}
			}
		}

		$result->remarks = $aRemarks;
		$result->records = $oAssocRecords;

		$q[0] = 'count(*)';
		$result->total = (int) $this->query_val_ss($q);

		return $result;
	}
}