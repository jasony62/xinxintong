<?php
namespace matter\enroll;

require_once dirname(__FILE__) . '/entity.php';
/**
 *
 */
class tag2_model extends entity_model {
	/**
	 *
	 */
	public function byId($id, $oOptions = []) {
		$fields = empty($oOptions['fields']) ? '*' : $oOptions['fields'];
		$q = [$fields, 'xxt_enroll_tag', ['id' => $id]];

		$oTag = $this->query_obj_ss($q);

		return $oTag;
	}
	/**
	 *
	 */
	public function userTagByTagId($id, $oUser = null, $oOptions = []) {
		$fields = empty($oOptions['fields']) ? '*' : $oOptions['fields'];

		$q = [$fields, 'xxt_enroll_user_tag', ['tag_id' => $id, 'userid' => $oUser->uid]];

		$oTag = $this->query_obj_ss($q);

		return $oTag;
	}
	/**
	 * 获得记录的标签
	 */
	public function byRecord($oRecord, $oUser = null, $oOptions = []) {
		$bUserAndPublic = isset($oOptions['UserAndPublic']) ? ($oOptions['UserAndPublic'] === true) : false;

		$oResult = new \stdClass;

		if (empty($oUser->uid)) {
			/* 只返回公共的标签 */
			$q = [
				't.id tag_id,t.assign_num,t.label',
				'xxt_enroll_tag t inner join xxt_enroll_tag_target tt on tt.tag_id=t.id',
				['tt.target_id' => $oRecord->id, 'tt.target_type' => 1, 't.public' => 'Y'],
			];
			$q2 = ['o' => 't.assign_num desc'];
			$tags = $this->query_objs_ss($q, $q2);
			$oResult->public = $tags;

			return $oResult;
		}

		$q = [
			'a.tag_id,a.user_tag_id,a.assign_at,t.label,t.public',
			'xxt_enroll_tag_assign a inner join xxt_enroll_tag t on a.tag_id=t.id',
			['a.target_id' => $oRecord->id, 'a.target_type' => 1, 'a.userid' => $oUser->uid],
		];
		$q2 = ['o' => 'a.assign_at desc'];
		$userTags = $this->query_objs_ss($q, $q2);

		$oResult->user = $userTags;
		if ($bUserAndPublic) {
			$oPublicResult = $this->byRecord($oRecord);
			$publicTags = $oPublicResult->public;
			if (count($userTags) && count($publicTags)) {
				foreach ($userTags as $oUserTag) {
					if ($oUserTag->public === 'Y') {
						foreach ($publicTags as $index => $oTag) {
							if ($oUserTag->tag_id === $oTag->tag_id) {
								array_splice($publicTags, $index, 1);
								break;
							}
						}
					}
				}
			}
			if (!empty($publicTags)) {
				$oResult->public = $publicTags;
			}
		}

		return $oResult;
	}
	/**
	 * 指定用户在指定活动中创建的标签
	 */
	public function byUser($oApp, $oUser) {
		$q = [
			't.id tag_id,t.label,t.public,t.forbidden,e.create_at,e.id user_tag_id',
			'xxt_enroll_tag t inner join xxt_enroll_user_tag e on t.id=e.tag_id',
			['t.aid' => $oApp->id, 'e.userid' => $oUser->uid, 'e.state' => 1],
		];
		$q2 = ['o' => 'e.create_at desc'];
		$tags = $this->query_objs_ss($q, $q2);

		return $tags;
	}
	/**
	 * 指定用户在指定活动中创建的标签
	 */
	public function byApp($oApp) {
		$q = [
			'id tag_id,label,public,forbidden,assign_num',
			'xxt_enroll_tag',
			['aid' => $oApp->id, 'public' => 'Y'],
		];
		$q2 = ['o' => 'assign_num desc'];
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