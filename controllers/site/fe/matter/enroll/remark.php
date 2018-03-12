<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动登记记录评论
 */
class remark extends base {
	/**
	 * 返回一条登记记录的所有评论
	 */
	public function list_action($ek, $schema = '', $data = '', $page = 1, $size = 99) {
		$oUser = $this->who;

		$modelRem = $this->model('matter\enroll\remark');
		$options = [];
		if (!empty($data)) {
			$options['data_id'] = $modelRem->escape($data);
		}

		$result = $modelRem->listByRecord($oUser, $ek, $schema, $page, $size, $options);

		return new \ResponseData($result);
	}
	/**
	 * 返回多项填写题的所有评论
	 */
	public function listMultitext_action($ek, $schema, $page = 1, $size = 99) {
		if (empty($schema)) {
			return new \ResponseError('没有指定题目id');
		}

		$oUser = $this->who;
		$oRecDatas = $this->model('matter\enroll\data')->getMultitext($ek, $schema, ['fields' => 'id,multitext_seq,agreed,value,like_num,like_log,remark_num,supplement,tag,multitext_seq']);

		$options = [];
		if (count($oRecDatas)) {
			$data_ids = [];
			foreach ($oRecDatas as $oRecData) {
				$data_ids[] = $oRecData->id;
			}
			$options['data_id'] = $data_ids;
		}

		$result = $this->model('matter\enroll\remark')->listByRecord($oUser, $ek, $schema, $page, $size, $options);

		$result->data = $oRecDatas;

		return new \ResponseData($result);
	}
	/**
	 * 给指定的登记记录的添加评论
	 * 进行评论操作的用户需满足进入活动规则的条件
	 *
	 * @param $remark 被评论的评论
	 *
	 */
	public function add_action($ek, $data, $remark = 0) {
		$ek = $this->escape($ek);
		$recDataId = $this->escape($data);

		$modelRec = $this->model('matter\enroll\record');
		$modelRecData = $this->model('matter\enroll\data');

		$oRecord = $modelRec->byId($ek);
		if (false === $oRecord && $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}
		if (!empty($recDataId)) {
			$oRecordData = $modelRecData->byId($recDataId);
			if (false === $oRecordData && $oRecordData->state !== '1') {
				return new \ObjectNotFoundError();
			}
		}

		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($oRecord->aid, ['cascaded' => 'N']);
		if (false === $oApp && $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		/* 操作规则 */
		$oEntryRuleResult = $this->checkEntryRule2($oApp);
		if (isset($oEntryRuleResult->passed) && $oEntryRuleResult->passed === 'N') {
			return new \ComplianceError('用户身份不符合进入规则，无法发表评论');
		}

		$oPosted = $this->getPostJson();
		if (empty($oPosted->content)) {
			return new \ResponseError('评论内容不允许为空');
		}

		/* 发表评论的用户 */
		$oUser = $this->getUser($oApp);

		$current = time();
		$oRemark = new \stdClass;
		$oRemark->siteid = $oRecord->siteid;
		$oRemark->aid = $oRecord->aid;
		$oRemark->rid = $oRecord->rid;
		$oRemark->userid = $oUser->uid;
		$oRemark->group_id = isset($oUser->group_id) ? $oUser->group_id : '';
		$oRemark->user_src = 'S';
		$oRemark->nickname = $modelRec->escape($oUser->nickname);
		$oRemark->enroll_key = $ek;
		$oRemark->enroll_group_id = $oRecord->group_id;
		$oRemark->enroll_userid = $oRecord->userid;
		$oRemark->schema_id = isset($oRecordData) ? $oRecordData->schema_id : '';
		$oRemark->data_id = $recDataId;
		$oRemark->remark_id = $modelRec->escape($remark);
		$oRemark->create_at = $current;
		$oRemark->content = $modelRec->escape($oPosted->content);

		$oRemark->id = $modelRec->insert('xxt_enroll_record_remark', $oRemark, true);

		$modelRec->update("update xxt_enroll_record set remark_num=remark_num+1 where enroll_key='$ek'");
		if (!empty($recDataId)) {
			$modelRec->update("update xxt_enroll_record_data set remark_num=remark_num+1,last_remark_at=$current where id = " . $recDataId);
			// 如果每一条的数据呗评论了那么这道题的总数据+1
			if ($oRecordData->multitext_seq != 0) {
				$modelRec->update("update xxt_enroll_record_data set remark_num=remark_num+1,last_remark_at=$current where enroll_key='$ek' and schema_id='{$oRecordData->schema_id}' and multitext_seq = 0");
			}
		}

		/* 更新用户汇总数据 */
		if (!empty($recDataId)) {
			$this->model('matter\enroll\event')->remarkRecData($oApp, $oRecordData, $oUser);
		} else {
			$this->model('matter\enroll\event')->remarkRecord($oApp, $oRecord, $oUser);
		}

		$this->_notifyHasRemark($oApp, $oRecord, $oRemark);

		return new \ResponseData($oRemark);
	}
	/**
	 * 通知评论登记记录事件
	 */
	private function _notifyHasRemark($oApp, $oRecord, $oRemark) {
		/* 模板消息参数 */
		$notice = $this->model('site\notice')->byName($oApp->siteid, 'site.enroll.remark');
		if ($notice === false) {
			return false;
		}
		$tmplConfig = $this->model('matter\tmplmsg\config')->byId($notice->tmplmsg_config_id, ['cascaded' => 'Y']);
		if (!isset($tmplConfig->tmplmsg)) {
			return false;
		}

		$params = new \stdClass;
		foreach ($tmplConfig->tmplmsg->params as $param) {
			if (!isset($tmplConfig->mapping->{$param->pname})) {
				continue;
			}
			$mapping = $tmplConfig->mapping->{$param->pname};
			if (isset($mapping->src)) {
				if ($mapping->src === 'matter') {
					if (isset($oApp->{$mapping->id})) {
						$value = $oApp->{$mapping->id};
					} else if ($mapping->id === 'event_at') {
						$value = date('Y-m-d H:i:s');
					}
				} else if ($mapping->src === 'text') {
					$value = $mapping->name;
				}
			}
			!isset($value) && $value = '';
			$params->{$param->pname} = $value;
		}
		/**
		 * 给记录的提交人发送通知
		 */
		if ($oRecord->userid !== $oRemark->userid) {
			/* 发送给登记记录的提交人 */
			$noticeURL = $this->model('matter\enroll')->getEntryUrl($oApp->siteid, $oApp->id);
			$noticeURL .= '&page=remark&ek=' . $oRecord->enroll_key;
			$noticeURL .= '&schema=' . $oRemark->schema_id;
			$params->url = $noticeURL;

			/* 消息的接收人 */
			$oEnroller = new \stdClass;
			$oEnroller->assoc_with = $oRecord->enroll_key;
			$oEnroller->userid = $oRecord->userid;

			/* 消息的创建人 */
			$oCreator = new \stdClass;
			$oCreator->uid = $oRemark->userid;
			$oCreator->name = $oRemark->nickname;
			$oCreator->src = 'site';

			/* 给用户发通知消息 */
			$modelTmplBat = $this->model('matter\tmplmsg\batch');
			$modelTmplBat->send($oRecord->siteid, $tmplConfig->msgid, $oCreator, [$oEnroller], $params, ['send_from' => 'enroll:' . $oRecord->aid . ':' . $oRecord->enroll_key]);
		}
		/**
		 * 给活动管理员发送通知
		 */
		$receivers = $this->model('matter\enroll\receiver')->byApp($oApp->siteid, $oApp->id);
		if (count($receivers)) {
			/* 获得活动的管理员链接 */
			$appURL = $this->model('matter\enroll')->getOpUrl($oApp->siteid, $oApp->id);
			$modelQurl = $this->model('q\url');
			$noticeURL = $modelQurl->urlByUrl($oApp->siteid, $appURL);
			$params->url = $noticeURL;

			/* 发送消息 */
			foreach ($receivers as &$receiver) {
				if (!empty($receiver->sns_user)) {
					$snsUser = json_decode($receiver->sns_user);
					if (isset($snsUser->src) && isset($snsUser->openid)) {
						$receiver->{$snsUser->src . '_openid'} = $snsUser->openid;
					}
				}
			}
			/* 发送给活动管理员 */
			$modelTmplBat = $this->model('matter\tmplmsg\plbatch');
			$modelTmplBat->send($oApp->siteid, $tmplConfig->msgid, $receivers, $params, ['event_name' => 'site.enroll.remark', 'send_from' => 'enroll:' . $oApp->id . ':' . $oRemark->enroll_key]);
		}

		return true;
	}
	/**
	 * 点赞登记记录中的某一个评论
	 *
	 * @param string $remark remark'id
	 *
	 */
	public function like_action($remark) {
		$remark = $this->escape($remark);

		$modelRem = $this->model('matter\enroll\remark');
		$oRemark = $modelRem->byId($remark, ['fields' => 'id,aid,rid,userid,like_log']);
		if (false === $oRemark) {
			return new \ObjectNotFoundError();
		}

		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($oRemark->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$oLikeLog = $oRemark->like_log;

		$oUser = $this->getUser($oApp);
		if (isset($oLikeLog->{$oUser->uid})) {
			unset($oLikeLog->{$oUser->uid});
			$incLikeNum = -1;
		} else {
			$oLikeLog->{$oUser->uid} = time();
			$incLikeNum = 1;
		}
		$likeNum = count(get_object_vars($oLikeLog));

		$modelRem->update(
			'xxt_enroll_record_remark',
			['like_log' => json_encode($oLikeLog), 'like_num' => $likeNum],
			['id' => $oRemark->id]
		);

		$modelEnlEvt = $this->model('matter\enroll\event');
		if ($incLikeNum > 0) {
			/* 发起点赞 */
			$modelEnlEvt->likeRemark($oApp, $oRemark, $oUser);
			/* 被点赞 */
			$modelEnlEvt->belikedRemark($oApp, $oRemark, $oUser);
		} else {
			/* 撤销发起点赞 */
			$modelEnlEvt->undoLikeRemark($oApp, $oRemark, $oUser);
			/* 撤销被点赞 */
			$modelEnlEvt->undoBeLikedRemark($oApp, $oRemark, $oUser);
		}

		return new \ResponseData(['like_log' => $oLikeLog, 'like_num' => $likeNum]);
	}
}