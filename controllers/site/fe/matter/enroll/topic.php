<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记记录专题
 */
class topic extends base {
	/**
	 *
	 */
	public function get_action($topic) {
		$modelTop = $this->model('matter\enroll\topic', null);
		$oTopic = $modelTop->byId($topic, ['fields' => 'id,siteid,aid,state,unionid,userid,group_id,nickname,create_at,title,summary,rec_num,share_in_group,task_id']);
		if (false === $oTopic || $oTopic->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oApp = $this->model('matter\enroll')->byId($oTopic->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		/* 活动任务的专题 */
		if (!empty($oTopic->task_id)) {
			$modelTsk = $this->model('matter\enroll\task', $oApp);
			$oTask = $modelTsk->byId($oTopic->task_id);
			if ($oTask) {
				$oTopic->task = $oTask;
			}
		}

		$oUser = $this->getUser($oApp);

		/* 修改默认访客昵称 */
		if ($this->getDeepValue($oUser, 'unionid') === $oTopic->unionid) {
			$oTopic->nickname = '我';
		} else {
			$oEditorGrp = $this->getEditorGroup($oApp);
			$this->setNickname($oTopic, $oUser, $oEditorGrp);
		}

		return new \ResponseData($oTopic);
	}
	/**
	 * 专题的概要信息
	 */
	public function sketch_action($topic) {
		$modelTop = $this->model('matter\enroll\topic', null);

		$oSketch = new \stdClass;
		$oTopic = $modelTop->byId($topic, ['fields' => 'id,state,aid,userid,group_id,nickname,title,summary,rec_num']);
		if ($oTopic) {
			$modelApp = $this->model('matter\enroll');
			$oApp = $modelApp->byId($oTopic->aid, ['fields' => 'title', 'cascaded' => 'N']);
			$oSketch->raw = $oTopic;
			$oSketch->title = $oTopic->title . '|' . $oApp->title;
		}

		return new \ResponseData($oSketch);
	}
	/**
	 * 创建记录专题
	 */
	public function add_action($app) {
		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->getUser($oApp);
		if (empty($oUser->unionid)) {
			return new \ResponseError('仅支持注册用户创建，请登录后再进行此操作');
		}

		$oPosted = $this->getPostJson();

		$current = time();
		$oNewTopic = new \stdClass;
		$oNewTopic->aid = $oApp->id;
		$oNewTopic->siteid = $oApp->siteid;
		$oNewTopic->unionid = $oUser->unionid;
		$oNewTopic->userid = $oUser->uid;
		$oNewTopic->group_id = isset($oUser->group_id) ? $oUser->group_id : '';
		$oNewTopic->nickname = $modelEnl->escape($oUser->nickname);
		$oNewTopic->create_at = $current;
		$oNewTopic->title = empty($oPosted->title) ? $oNewTopic->nickname . '的专题（' . date('y年n月d日', $current) . '）' : $modelEnl->escape($oPosted->title);
		$oNewTopic->summary = empty($oPosted->summary) ? $oNewTopic->title : $modelEnl->escape($oPosted->summary);
		$oNewTopic->rec_num = 0;
		$oNewTopic->id = $modelEnl->insert('xxt_enroll_topic', $oNewTopic, true);

		// 处理用户数据
		if ($activeRound = $this->model('matter\enroll\round')->getActive($oApp)) {
			$rid = $activeRound->rid;
		} else {
			$rid = '';
		}
		$oUsrEventData = new \stdClass;
		$oUsrEventData->topic_num = 1;
		$oUsrEventData->last_topic_at = $current;
		$this->model('matter\enroll\event')->_updateUsrData($oApp, $rid, false, $oUser, $oUsrEventData);

		return new \ResponseData($oNewTopic);
	}
	/**
	 * 创建记录专题
	 */
	public function update_action($topic) {
		$modelTop = $this->model('matter\enroll\topic', null);
		$oTopic = $modelTop->byId($topic, ['fields' => 'id,unionid,state,aid,group_id,title']);
		if (false === $oTopic || $oTopic->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$oApp = $this->model('matter\enroll')->byId($oTopic->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->getUser($oApp);
		if (!isset($oUser->group_id)) {
			$oUser->group_id = '';
		}
		if (empty($oUser->unionid)) {
			return new \ResponseError('仅支持注册用户创建，请登录后再进行此操作');
		}

		$oPosted = $this->getPostJson();
		if (empty((array) $oPosted)) {
			return new \ResponseError('没有指定要更新的数据（1）');
		}

		if ($oUser->unionid === $oTopic->unionid && $oUser->group_id !== $oTopic->group_id) {
			$oPosted->group_id = $oUser->group_id;
			if (empty($oUser->group_id)) {
				$oPosted->share_in_group = 'N';
			}
		}

		$aUpdated = [];
		foreach ($oPosted as $prop => $val) {
			switch ($prop) {
			case 'title':
			case 'summary':
			case 'group_id':
				$aUpdated[$prop] = $modelTop->escape($val);
				break;
			case 'share_in_group':
			case 'is_public':
				$aUpdated[$prop] = in_array($val, ['Y', 'N']) ? $val : 'N';
				break;
			}
		}
		if (empty($aUpdated)) {
			return new \ResponseError('没有指定要更新的数据（2）');
		}

		$rst = $modelTop->update('xxt_enroll_topic', $aUpdated, ['id' => $topic]);

		return new \ResponseData($rst);
	}
	/**
	 * 删除专题
	 */
	public function remove_action($topic) {
		$oUser = $this->who;
		if (empty($oUser->unionid)) {
			return new \ResponseError('仅支持注册用户创建，请登录后再进行此操作');
		}

		$modelTop = $this->model('matter\enroll\topic', null);
		$rst = $modelTop->update('xxt_enroll_topic', ['state' => 0], ['id' => $topic]);

		return new \ResponseData($rst);
	}
	/**
	 * 专题列表
	 */
	public function list_action($app) {
		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->getUser($oApp);
		if (empty($oUser->unionid)) {
			return new \ResponseError('仅支持注册用户创建，请登录后再进行此操作');
		}
		$w = "state=1 and aid='{$oApp->id}'";
		$w .= " and (";
		$w .= "unionid='$oUser->unionid'";
		if (isset($oUser->group_id)) {
			$w .= " or (share_in_group='Y' and group_id='{$oUser->group_id}')";
		}
		$w .= ")";
		$q = [
			'id,create_at,title,summary,rec_num,userid,group_id,nickname,share_in_group,is_public',
			'xxt_enroll_topic',
			$w,
		];
		$q2 = ['o' => 'create_at desc'];
		$topics = $modelEnl->query_objs_ss($q, $q2);
		foreach ($topics as $oTopic) {
			if ($oTopic->userid === $oUser->uid) {
				$oTopic->nickname = '我';
			}
		}
		$oResult = new \stdClass;
		$oResult->topics = $topics;
		$oResult->total = count($topics);

		return new \ResponseData($oResult);
	}
	/**
	 * 公共专题列表
	 */
	public function listPublic_action($app) {
		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$q = [
			'id,create_at,title,summary,rec_num,userid,group_id,nickname,share_in_group,is_public',
			'xxt_enroll_topic',
			['state' => 1, 'aid' => $oApp->id, 'is_public' => 'Y'],
		];
		$q2 = ['o' => 'create_at desc'];
		$topics = $modelEnl->query_objs_ss($q, $q2);

		$oResult = new \stdClass;
		$oResult->topics = $topics;
		$oResult->total = count($topics);

		return new \ResponseData($oResult);
	}
	/**
	 * 指定记录的主题
	 */
	public function byRecord_action($record) {
		$oUser = $this->who;
		if (empty($oUser->unionid)) {
			return new \ResponseError('仅支持注册用户创建，请登录后再进行此操作');
		}

		$modelRec = $this->model('matter\enroll\record');
		$q = ['id,state', 'xxt_enroll_record', ['id' => $record]];
		$oRecord = $modelRec->query_obj_ss($q);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$q = ['id,topic_id', 'xxt_enroll_topic_record', ['record_id' => $oRecord->id]];
		$topics = $modelRec->query_objs_ss($q);

		return new \ResponseData($topics);
	}
	/**
	 * 指定记录的专题
	 *
	 * @param int $task 放入指定任务的专题
	 */
	public function assign_action($record, $data = null, $task = null) {
		$modelRec = $this->model('matter\enroll\record');
		$q = ['id,aid,siteid,state', 'xxt_enroll_record', ['id' => $record]];
		$oRecord = $modelRec->query_obj_ss($q);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError('指定的记录不存在或不可用');
		}
		$oApp = $this->model('matter\enroll')->byId($oRecord->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$modelTop = $this->model('matter\enroll\topic', $oApp);
		if (isset($task)) {
			$modelTsk = $this->model('matter\enroll\task', $oApp);
			$oTask = $modelTsk->byId($task);
			if (false === $oTask || $oTask->state !== 'IP') {
				return new \ObjectNotFoundError('指定的任务不存在或不可用（1）');
			}
			$oTopic = $modelTop->byTask($oTask);
			if (false === $oTopic) {
				return new \ObjectNotFoundError('指定的任务不存在或不可用（1）');
			}
			$aAfterTopicIds = [$oTopic->id];
		} else {
			$oPosted = $this->getPostJson();
			$aAfterTopicIds = $oPosted->topic;
			if (!isset($aAfterTopicIds) || !is_array($aAfterTopicIds)) {
				return new \ParameterError('没有指定专题');
			}
		}

		$oUser = $this->who;
		if (empty($oUser->unionid)) {
			return new \ResponseError('仅支持注册用户创建，请登录后再进行此操作');
		}

		if (!empty($data)) {
			$modelDat = $this->model('matter\enroll\data');
			$oRecData = $modelDat->byId($data, ['fields' => '*']);
			if (false === $oRecData || $oRecData->state !== '1') {
				return new \ObjectNotFoundError('指定的数据不存在或不可用');
			}
		}

		$q = ['topic_id', 'xxt_enroll_topic_record', ['record_id' => $oRecord->id]];
		$q[2]['data_id'] = isset($oRecData) ? $oRecData->id : 0;

		$aBeforeTopicIds = $modelRec->query_vals_ss($q);

		$countOfNew = 0;

		if (empty($aAfterTopicIds) || empty($aBeforeTopicIds)) {
			/* 更新后和更新前的专题没有交集 */
			$aNewTopicIds = $aAfterTopicIds;
			$aDelTopicIds = $aBeforeTopicIds;
		} else {
			/* 更新后和更新前的专题有交集 */
			$aNewTopicIds = array_diff($aAfterTopicIds, $aBeforeTopicIds);
			$aDelTopicIds = array_diff($aBeforeTopicIds, $aAfterTopicIds);
		}

		/* 新指定的专题 */
		if (count($aNewTopicIds)) {
			$oProtoNewRel = new \stdClass;
			$oProtoNewRel->aid = $oRecord->aid;
			$oProtoNewRel->siteid = $oRecord->siteid;
			$oProtoNewRel->record_id = $oRecord->id;
			isset($oRecData) && $oProtoNewRel->data_id = $oRecData->id;
			$oProtoNewRel->assign_at = time();
			foreach ($aNewTopicIds as $topicId) {
				$oProtoNewRel->topic_id = $topicId;
				$oTopic = $modelTop->byId($topicId, 'id,state,rec_num');
				if (false === $oTopic || $oTopic->state !== '1') {
					continue;
				}
				$oProtoNewRel->seq = (int) $oTopic->rec_num + 1;
				$modelTop->insert('xxt_enroll_topic_record', $oProtoNewRel, false);
				$modelTop->update(
					'xxt_enroll_topic',
					['rec_num' => (object) ['op' => '+=', 'pat' => 1]],
					['id' => $topicId]
				);
			}
		}

		/* 删除不再保留的专题 */
		if (count($aDelTopicIds)) {
			foreach ($aDelTopicIds as $topicId) {
				$oRecInTop = $modelTop->query_obj_ss(['id,seq', 'xxt_enroll_topic_record', ['record_id' => $oRecord->id, 'topic_id' => $topicId]]);
				if (false === $oRecInTop) {
					continue;
				}
				$modelTop->delete('xxt_enroll_topic_record', ['id' => $oRecInTop->id]);
				/* 修改其他记录的序号 */
				$modelTop->update(
					'xxt_enroll_topic_record',
					['seq' => (object) ['op' => '-=', 'pat' => '1']],
					['topic_id' => $topicId, 'seq' => (object) ['op' => '>', 'pat' => $oRecInTop->seq]]
				);
				$modelTop->update(
					'xxt_enroll_topic',
					['rec_num' => (object) ['op' => '-=', 'pat' => 1]],
					['id' => $topicId]
				);
			}
		}

		return new \ResponseData(count($aNewTopicIds));
	}
	/**
	 * 更新专题中记录的排序
	 */
	public function updateSeq_action($topic) {
		$oUser = $this->who;
		if (empty($oUser->unionid)) {
			return new \ResponseError('仅支持注册用户修改，请登录后再进行此操作');
		}
		$oPosted = $this->getPostJson();
		if (empty($oPosted->record) || empty($oPosted->step)) {
			return new \ParameterError();
		}

		$modelTop = $this->model('matter\enroll\topic', null);
		$oTopic = $modelTop->byId($topic, ['fields' => 'id,state,rec_num']);
		if (false === $oTopic || $oTopic->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$q = ['id,seq', 'xxt_enroll_topic_record', ['record_id' => $modelTop->escape($oPosted->record), 'topic_id' => $oTopic->id]];
		$q[2]['data_id'] = 0;
		$oRecInTop = $modelTop->query_obj_ss($q);
		if (false === $oRecInTop) {
			return new \ObjectNotFoundError();
		}

		$oRecInTop->seq = (int) $oRecInTop->seq;
		$moveStep = (int) $oPosted->step;
		$afterSeq = $oRecInTop->seq + $moveStep;
		if ($afterSeq <= 0 || $afterSeq > $oTopic->rec_num) {
			return new \ParameterError('指定位置超出范围');
		}

		if ($moveStep < 0) {
			$modelTop->update(
				'xxt_enroll_topic_record',
				['seq' => (object) ['op' => '+=', 'pat' => 1]],
				['topic_id' => $oTopic->id, 'seq' => (object) ['op' => 'between', 'pat' => [$afterSeq, $oRecInTop->seq - 1]]]
			);
		} else {
			$modelTop->update(
				'xxt_enroll_topic_record',
				['seq' => (object) ['op' => '-=', 'pat' => 1]],
				['topic_id' => $oTopic->id, 'seq' => (object) ['op' => 'between', 'pat' => [$oRecInTop->seq + 1, $afterSeq]]]
			);
		}

		$rst = $modelTop->update(
			'xxt_enroll_topic_record',
			['seq' => $afterSeq],
			['id' => $oRecInTop->id]
		);

		return new \ResponseData($rst);
	}
	/**
	 * 将记录从专题中删除
	 */
	public function removeRec_action($topic) {
		$oUser = $this->who;
		if (empty($oUser->unionid)) {
			return new \ResponseError('仅支持注册用户修改，请登录后再进行此操作');
		}
		$oPosted = $this->getPostJson();
		if (empty($oPosted->id_in_topic)) {
			return new \ParameterError();
		}
		$modelTop = $this->model('matter\enroll\topic', null);
		$oTopic = $modelTop->byId($topic, ['fields' => 'id,state']);
		if (false === $oTopic || $oTopic->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$q = ['id,seq', 'xxt_enroll_topic_record', ['id' => $modelTop->escape($oPosted->id_in_topic), 'topic_id' => $oTopic->id]];
		$oRecInTop = $modelTop->query_obj_ss($q);
		if (false === $oRecInTop) {
			return new \ObjectNotFoundError();
		}

		$rst = $modelTop->delete('xxt_enroll_topic_record', ['id' => $oRecInTop->id]);

		$modelTop->update(
			'xxt_enroll_topic_record',
			['seq' => (object) ['op' => '-=', 'pat' => 1]],
			['topic_id' => $oTopic->id, 'seq' => (object) ['op' => '>', 'pat' => $oRecInTop->seq]]
		);

		$modelTop->update(
			'xxt_enroll_topic',
			['rec_num' => (object) ['op' => '-=', 'pat' => 1]],
			['id' => $oTopic->id]
		);

		return new \ResponseData($rst);
	}
}