<?php
namespace matter\enroll;
/**
 * 专题
 */
class topic_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function byId($id, $aOptons = []) {
		$fields = empty($aOptons['fields']) ? '*' : $aOptons['fields'];

		$q = [$fields, 'xxt_enroll_topic', ['id' => $id]];

		$oTopic = $this->query_obj_ss($q);

		return $oTopic;
	}
	/**
	 * 返回专题下的记录
	 */
	public function records($oApp, $oTopic) {
		$q = [
			'r.*,tr.assign_at,tr.seq seq_in_topic',
			'xxt_enroll_record r inner join xxt_enroll_topic_record tr on r.id=tr.record_id',
			['tr.topic_id' => $oTopic->id],
		];
		$q2 = ['o' => 'tr.seq'];

		$records = $this->query_objs_ss($q, $q2);
		if (count($records)) {
			$modelRec = $this->model('matter\enroll\record');
			$modelRec->parse($oApp, $records);
		}

		$oResult = new \stdClass;
		$oResult->records = $records;
		$oResult->total = count($records);

		return $oResult;
	}
}