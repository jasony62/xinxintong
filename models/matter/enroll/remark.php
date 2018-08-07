<?php
namespace matter\enroll;
/**
 * 登记数据留言
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
			if (property_exists($oRemark, 'like_log')) {
				$oRemark->like_log = empty($oRemark->like_log) ? new \stdClass : json_decode($oRemark->like_log);
			}
			if (property_exists($oRemark, 'agreed_log')) {
				$oRemark->agreed_log = empty($oRemark->agreed_log) ? new \stdClass : json_decode($oRemark->agreed_log);
			}
			if (property_exists($oRemark, 'modify_log')) {
				$oRemark->modify_log = empty($oRemark->modify_log) ? [] : json_decode($oRemark->modify_log);
			}
		}

		return $oRemark;
	}
	/**
	 * 用户在指定活动中发表的留言
	 */
	public function byUser($oApp, $oUser, $oOptions = []) {
		$fields = isset($oOptions['fields']) ? $oOptions['fields'] : '*';

		$userid = isset($oUser->uid) ? $oUser->uid : (isset($oUser->userid) ? $oUser->userid : '');
		if (empty($userid)) {
			return false;
		}

		$q = [
			$fields,
			'xxt_enroll_record_remark',
			['aid' => $oApp->id, 'userid' => $userid],
		];
		if (!empty($oOptions['ek'])) {
			$q[2]['enroll_key'] = $oOptions['ek'];
		}
		if (!empty($oOptions['rid'])) {
			$q[2]['rid'] = $oOptions['rid'];
		}

		$remarks = $this->query_objs_ss($q);

		return $remarks;
	}
	/**
	 * 获得指定登记记录的留言
	 */
	public function listByRecord($oUser, $ek, $schemaId, $page = 1, $size = 10, $oOptions = []) {
		$fields = isset($oOptions['fields']) ? $oOptions['fields'] : '*';

		$oRecord = $this->model('matter\enroll\record')->byId($ek, ['fields' => 'rid,userid,state']);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError('（1）指定的对象不存在或不可用');
		}

		$oResult = new \stdClass;
		$q = [
			$fields,
			'xxt_enroll_record_remark',
			"enroll_key='$ek' and state=1",
		];
		if (!empty($schemaId)) {
			$q[2] .= " and schema_id='$schemaId'";
		}
		/* 根据数据状态或分组显示可见性 */
		if (!empty($oUser->uid) && $oRecord->userid !== $oUser->uid) {
			if (empty($oUser->is_leader) || $oUser->is_leader !== 'S') {
				$w = " and (";
				$w .= "(agreed<>'N' and agreed<>'D')";
				$w .= " or userid='{$oUser->uid}'";
				if (!empty($oUser->group_id)) {
					$w .= " or group_id='{$oUser->group_id}'";
				}
				if (isset($oUser->is_editor) && $oUser->is_editor === 'Y') {
					$w .= " or group_id=''";
				}
				$w .= ")";
				$q[2] .= $w;
			}
		}
		if (isset($oOptions['agreed']) && $oOptions['agreed'] === 'Y') {
			$q[2] .= " and agreed='Y'";
		}
		if (isset($oOptions['data_id'])) {
			$rdId = $oOptions['data_id'];
			if (is_array($rdId)) {
				$rdId = implode('","', $oOptions['data_id']);
			}
			$rdId = '("' . $rdId . '")';
			$q[2] .= " and data_id in $rdId";
		}

		// $q2 = [
		// 	'o' => 'agreed desc,create_at desc',
		// ];
		$q2 = [
			'o' => 'seq_in_record',
		];
		if (isset($page) && isset($size)) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}
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
		$oResult->remarks = $aRemarks;

		$q[0] = 'count(*)';
		$oResult->total = (int) $this->query_val_ss($q);

		return $oResult;
	}
	/**
	 * 获得指定登记记录的留言
	 */
	public function listByApp($oApp, $page = 1, $size = 10, $oOptions = []) {
		$fields = isset($oOptions['fields']) ? $oOptions['fields'] : '*';

		$oResult = new \stdClass;
		$q = [
			$fields,
			'xxt_enroll_record_remark',
			"aid='$oApp->id'",
		];
		/* filter */
		if (isset($oOptions['criteria'])) {
			$oCriteria = $oOptions['criteria'];
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
		if (isset($oOptions['criteria'])) {
			$oCriteria = $oOptions['criteria'];
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

		$oResult->remarks = $aRemarks;
		$oResult->records = $oAssocRecords;

		$q[0] = 'count(*)';
		$oResult->total = (int) $this->query_val_ss($q);

		return $oResult;
	}
}