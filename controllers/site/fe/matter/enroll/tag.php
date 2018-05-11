<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动标签
 */
class tag extends base {
	/**
	 *
	 */
	public function create_action($site, $app) {
		/* 登记活动定义 */
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oPosted = $this->getPostJson();

		$oUser = $this->getUser($oApp);
		$oUser->creater_src = 'S';
		$newTags = $this->model('matter\enroll\tag')->add($oApp, $oUser, $oPosted);

		return new \ResponseData($newTags);
	}
	/**
	 * 获得用户创建的标签
	 */
	public function list_action($app) {
		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$oUser = $this->who;
		if (empty($oUser->unionid)) {
			return new \ResponseError('仅支持注册用户创建标签，请登录后再进行此操作');
		}
		$q = [
			't.id tag_id,t.label,t.public,t.forbidden,e.create_at,e.id user_tag_id',
			'xxt_enroll_tag t inner join xxt_enroll_user_tag e on t.id=e.tag_id',
			['t.aid' => $oApp->id, 'e.userid' => $oUser->uid, 'e.state' => 1],
		];
		$q2 = ['o' => 'e.create_at desc'];
		$userTags = $modelEnl->query_objs_ss($q, $q2);

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
		} else {
			$oEnlTag = new \stdClass;
			$oEnlTag->siteid = $oApp->siteid;
			$oEnlTag->aid = $oApp->id;
			$oEnlTag->label = $modelEnl->escape($label);
			$oEnlTag->assign_num = 0;
			$oEnlTag->user_num = 1;
			$oEnlTag->public = 'N';
			$oEnlTag->forbidden = 'N';
			$oEnlTag->id = $modelEnl->insert('xxt_enroll_tag', $oEnlTag, true);
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
	 *
	 */
	public function byRecord_action($record) {
		$modelRec = $this->model('matter\enroll\record');
		$q = ['id,aid,siteid,state', 'xxt_enroll_record', ['id' => $record]];
		$oRecord = $modelRec->query_obj_ss($q);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$q = [
			't.id tag_id,t.label,t.public,t.forbidden,a.assign_at,a.user_tag_id',
			'xxt_enroll_tag t inner join xxt_enroll_tag_assign a on t.id=a.tag_id',
			['t.aid' => $oRecord->aid, 'a.target_id' => $oRecord->id, 'a.target_type' => 1],
		];
		$q2 = ['o' => 'a.assign_at desc'];

		$recordTags = $modelRec->query_objs_ss($q, $q2);

		return new \ResponseData($recordTags);
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
		$q = ['user_tag_id', 'xxt_enroll_tag_assign', ['target_id' => $oRecord->id, 'target_type' => 1]];
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
			foreach ($aNewTagIds as $userTagId) {
				$oUserTag = $modelTag->byId($userTagId, $oUser, ['fields' => 'id,tag_id,state']);
				if (false === $oUserTag || $oUserTag->state !== '1') {
					continue;
				}
				$oProtoNewRel->tag_id = $oUserTag->tag_id;
				$oProtoNewRel->user_tag_id = $oUserTag->id;

				$modelTag->insert('xxt_enroll_tag_assign', $oProtoNewRel, false);
				/**
				 * 记录汇总数据
				 */
				$oTargetLog = $modelTag->logByTarget($oUserTag->tag_id, (object) ['id' => $oRecord->id, 'type' => 1], ['fields' => 'id']);
				if (false === $oTargetLog) {
					$oTargetLog = new \stdClass;
					$oTargetLog->aid = $oRecord->aid;
					$oTargetLog->siteid = $oRecord->siteid;
					$oTargetLog->tag_id = $oUserTag->tag_id;
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
				$modelTag->update(
					'xxt_enroll_user_tag',
					['assign_num' => (object) ['op' => '+=', 'pat' => 1]],
					['id' => $oUserTag->id]
				);
				$modelTag->update(
					'xxt_enroll_tag',
					['assign_num' => (object) ['op' => '+=', 'pat' => 1]],
					['id' => $oUserTag->tag_id]
				);
			}
		}

		/* 删除不再保留的标签 */
		if (count($aDelTagIds)) {
			foreach ($aDelTagIds as $userTagId) {
				$oAssignLog = $modelTag->query_obj_ss(['id,tag_id,user_tag_id,assign_at', 'xxt_enroll_tag_assign', ['target_id' => $oRecord->id, 'target_type' => 1, 'user_tag_id' => $userTagId]]);
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
				/* 修改其他记录的序号 */
				$modelTag->update(
					'xxt_enroll_user_tag',
					['assign_num' => (object) ['op' => '-=', 'pat' => '1']],
					['id' => $oAssignLog->user_tag_id]
				);
				$modelTag->update(
					'xxt_enroll_tag',
					['assign_num' => (object) ['op' => '-=', 'pat' => '1']],
					['id' => $oAssignLog->tag_id]
				);
			}
		}

		/* 更新后的所有标签 */
		$oAfterTags = $modelTag->byRecord($oRecord, $oUser);

		return new \ResponseData($oAfterTags);
	}
}