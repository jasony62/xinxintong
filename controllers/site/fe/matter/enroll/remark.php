<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动登记记录留言
 */
class remark extends base {
	/**
	 * 返回一条登记记录的所有留言
	 */
	public function list_action($ek, $schema = '', $data = '', $page = 1, $size = 99) {
		$modelRec = $this->model('matter\enroll\record');
		$oRecord = $modelRec->byId($ek, ['aid,state']);
		if (false === $oRecord && $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($oRecord->aid, ['cascaded' => 'N']);
		if (false === $oApp && $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->getUser($oApp);

		$modelRem = $this->model('matter\enroll\remark');
		$aOptions = [];
		if (!empty($data)) {
			$aOptions['data_id'] = $data;
		}

		$result = $modelRem->listByRecord($oUser, $ek, $schema, $page, $size, $aOptions);

		return new \ResponseData($result);
	}
	/**
	 * 返回多项填写题的所有留言
	 */
	public function listMultitext_action($ek, $schema, $page = 1, $size = 99) {
		if (empty($schema)) {
			return new \ResponseError('没有指定题目id');
		}
		$modelRec = $this->model('matter\enroll\record');
		$oRecord = $modelRec->byId($ek, ['aid,state']);
		if (false === $oRecord && $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($oRecord->aid, ['cascaded' => 'N']);
		if (false === $oApp && $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->getUser($oApp);

		$oRecDatas = $this->model('matter\enroll\data')->getMultitext($ek, $schema, ['fields' => 'id,multitext_seq,agreed,value,like_num,like_log,remark_num,supplement,tag,multitext_seq']);

		$aOptions = [];
		if (count($oRecDatas)) {
			$data_ids = [];
			foreach ($oRecDatas as $oRecData) {
				$data_ids[] = $oRecData->id;
			}
			$aOptions['data_id'] = $data_ids;
		}

		$result = $this->model('matter\enroll\remark')->listByRecord($oUser, $ek, $schema, $page, $size, $aOptions);

		$result->data = $oRecDatas;

		return new \ResponseData($result);
	}
	/**
	 * 给指定的登记记录的添加留言
	 * 进行留言操作的用户需满足进入活动规则的条件
	 *
	 * @param $remark 被留言的留言
	 *
	 */
	public function add_action($ek, $data = 0, $remark = 0) {
		$recDataId = $data;

		$modelRec = $this->model('matter\enroll\record');
		$modelRecData = $this->model('matter\enroll\data');

		$oRecord = $modelRec->byId($ek);
		if (false === $oRecord && $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}
		if (!empty($recDataId)) {
			$oRecData = $modelRecData->byId($recDataId);
			if (false === $oRecData && $oRecData->state !== '1') {
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
			return new \ComplianceError('用户身份不符合进入规则，无法发表留言');
		}

		$oPosted = $this->getPostJson();
		if (empty($oPosted->content)) {
			return new \ResponseError('留言内容不允许为空');
		}

		/* 发表留言的用户 */
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
		$oRemark->schema_id = isset($oRecData) ? $oRecData->schema_id : '';
		$oRemark->data_id = empty($recDataId) ? 0 : $recDataId;
		$oRemark->remark_id = $remark;
		$oRemark->create_at = $current;
		$oRemark->content = $modelRec->escape($oPosted->content);
		/* 如果记录是讨论状态，留言也是讨论状态 */
		if (isset($oRecord->agreed) && $oRecord->agreed === 'D') {
			$oRemark->agreed = 'D';
		}

		$oRemark->id = $modelRec->insert('xxt_enroll_record_remark', $oRemark, true);

		$modelRec->update("update xxt_enroll_record set remark_num=remark_num+1 where enroll_key='$ek'");
		if (!empty($recDataId)) {
			$modelRec->update("update xxt_enroll_record_data set remark_num=remark_num+1,last_remark_at=$current where id = " . $recDataId);
			// 如果每一条的数据呗留言了那么这道题的总数据+1
			if ($oRecData->multitext_seq != 0) {
				$modelRec->update("update xxt_enroll_record_data set remark_num=remark_num+1,last_remark_at=$current where enroll_key='$ek' and schema_id='{$oRecData->schema_id}' and multitext_seq = 0");
			}
		}

		/* 更新用户汇总数据 */
		if (!empty($recDataId)) {
			foreach ($oApp->dataSchemas as $dataSchema) {
				if ($dataSchema->id === $oRecData->schema_id) {
					$oDataSchema = $dataSchema;
					break;
				}
			}
			if (isset($oDataSchema->cowork) && $oDataSchema->cowork === 'Y') {
				$this->model('matter\enroll\event')->remarkCowork($oApp, $oRecData, $oUser);
			} else {
				$this->model('matter\enroll\event')->remarkRecData($oApp, $oRecData, $oUser);
			}
		} else {
			$this->model('matter\enroll\event')->remarkRecord($oApp, $oRecord, $oUser);
		}

		$this->_notifyHasRemark($oApp, $oRecord, $oRemark);

		return new \ResponseData($oRemark);
	}
	/**
	 * 通知留言登记记录事件
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
	 * 点赞登记记录中的某一个留言
	 *
	 * @param string $remark remark'id
	 *
	 */
	public function like_action($remark) {
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
		} else {
			/* 撤销发起点赞 */
			$modelEnlEvt->undoLikeRemark($oApp, $oRemark, $oUser);
		}

		return new \ResponseData(['like_log' => $oLikeLog, 'like_num' => $likeNum]);
	}
	/**
	 * 组长对留言表态
	 */
	public function agree_action($remark, $value = '') {
		$modelRem = $this->model('matter\enroll\remark');
		$oRemark = $modelRem->byId($remark, ['fields' => 'id,aid,rid,userid,agreed,agreed_log']);
		if (false === $oRemark) {
			return new \ObjectNotFoundError();
		}

		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($oRemark->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->getUser($oApp);

		$modelGrpUsr = $this->model('matter\group\player');
		/* 当前用户所属分组及角色 */
		$oGrpLeader = $modelGrpUsr->byUser($oApp->entryRule->group, $oUser->uid, ['fields' => 'is_leader,round_id', 'onlyOne' => true]);
		if (false === $oGrpLeader || !in_array($oGrpLeader->is_leader, ['Y', 'S'])) {
			return new \ParameterError('只允许组长进行推荐');
		}
		/* 填写记录用户所属分组 */
		if ($oGrpLeader->is_leader === 'Y') {
			$oGrpMemb = $modelGrpUsr->byUser($oApp->entryRule->group, $oRemark->userid, ['fields' => 'round_id', 'onlyOne' => true]);
			if (false === $oGrpMemb || $oGrpMemb->round_id !== $oGrpLeader->round_id) {
				return new \ParameterError('只允许组长推荐本组数据');
			}
		}

		if (!in_array($value, ['Y', 'N', 'A'])) {
			$value = '';
		}
		$beforeValue = $oRemark->agreed;
		if ($beforeValue === $value) {
			return new \ParameterError('不能重复设置推荐状态');
		}

		/**
		 * 更新记录数据
		 */
		$oAgreedLog = $oRemark->agreed_log;
		if (isset($oAgreedLog->{$oUser->uid})) {
			$oLog = $oAgreedLog->{$oUser->uid};
			$oLog->time = time();
			$oLog->value = $value;
		} else {
			$oAgreedLog->{$oUser->uid} = (object) ['time' => time(), 'value' => $value];
		}

		$modelRem->update(
			'xxt_enroll_record_remark',
			['agreed' => $value, 'agreed_log' => json_encode($oAgreedLog)],
			['id' => $oRemark->id]
		);

		/* 处理用户汇总数据，积分数据 */
		$this->model('matter\enroll\event')->agreeRemark($oApp, $oRemark, $oUser, $value);

		return new \ResponseData($value);
	}
	/**
	 * 和留言相关的任务
	 */
	public function task_action($app, $ek) {
		$modelApp = $this->model('matter\enroll');

		$oApp = $modelApp->byId($app, ['cascaded' => 'N', 'fields' => 'id,siteid,state,entry_rule,action_rule']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$oUser = $this->getUser($oApp);

		$tasks = [];
		if (isset($oApp->actionRule)) {
			$oActionRule = $oApp->actionRule;
			/* 留言出现在共享数据页 */
			if (isset($oActionRule->remark->repos->pre)) {
				$oRule = $oActionRule->remark->repos->pre;
				if ($oRule->desc) {
					$oRule->id = 'remark.repos.pre';
					$tasks[] = $oRule;
				}
			}
			/* 对组长的任务要求 */
			if (!empty($oUser->group_id) && isset($oUser->is_leader) && $oUser->is_leader === 'Y') {
				/* 对组长推荐记录的要求 */
				if (isset($oActionRule->leader->remark->agree->end)) {
					$oRule = $oActionRule->leader->remark->agree->end;
					if (!empty($oRule->min)) {
						$modelRem = $this->model('matter\enroll\remark');
						$remarks = $modelRem->listByRecord(null, $ek, null, null, null, ['agreed' => 'Y']);
						$remarkNum = count($remarks);
						if ($remarkNum >= $oRule->min) {
							$oRule->_ok = [$remarkNum];
						} else {
							$oRule->_no = [(int) $oRule->min - $remarkNum];
						}
						$oRule->id = 'leader.remark.agree.end';
						$tasks[] = $oRule;
					}
				}
			}
		}

		return new \ResponseData($tasks);

	}
}