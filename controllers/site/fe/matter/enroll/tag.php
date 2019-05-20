<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 记录活动标签
 */
class tag extends base {
	/**
	 * 获得用户创建的标签
	 */
	public function list_action($app, $public = 'N') {
		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$oUser = $this->who;

		$modelTag = $this->model('matter\enroll\tag2');
		$userTags = $modelTag->byUser($oApp, $oUser);

		if ($public === 'Y') {
			$tags = $modelTag->byApp($oApp);
			/* 合并用户标签和公共标签 */
			if (count($userTags) && count($tags)) {
				foreach ($userTags as $oUserTag) {
					if ($oUserTag->public === 'Y') {
						foreach ($tags as $index => $oTag) {
							if ($oUserTag->tag_id === $oTag->tag_id) {
								array_splice($tags, $index, 1);
								break;
							}
						}
					}
				}
			}
			if (count($tags)) {
				$userTags = array_merge($userTags, $tags);
			}
		}

		return new \ResponseData($userTags);
	}
	/**
	 * 更新标签
	 */
	public function submit_action($app) {
		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oPosted = $this->getPostJson();
		if (empty($oPosted->label)) {
			return new \ParameterError('标签不允许为空');
		}
		$label = $oPosted->label;

		$oUser = $this->who;
		if (empty($oUser->unionid)) {
			return new \ResponseError('仅支持注册用户创建标签，请登录后再进行此操作');
		}
		/* 创建标签 */
		$bNewEnlTag = false;
		$q = [
			'id,siteid,aid,label,assign_num,user_num,public,forbidden',
			'xxt_enroll_tag',
			['aid' => $oApp->id, 'label' => $label],
		];
		if ($oEnlTag = $modelEnl->query_obj_ss($q)) {
			$q = [
				'id',
				'xxt_enroll_user_tag',
				['tag_id' => $oEnlTag->id, 'userid' => $oUser->uid, 'state' => 1],
			];
			if ($oUserTag = $modelEnl->query_obj_ss($q)) {
				return new \ParameterError('标签【' . $label . '】已经存在');
			}
			$oEnlTag->tag_id = $oEnlTag->id;
		} else {
			$oEnlTag = new \stdClass;
			$oEnlTag->siteid = $oApp->siteid;
			$oEnlTag->aid = $oApp->id;
			$oEnlTag->label = $label;
			$oEnlTag->assign_num = 0;
			$oEnlTag->user_num = 1;
			$oEnlTag->public = 'N';
			$oEnlTag->forbidden = 'N';
			$oEnlTag->id = $oEnlTag->tag_id = $modelEnl->insert('xxt_enroll_tag', $oEnlTag, true);
			$bNewEnlTag = true;
		}
		/* 用户数据 */
		$oUserTag = new \stdClass;
		$oUserTag->siteid = $oEnlTag->siteid;
		$oUserTag->aid = $oEnlTag->aid;
		$oUserTag->tag_id = $oEnlTag->id;
		$oUserTag->userid = $oUser->uid;
		$oUserTag->create_at = time();
		$oUserTag->id = $modelEnl->insert('xxt_enroll_user_tag', $oUserTag, true);
		if (false === $bNewEnlTag) {
			$modelEnl->update('xxt_enroll_tag', ['user_num' => (object) ['op' => '+=', 'pat' => 1]], ['id' => $oEnlTag->id]);
		}
		$oEnlTag->user_tag_id = $oUserTag->id;

		return new \ResponseData($oEnlTag);
	}
	/**
	 *
	 */
	public function update_action($tag) {
		$oUser = $this->who;
		if (empty($oUser->unionid)) {
			return new \ResponseError('仅支持注册用户创建标签，请登录后再进行此操作');
		}

		$modelTag = $this->model('matter\enroll\tag2');
		$oTag = $modelTag->byId($tag);
		if (false === $oTag) {
			return new \ObjectNotFoundError();
		}

		$oPosted = $this->getPostJson();
		$oUpdated = new \stdClass;
		foreach ($oPosted as $prop => $val) {
			switch ($prop) {
			case 'public':
				$oUpdated->public = $val === 'Y' ? 'Y' : 'N';
				break;
			}
		}
		if (count((object) $oUpdated) === 0) {
			return new \ParameterError('没有有效的更新数据');
		}

		$rst = $modelTag->update('xxt_enroll_tag', $oUpdated, ['id' => $oTag->id]);

		return new \ResponseData($rst);
	}
	/**
	 * 获得记录的用户标签和公共标签
	 */
	public function byRecord_action($record, $public = 'N') {
		$modelRec = $this->model('matter\enroll\record');
		$q = ['id,aid,siteid,state', 'xxt_enroll_record', ['id' => $record]];
		$oRecord = $modelRec->query_obj_ss($q);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->who;

		$modelTag = $this->model('matter\enroll\tag2');
		$oRecTags = $modelTag->byRecord($oRecord, $oUser, ['UserAndPublic' => $public === 'Y']);

		return new \ResponseData($oRecTags);
	}
	/**
	 * 打标签
	 */
	public function assign_action() {
		$oPosted = $this->getPostJson();
		$aAfterTagIds = $oPosted->tag;
		if (!isset($aAfterTagIds) || !is_array($aAfterTagIds)) {
			return new \ParameterError('没有指定专题');
		}

		$oUser = $this->who;
		if (empty($oUser->unionid)) {
			return new \ResponseError('仅支持注册用户创建，请登录后再进行此操作');
		}

		$oPosted->record = (int) $oPosted->record;
		if (empty($oPosted->record)) {
			return new \ObjectNotFoundError();
		}

		$modelRec = $this->model('matter\enroll\record');
		$q = ['id,aid,siteid,state', 'xxt_enroll_record', ['id' => $oPosted->record]];
		$oRecord = $modelRec->query_obj_ss($q);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$modelTag = $this->model('matter\enroll\tag2');
		$q = ['tag_id', 'xxt_enroll_tag_assign', ['target_id' => $oRecord->id, 'target_type' => 1, 'userid' => $oUser->uid]];
		$aBeforeTagIds = $modelTag->query_vals_ss($q);
		$countOfNew = 0;

		if (empty($aAfterTagIds) || empty($aBeforeTagIds)) {
			/* 更新后和更新前的专题没有交集 */
			$aNewTagIds = $aAfterTagIds;
			$aDelTagIds = $aBeforeTagIds;
		} else {
			/* 更新后和更新前的专题有交集 */
			$aNewTagIds = array_diff($aAfterTagIds, $aBeforeTagIds);
			$aDelTagIds = array_diff($aBeforeTagIds, $aAfterTagIds);
		}

		/* 新指定的标签 */
		if (count($aNewTagIds)) {
			$current = time();
			$oProtoNewRel = new \stdClass;
			$oProtoNewRel->aid = $oRecord->aid;
			$oProtoNewRel->siteid = $oRecord->siteid;
			$oProtoNewRel->target_id = $oRecord->id;
			$oProtoNewRel->target_type = 1;
			$oProtoNewRel->userid = $oUser->uid;
			$oProtoNewRel->assign_at = $current;
			foreach ($aNewTagIds as $tagId) {
				$oUserTag = $modelTag->userTagbyTagId($tagId, $oUser, ['fields' => 'id,tag_id']);
				$oProtoNewRel->tag_id = $tagId;
				$oProtoNewRel->user_tag_id = $oUserTag ? $oUserTag->id : 0;

				$modelTag->insert('xxt_enroll_tag_assign', $oProtoNewRel, false);
				/**
				 * 记录汇总数据
				 */
				$oTargetLog = $modelTag->logByTarget($tagId, (object) ['id' => $oRecord->id, 'type' => 1], ['fields' => 'id']);
				if (false === $oTargetLog) {
					$oTargetLog = new \stdClass;
					$oTargetLog->aid = $oRecord->aid;
					$oTargetLog->siteid = $oRecord->siteid;
					$oTargetLog->tag_id = $tagId;
					$oTargetLog->target_id = $oRecord->id;
					$oTargetLog->target_type = 1;
					$oTargetLog->last_assign_at = $oTargetLog->first_assign_at = $current;
					$oTargetLog->assign_num = 1;
					$modelTag->insert('xxt_enroll_tag_target', $oTargetLog, false);
				} else {
					$modelTag->update(
						'xxt_enroll_tag_target',
						['last_assign_at' => $current, 'assign_num' => (object) ['op' => '+=', 'pat' => 1]],
						['id' => $oTargetLog->id]
					);
				}
				if ($oUserTag) {
					$modelTag->update(
						'xxt_enroll_user_tag',
						['assign_num' => (object) ['op' => '+=', 'pat' => 1]],
						['id' => $oUserTag->id]
					);
				}
				$modelTag->update(
					'xxt_enroll_tag',
					['assign_num' => (object) ['op' => '+=', 'pat' => 1]],
					['id' => $tagId]
				);
			}
		}

		/* 删除不再保留的标签 */
		if (count($aDelTagIds)) {
			foreach ($aDelTagIds as $tagId) {
				$oAssignLog = $modelTag->query_obj_ss(['id,tag_id,user_tag_id,assign_at', 'xxt_enroll_tag_assign', ['target_id' => $oRecord->id, 'target_type' => 1, 'tag_id' => $tagId, 'userid' => $oUser->uid]]);
				if (false === $oAssignLog) {
					continue;
				}
				$modelTag->delete('xxt_enroll_tag_assign', ['id' => $oAssignLog->id]);
				/**
				 * 更新汇总数据
				 */
				$oTargetLog = $modelTag->logByTarget($oAssignLog->tag_id, (object) ['id' => $oRecord->id, 'type' => 1], ['fields' => 'id,first_assign_at,last_assign_at,assign_num']);
				if ($oTargetLog) {
					if ((int) $oTargetLog->assign_num === 1) {
						$modelTag->delete('xxt_enroll_tag_target', ['id' => $oTargetLog->id]);
					} else {
						$oUpdated = [
							'assign_num' => (object) ['op' => '-=', 'pat' => 1],
						];
						if ($oTargetLog->first_assign_at === $oAssignLog->assign_at) {
							$assignAt = $modelTag->query_obj_ss(['min(assign_at)', 'xxt_enroll_tag_assign', ['target_id' => $oRecord->id, 'target_type' => 1]]);
							$oUpdated['first_assign_at'] = $assignAt;
						} else if ($oTargetLog->last_assign_at === $oAssignLog->assign_at) {
							$assignAt = $modelTag->query_obj_ss(['max(assign_at)', 'xxt_enroll_tag_assign', ['target_id' => $oRecord->id, 'target_type' => 1]]);
							$oUpdated['last_assign_at'] = $assignAt;
						}
						$modelTag->update(
							'xxt_enroll_tag_target',
							$oUpdated,
							['id' => $oTargetLog->id]
						);
					}
				}
				if (!empty($oAssignLog->user_tag_id)) {
					$modelTag->update(
						'xxt_enroll_user_tag',
						['assign_num' => (object) ['op' => '-=', 'pat' => '1']],
						['id' => $oAssignLog->user_tag_id]
					);
				}
				$modelTag->update(
					'xxt_enroll_tag',
					['assign_num' => (object) ['op' => '-=', 'pat' => '1']],
					['id' => $oAssignLog->tag_id]
				);
			}
		}

		/* 更新后的所有用户标签 */
		$oAfterTags = $modelTag->byRecord($oRecord, $oUser);

		return new \ResponseData($oAfterTags);
	}
}