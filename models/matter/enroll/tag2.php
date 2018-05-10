<?php
namespace matter\enroll;
/**
 *
 */
class tag2_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function byId($id, $oUser = null, $oOptions = []) {
		$fields = empty($oOptions['fields']) ? '*' : $oOptions['fields'];
		if (isset($oUser->uid)) {
			$q = [$fields, 'xxt_enroll_user_tag', ['id' => $id]];
		} else {
			$q = [$fields, 'xxt_enroll_tag', ['id' => $id]];
		}

		$oTag = $this->query_obj_ss($q);

		return $oTag;
	}
	/**
	 * 获得标签
	 */
	public function byRecord($oRecord, $oUser = null) {
		if (isset($oUser->uid)) {
			$q = [
				'a.tag_id,a.user_tag_id,u.assign_num,t.label',
				'(xxt_enroll_tag_assign a inner join xxt_enroll_tag t on a.tag_id=t.id) inner join xxt_enroll_user_tag u on a.user_tag_id=u.id',
				['a.target_id' => $oRecord->id, 'a.target_type' => 1, 'a.userid' => $oUser->uid],
			];
			$q2 = ['o' => 'a.assign_at desc'];
		} else {
			$q = [
				't.id tag_id,t.assign_num,t.label',
				'xxt_enroll_tag t inner join xxt_enroll_tag_target tt on tt.tag_id=t.id',
				['tt.target_id' => $oRecord->id, 'tt.target_type' => 1, 't.public' => 'Y'],
			];
			$q2 = ['o' => 't.assign_num desc'];
		}

		$tags = $this->query_objs_ss($q, $q2);

		return $tags;
	}
	/**
	 *
	 */
	public function logByTarget($tagId, $oTarget, $oOptions = []) {
		$fields = empty($oOptions['fields']) ? '*' : $oOptions['fields'];
		$q = [
			$fields,
			'xxt_enroll_tag_target',
			['tag_id' => $tagId, 'target_id' => $oTarget->id, 'target_type' => $oTarget->type],
		];
		$oLog = $this->query_obj_ss($q);

		return $oLog;
	}
}