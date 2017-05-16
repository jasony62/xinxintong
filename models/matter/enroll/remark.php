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
	 * 获得指定登记记录的评论
	 */
	public function listByRecord($ek, $schemaId = '', $page = 1, $size = 10, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$result = new \stdClass;
		$q = [
			$fields,
			'xxt_enroll_record_remark',
			['enroll_key' => $ek, 'schema_id' => $schemaId],
		];
		$q2 = ['o' => 'create_at desc', 'r' => ['o' => ($page - 1) * $size, 'l' => $size]];
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
}