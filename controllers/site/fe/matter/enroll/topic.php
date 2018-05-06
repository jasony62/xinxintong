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
		$oUser = $this->who;
		if (empty($oUser->unionid)) {
			return new \ResponseError('仅支持注册用户创建，请登录后再进行此操作');
		}

		$modelTop = $this->model('matter\enroll\topic');
		$oTopic = $modelTop->byId($topic);
		if (false === $oTopic || $oTopic->state !== '1') {
			return new \ObjectNotFoundError();
		}

		return new \ResponseData($oTopic);
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

		$current = time();
		$oNewTopic = new \stdClass;
		$oNewTopic->aid = $oApp->id;
		$oNewTopic->siteid = $oApp->siteid;
		$oNewTopic->unionid = $oUser->unionid;
		$oNewTopic->nickname = $modelEnl->escape($oUser->nickname);
		$oNewTopic->create_at = $current;
		$oNewTopic->title = $oNewTopic->nickname . '的专题（' . date('y年n月d日', $current) . '）';
		$oNewTopic->summary = $oNewTopic->title;
		$oNewTopic->rec_num = 0;
		$oNewTopic->id = $modelEnl->insert('xxt_enroll_topic', $oNewTopic, true);

		return new \ResponseData($oNewTopic);
	}
	/**
	 * 创建记录专题
	 */
	public function update_action($topic) {
		$oUser = $this->who;
		if (empty($oUser->unionid)) {
			return new \ResponseError('仅支持注册用户创建，请登录后再进行此操作');
		}

		$oPosted = $this->getPostJson();
		if (empty((array) $oPosted)) {
			return new \ResponseError('没有指定要更新的数据');
		}

		$modelTop = $this->model('matter\enroll\topic');
		$oUpdated = new \stdClass;
		foreach ($oPosted as $prop => $val) {
			switch ($prop) {
			case 'title':
			case 'summary':
				$oUpdated->{$prop} = $modelTop->escape($val);
				break;
			}
		}

		$rst = $modelTop->update('xxt_enroll_topic', $oUpdated, ['id' => $topic]);

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

		$modelTop = $this->model('matter\enroll\topic');
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

		$q = [
			'id,create_at,title,summary,rec_num',
			'xxt_enroll_topic',
			['aid' => $oApp->id, 'unionid' => $oUser->unionid, 'state' => 1],
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
	 */
	public function assign_action($record) {
		$oPosted = $this->getPostJson();
		$aAfterTopicIds = $oPosted->topic;
		if (!isset($aAfterTopicIds) || !is_array($aAfterTopicIds)) {
			return new \ParameterError('没有指定专题');
		}

		$oUser = $this->who;
		if (empty($oUser->unionid)) {
			return new \ResponseError('仅支持注册用户创建，请登录后再进行此操作');
		}

		$modelRec = $this->model('matter\enroll\record');
		$q = ['id,aid,siteid,state', 'xxt_enroll_record', ['id' => $record]];
		$oRecord = $modelRec->query_obj_ss($q);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$q = ['topic_id', 'xxt_enroll_topic_record', ['record_id' => $oRecord->id]];
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
			$oProtoNewRel->assign_at = time();
			foreach ($aNewTopicIds as $topicId) {
				$oProtoNewRel->topic_id = $topicId;
				$modelRec->insert('xxt_enroll_topic_record', $oProtoNewRel, false);
				$modelRec->update(
					'xxt_enroll_topic',
					['rec_num' => (object) ['op' => '+=', 'pat' => 1]],
					['id' => $topicId]
				);
			}
		}

		/* 删除不再保留的专题 */
		if (count($aDelTopicIds)) {
			foreach ($aDelTopicIds as $topicId) {
				$modelRec->delete('xxt_enroll_topic_record', ['record_id' => $oRecord->id, 'topic_id' => $topicId]);
				$modelRec->update(
					'xxt_enroll_topic',
					['rec_num' => (object) ['op' => '-=', 'pat' => 1]],
					['id' => $topicId]
				);
			}
		}

		return new \ResponseData(count($aNewTopicIds));
	}
}