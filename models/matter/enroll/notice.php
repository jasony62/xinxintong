<?php
namespace matter\enroll;

require_once dirname(__FILE__) . '/event.php';

/**
 * 登记活动用户提醒
 */
class notice_model extends \TMS_MODEL {
	/**
	 * 用户提交记录
	 * 1、如果提交记录的用户属于一个活动分组，那么通知这个组的所有成员
	 * 2、如果提交记录的用户不属于任何活动分组，那么通知活动编辑组的所有成员或超级用户
	 */
	public function addRecord($oApp, $oRecord, $oUser) {
		$targetGroupId = null; // 提交人所属分组或编辑组
		$noticeReason = null;
		/* 通知记录提交人的同组用户 */
		if (!empty($oRecord->group_id)) {
			$targetGroupId = $oRecord->group_id;
			$noticeReason = 'same.group'; // 同组用户提交新记录，去补充数据
		} else if (!empty($oApp->actionRule->role->editor->group)) {
			/* 通知活动编辑组 */
			$targetGroupId = $oApp->actionRule->role->editor->group;
			$noticeReason = 'as.editor'; // 用户提交新记录，去处理
		}
		if (!empty($targetGroupId)) {
			$modelGrpUsr = $this->model('matter\group\player');
			$q = [
				'userid,nickname',
				'xxt_group_player',
				['round_id' => $targetGroupId, 'state' => 1, 'userid' => (object) ['op' => '<>', 'pat' => $oRecord->userid]],
			];
			$grpUsers = $modelGrpUsr->query_objs_ss($q);
		} else {
			$targetGrpAppId = null;
			if (!empty($oApp->group_app_id)) {
				$targetGrpAppId = $oApp->group_app_id;
			} else if (isset($oApp->entryRule->scope->group) && $oApp->entryRule->scope->group === 'Y' && isset($oApp->entryRule->group->id)) {
				$targetGrpAppId = $oApp->entryRule->group->id;
			}
			if (!empty($targetGrpAppId)) {
				$noticeReason = 'as.super';
				$modelGrpUsr = $this->model('matter\group\player');
				$q = [
					'userid,nickname',
					'xxt_group_player',
					['aid' => $targetGrpAppId, 'state' => 1, 'is_leader' => 'S', 'userid' => (object) ['op' => '<>', 'pat' => $oRecord->userid]],
				];
				$grpUsers = $modelGrpUsr->query_objs_ss($q);
			}
		}
		/* 生成通知 */
		if (!empty($grpUsers) && !empty($noticeReason)) {
			foreach ($grpUsers as $oGrpUser) {
				$oNewNotice = new \stdClass;
				$oNewNotice->siteid = $oApp->siteid;
				$oNewNotice->aid = $oApp->id;
				$oNewNotice->rid = $oRecord->rid;
				$oNewNotice->enroll_key = $oRecord->enroll_key;
				$oNewNotice->userid = $oGrpUser->userid;
				$oNewNotice->nickname = $this->escape($oGrpUser->nickname);
				$oNewNotice->event_userid = $oUser->uid;
				$oNewNotice->event_nickname = $this->escape($oUser->nickname);
				$oNewNotice->event_target_id = $oRecord->id;
				$oNewNotice->event_target_type = 'record';
				$oNewNotice->event_name = event_model::SubmitEventName;
				$oNewNotice->event_op = 'New';
				$oNewNotice->event_at = $oRecord->enroll_at;
				$oNewNotice->notice_reason = $noticeReason;
				$this->insert('xxt_enroll_notice', $oNewNotice, false);
			}
			return count($grpUsers);
		}

		return 0;
	}
	/**
	 * 用户提交数据
	 * 1、如果提交数据人和记录提交人不是同一个人，通知记录的提交人
	 * 2、通知记录下提交数据的其他人
	 */
	public function addCowork($oApp, $oRecData, $oNewItem, $oUser) {
		$oTargetUsers = [];
		/* 记录的提交者 */
		if ($oRecData->userid !== $oNewItem->userid) {
			$oTargetUsers[$oRecData->userid] = (object) ['nickname' => $oRecData->nickname, 'reason' => 'record.owner'];
		}
		/* 记录下其他提交答案的人 */
		$others = $this->model('matter\enroll\data')->getMultitext($oRecData->enroll_key, $oRecData->schema_id, ['excludeRoot' => true, 'fields' => 'userid,nickname']);
		foreach ($others as $oOther) {
			if ($oUser->uid !== $oOther->userid) {
				if (!isset($oTargetUsers[$oOther->userid])) {
					$oTargetUsers[$oOther->userid] = (object) ['nickname' => $oOther->nickname, 'reason' => 'other.cowork'];
				}
			}
		}
		/* 记录提交者的同组用户 */
		if (!empty($oRecData->group_id)) {
			$modelGrpUsr = $this->model('matter\group\player');
			$q = [
				'userid,nickname',
				'xxt_group_player',
				['round_id' => $oRecData->group_id, 'state' => 1, 'userid' => (object) ['op' => '<>', 'pat' => $oUser->uid]],
			];
			$grpUsers = $modelGrpUsr->query_objs_ss($q);
			foreach ($grpUsers as $oGrpUser) {
				if (!isset($oTargetUsers[$oGrpUser->userid])) {
					$oTargetUsers[$oGrpUser->userid] = (object) ['nickname' => $oGrpUser->nickname, 'reason' => 'same.group'];
				}
			}
		}
		/* 生成提醒 */
		foreach ($oTargetUsers as $userid => $oTargetUser) {
			$oNewNotice = new \stdClass;
			$oNewNotice->siteid = $oApp->siteid;
			$oNewNotice->aid = $oApp->id;
			$oNewNotice->rid = $oNewItem->rid;
			$oNewNotice->enroll_key = $oNewItem->enroll_key;
			$oNewNotice->userid = $userid;
			$oNewNotice->nickname = $this->escape($oTargetUser->nickname);
			$oNewNotice->event_userid = $oUser->uid;
			$oNewNotice->event_nickname = $this->escape($oUser->nickname);
			$oNewNotice->event_target_id = $oNewItem->id;
			$oNewNotice->event_target_type = 'record.data';
			$oNewNotice->event_name = event_model::DoSubmitCoworkEventName;
			$oNewNotice->event_op = 'New';
			$oNewNotice->event_at = $oNewItem->submit_at;
			$oNewNotice->notice_reason = $oTargetUser->reason;
			$this->insert('xxt_enroll_notice', $oNewNotice, false);
		}

		return count($oTargetUsers);
	}
	/**
	 * 添加留言
	 */
	public function addRemark($oApp, $oRecord, $oNewRemark, $oUser, $oRecData = null, $oRemark = null) {
		$oTargetUsers = [];
		/* 被留言记录的提交者 */
		if ($oRecord->userid !== $oNewRemark->userid) {
			$oTargetUsers[$oRecord->userid] = (object) ['nickname' => $oRecord->nickname, 'reason' => 'record.owner'];
		}
		/* 被留言数据的提交者 */
		if (isset($oRecData) && $oRecData->userid !== $oNewRemark->userid) {
			if (!isset($oTargetUsers[$oRecData->userid])) {
				$oTargetUsers[$oRecData->userid] = (object) ['nickname' => $oRecData->nickname, 'reason' => 'record.data.owner'];
			}
		}
		/* 被留言留言的提交者 */
		if (isset($oRemark) && $oRemark->userid !== $oNewRemark->userid) {
			if (!isset($oTargetUsers[$oRemark->userid])) {
				$oTargetUsers[$oRemark->userid] = (object) ['nickname' => $oRemark->nickname, 'reason' => 'remark.owner'];
			}
		}

		/* 生成提醒 */
		foreach ($oTargetUsers as $userid => $oTargetUser) {
			$oNewNotice = new \stdClass;
			$oNewNotice->siteid = $oApp->siteid;
			$oNewNotice->aid = $oApp->id;
			$oNewNotice->rid = $oNewRemark->rid;
			$oNewNotice->enroll_key = $oNewRemark->enroll_key;
			$oNewNotice->userid = $userid;
			$oNewNotice->nickname = $this->escape($oTargetUser->nickname);
			$oNewNotice->event_userid = $oUser->uid;
			$oNewNotice->event_nickname = $this->escape($oUser->nickname);
			$oNewNotice->event_target_id = $oNewRemark->id;
			$oNewNotice->event_target_type = 'remark';
			$oNewNotice->event_name = event_model::DoRemarkEventName;
			$oNewNotice->event_op = 'New';
			$oNewNotice->event_at = $oNewRemark->create_at;
			$oNewNotice->notice_reason = $oTargetUser->reason;
			$this->insert('xxt_enroll_notice', $oNewNotice, false);
		}

		return count($oTargetUsers);
	}
	/**
	 *
	 */
	public function byId($id, $oOptions = []) {
		$fields = isset($oOptions['fields']) ? $oOptions['fields'] : '*';
		$q = [
			$fields,
			'xxt_enroll_notice',
			['id' => $id],
		];
		$oNotice = $this->query_obj_ss($q);

		return $oNotice;
	}
	/**
	 * 获得指定活动的通知
	 */
	public function byApp($oApp, $oOptions = []) {
		$fields = isset($oOptions['fields']) ? $oOptions['fields'] : '*';
		$q = [
			$fields,
			'xxt_enroll_notice',
			['aid' => $oApp->id, 'state' => 1],
		];
		/* 按用户筛选 */
		if (isset($oOptions['user']) && is_object($oOptions['user'])) {
			$oUser = $oOptions['user'];
			if (!empty($oUser->uid)) {
				$q[2]['userid'] = $oUser->uid;
			}
		}
		/* 查询结果分页 */
		if (isset($oOptions['page']) && is_object($oOptions['page'])) {
			$oPage = $oOptions['page'];
		} else {
			$oPage = (object) ['at' => 1, 'size' => 30];
		}
		$q2['r'] = ['o' => ((int) $oPage->at - 1) * (int) $oPage->size, 'l' => (int) $oPage->size];

		$notices = $this->query_objs_ss($q, $q2);

		$oResult = new \stdClass;
		$oResult->notices = $notices;
		/* 符合条件的数据总数 */
		if (count($notices) < (int) $oPage->size) {
			$oResult->total = ((int) $oPage->at - 1) * (int) $oPage->size + count($notices);
		} else {
			$q[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q);
			$oResult->total = $total;
		}

		return $oResult;
	}
}