<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动填写记录留言
 */
class remark extends base {
	/**
	 *
	 */
	private function _setNickname(&$oTarget, $oUser, $oEditor = null) {
		if ($oTarget->userid === $oUser->uid) {
			$oTarget->nickname = '我';
		} else if (preg_match('/用户[^\W_]{13}/', $oTarget->nickname)) {
			$oTarget->nickname = '访客';
		} else if (isset($oEditor) && !empty($oTarget->group_id)) {
			/* 设置编辑统一昵称 */
			if ($oTarget->group_id === $oEditor->group) {
				$oTarget->is_editor = 'Y';
			}
			if (empty($oUser->is_editor) || $oUser->is_editor !== 'Y') {
				/* 设置编辑统一昵称 */
				if (!empty($oTarget->group_id) && $oTarget->group_id === $oEditor->group) {
					$oTarget->nickname = $oEditor->nickname;
				}
			}
		}
	}
	/**
	 * 返回指定留言
	 */
	public function get_action($remark, $cascaded = null) {
		$modelRem = $this->model('matter\enroll\remark');
		$oRemark = $modelRem->byId($remark, ['fields' => 'id,userid,group_id,nickname,state,aid,rid,enroll_key,data_id,remark_id,content,modify_at']);
		if (false === $oRemark && $oRemark->state !== '1') {
			return new \ObjectNotFoundError('（1）访问的资源不可用');
		}
		$oApp = $this->model('matter\enroll')->byId($oRemark->aid, ['cascaded' => 'N']);
		if (false === $oApp && $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$oUser = $this->getUser($oApp);

		/* 是否设置了编辑组统一名称 */
		if (isset($oApp->actionRule->role->editor->group)) {
			if (isset($oApp->actionRule->role->editor->nickname)) {
				$oEditor = new \stdClass;
				$oEditor->group = $oApp->actionRule->role->editor->group;
				$oEditor->nickname = $oApp->actionRule->role->editor->nickname;
			}
		}

		/* 修改昵称 */
		$this->_setNickname($oRemark, $oUser, isset($oEditor) ? $oEditor : null);
		/* 关联数据 */
		if (!empty($cascaded)) {
			$oRecord = $this->model('matter\enroll\record')->byId($oRemark->enroll_key, ['fields' => 'id,userid,group_id,nickname']);
			$this->_setNickname($oRecord, $oUser, isset($oEditor) ? $oEditor : null);
			$oRemark->record = $oRecord;
			if (!empty($oRemark->data_id)) {
				$oRecData = $this->model('matter\enroll\data')->byId($oRemark->data_id, ['fields' => 'userid,group_id,nickname']);
				$this->_setNickname($oRecData, $oUser, isset($oEditor) ? $oEditor : null);
				$oRemark->data = $oRecData;
			}
		}

		return new \ResponseData($oRemark);
	}
	/**
	 * 返回一条填写记录的所有留言
	 */
	public function list_action($ek, $schema = '', $data = '', $page = 1, $size = 99, $role = null) {
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

		/* 是否设置了编辑组统一名称 */
		if (isset($oApp->actionRule->role->editor->group)) {
			if (isset($oApp->actionRule->role->editor->nickname)) {
				$oEditor = new \stdClass;
				$oEditor->group = $oApp->actionRule->role->editor->group;
				$oEditor->nickname = $oApp->actionRule->role->editor->nickname;
				// 如果登记活动指定了编辑组需要获取，编辑组中所有的用户
				$modelGrpUsr = $this->model('matter\group\player');
				$assocGroupId = $oApp->entryRule->group->id;
				$groupEditor = $modelGrpUsr->byApp($assocGroupId, ['roleRoundId' => $oEditor->group, 'fields' => 'role_rounds,userid']);
				if (isset($groupEditor->players)) {
					$groupEditorPlayers = $groupEditor->players;
					$oEditorUsers = new \stdClass;
					foreach ($groupEditorPlayers as $player) {
						$oEditorUsers->{$player->userid} = $player->role_rounds;
					}
					unset($groupEditorPlayers);
				}
			}
		}

		$oUser = $this->getUser($oApp);

		$modelRem = $this->model('matter\enroll\remark');
		$aOptions = [
			'fields' => 'id,seq_in_record,seq_in_data,userid,group_id,userid,nickname,data_id,remark_id,create_at,modify_at,content,agreed,remark_num,like_num,like_log,as_cowork_id',
		];
		if (!empty($data)) {
			$aOptions['data_id'] = $data;
		}
		/* 指定的用户身份 */
		if ($role === 'visitor') {
			$oMockUser = clone $oUser;
			$oMockUser->is_leader = 'N';
			$oMockUser->is_editor = 'N';
		} else if ($role === 'member') {
			$oMockUser = clone $oUser;
			$oMockUser->is_leader = 'N';
		} else {
			$oMockUser = $oUser;
		}

		$oResult = $modelRem->listByRecord($oMockUser, $ek, $schema, $page, $size, $aOptions);
		if (!empty($oResult->remarks)) {
			$modelRecData = $this->model('matter\enroll\data');
			foreach ($oResult->remarks as $oRemark) {
				if (!empty($oRemark->data_id)) {
					$oData = $modelRecData->byId($oRemark->data_id, ['fields' => 'id,schema_id,nickname,multitext_seq']);
					$oRemark->data = $oData;
				}
				/* 修改昵称 */
				if ($oRemark->userid === $oUser->uid) {
					$oRemark->nickname = '我';
				} else if (preg_match('/用户[^\W_]{13}/', $oRemark->nickname)) {
					$oRemark->nickname = '访客';
				} else if (isset($oEditor) && (empty($oUser->is_editor) || $oUser->is_editor !== 'Y')) {
					/* 设置编辑统一昵称 */
					if (!empty($oRemark->group_id) && $oRemark->group_id === $oEditor->group) {
						$oRemark->nickname = $oEditor->nickname;
					} else if (isset($oEditorUsers) && isset($oEditorUsers->{$oRemark->userid})) {
						// 记录提交者是否有编辑组角色
						$oRemark->nickname = $oEditor->nickname;
					}
				}
			}
		}

		return new \ResponseData($oResult);
	}
	/**
	 * 给指定的填写记录的添加留言
	 * 进行留言操作的用户需满足进入活动规则的条件
	 *
	 * @param $ek 被留言的记录
	 * @param $data 被留言的数据
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
		if (!empty($remark)) {
			$modelRem = $this->model('matter\enroll\remark');
			$oRemark = $modelRem->byId($remark, ['fields' => 'id,userid,nickname,state,aid,rid,enroll_key,content,modify_at,modify_log']);
			if (false === $oRemark && $oRemark->state !== '1') {
				return new \ObjectNotFoundError('（1）访问的资源不可用');
			}
		}

		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($oRecord->aid, ['cascaded' => 'N']);
		if (false === $oApp && $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oPosted = $this->getPostJson();
		if (empty($oPosted->content)) {
			return new \ResponseError('留言内容不允许为空');
		}

		/* 发表留言的用户 */
		$oRemarker = $this->getUser($oApp);
		/* 检查是否满足添加留言的条件 */
		if (!isset($oApp->entryRule->exclude_action) || $oApp->entryRule->exclude_action->add_remark != "Y") {
			$checkEntryRule = $this->checkEntryRule($oApp, false, $oRemarker);
			if ($checkEntryRule[0] === false) {
				return new \ResponseError($checkEntryRule[1]);
			}
		}

		$current = time();
		$oNewRemark = new \stdClass;
		$oNewRemark->siteid = $oRecord->siteid;
		$oNewRemark->aid = $oRecord->aid;
		$oNewRemark->rid = $oApp->appRound->rid; // 记录在当前活动的激活轮次上
		$oNewRemark->userid = $oRemarker->uid;
		$oNewRemark->group_id = isset($oRemarker->group_id) ? $oRemarker->group_id : '';
		$oNewRemark->nickname = $modelRec->escape($oRemarker->nickname);
		$oNewRemark->enroll_key = $ek;
		$oNewRemark->enroll_group_id = $oRecord->group_id;
		$oNewRemark->enroll_userid = $oRecord->userid;
		$oNewRemark->schema_id = isset($oRecData) ? $oRecData->schema_id : '';
		$oNewRemark->data_id = empty($recDataId) ? 0 : $recDataId;
		$oNewRemark->remark_id = isset($oRemark) ? $oRemark->id : 0;
		$oNewRemark->create_at = $current;
		$oNewRemark->modify_at = $current;
		$oNewRemark->content = $modelRec->escape($oPosted->content);
		$oNewRemark->as_cowork_id = '0';
		$oNewRemark->like_num = 0;
		$oNewRemark->like_log = '{}';
		$oNewRemark->remark_num = 0;

		/* 在记录中的序号 */
		$seq = (int) $modelRec->query_val_ss([
			'max(seq_in_record)',
			'xxt_enroll_record_remark',
			['enroll_key' => $oRecord->enroll_key],
		]);
		$oNewRemark->seq_in_record = $seq + 1;
		/* 在数据中的序号 */
		if (isset($oRecData)) {
			$seq = (int) $modelRec->query_val_ss([
				'max(seq_in_data)',
				'xxt_enroll_record_remark',
				['data_id' => $oRecData->id],
			]);
			$oNewRemark->seq_in_data = $seq + 1;
		}
		/* 默认表态 */
		$oEntryRuleResult = $this->checkEntryRule2($oApp);
		if (isset($oEntryRuleResult->passed) && $oEntryRuleResult->passed === 'N') {
			/* 如果当前用户不满足进入活动规则，留言设置为讨论状态 */
			$oNewRemark->agreed = 'D';
		} else {
			if (isset($oApp->actionRule->remark->default->agreed)) {
				/* 活动设置的默认规则 */
				$agreed = $oApp->actionRule->remark->default->agreed;
				if (in_array($agreed, ['A', 'D'])) {
					$oNewRemark->agreed = $agreed;
				}
			} else if (isset($oRecord->agreed) && $oRecord->agreed === 'D') {
				/* 如果记录是讨论状态，留言也是讨论状态 */
				$oNewRemark->agreed = 'D';
			}
		}

		$oNewRemark->id = $modelRec->insert('xxt_enroll_record_remark', $oNewRemark, true);

		/* 留言总数 */
		$modelRec->update("update xxt_enroll_record set remark_num=remark_num+1 where enroll_key='$ek'");
		/* 记录的直接留言 */
		if (!isset($oRecData) && !isset($oRemark)) {
			$modelRec->update("update xxt_enroll_record set rec_remark_num=rec_remark_num+1 where enroll_key='$ek'");
		} else {
			if (isset($oRecData)) {
				$modelRec->update("update xxt_enroll_record_data set remark_num=remark_num+1,last_remark_at=$current where id = " . $recDataId);
				// 如果每一条的数据被留言了那么这道题的总数据+1
				if ($oRecData->multitext_seq != 0) {
					$modelRec->update("update xxt_enroll_record_data set remark_num=remark_num+1,last_remark_at=$current where enroll_key='$ek' and schema_id='{$oRecData->schema_id}' and multitext_seq = 0");
				}
			}
			if (isset($oRemark)) {
				$modelRec->update("update xxt_enroll_record_remark set remark_num=remark_num+1 where id='{$oRemark->id}'");
			}
		}

		/* 更新用户汇总数据 */
		if (isset($oRecData)) {
			foreach ($oApp->dataSchemas as $dataSchema) {
				if ($dataSchema->id === $oRecData->schema_id) {
					$oDataSchema = $dataSchema;
					break;
				}
			}
			if (isset($oDataSchema->cowork) && $oDataSchema->cowork === 'Y') {
				$this->model('matter\enroll\event')->remarkCowork($oApp, $oRecData, $oNewRemark, $oRemarker);
			} else {
				$this->model('matter\enroll\event')->remarkRecData($oApp, $oRecData, $oNewRemark, $oRemarker);
			}
		} else {
			$this->model('matter\enroll\event')->remarkRecord($oApp, $oRecord, $oNewRemark, $oRemarker);
		}

		/* 生成提醒 */
		$this->model('matter\enroll\notice')->addRemark($oApp, $oRecord, $oNewRemark, $oRemarker, isset($oRecData) ? $oRecData : null, isset($oRemark) ? $oRemark : null);

		/* 修改昵称 */
		if ($oNewRemark->userid === $oRemarker->uid) {
			$oNewRemark->nickname = '我';
		}

		/* 通知登记活动事件接收人 */
		if (isset($oApp->notifyConfig->remark->valid) && $oApp->notifyConfig->remark->valid === true) {
			$this->_notifyReceivers($oApp, $oRecord, $oNewRemark);
		}

		return new \ResponseData($oNewRemark);
	}
	/**
	 * 修改留言
	 * 1、只允许修改自己的留言
	 */
	public function update_action($remark) {
		$modelRem = $this->model('matter\enroll\remark');
		$oRemark = $modelRem->byId($remark, ['fields' => 'id,aid,userid,state,rid,enroll_key,content,modify_at,modify_log']);
		if (false === $oRemark && $oRemark->state !== '1') {
			return new \ObjectNotFoundError('（1）访问的资源不可用');
		}

		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($oRemark->aid, ['cascaded' => 'N']);
		if (false === $oApp && $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->getUser($oApp);

		if ($oUser->uid !== $oRemark->userid) {
			return new \ResponseError('没有修改指定留言的权限');
		}
		$oPosted = $this->getPostJson();
		if (empty($oPosted->content)) {
			return new \ResponseError('留言内容不允许为空');
		}
		if ($oPosted->content === $oRemark->content) {
			return new \ResponseError('留言内容没有变化');
		}

		$current = time();
		$oRemark->modify_log[] = (object) ['at' => $oRemark->modify_at, 'c' => $oRemark->content];
		$rst = $modelRem->update(
			'xxt_enroll_record_remark',
			[
				'content' => $modelRem->escape($oPosted->content),
				'modify_at' => $current,
				'modify_log' => $modelRem->escape($modelRem->toJson($oRemark->modify_log)),
			],
			['id' => $oRemark->id]
		);

		/* 记录日志 */
		$this->model('matter\enroll\event')->updateRemark($oApp, $oRemark, $oUser);

		return new \ResponseData($rst);
	}
	/**
	 * 删除留言
	 */
	public function remove_action($remark) {
		$modelRem = $this->model('matter\enroll\remark');
		$oRemark = $modelRem->byId($remark, ['fields' => 'id,aid,userid,state,rid,enroll_key,data_id,remark_id']);
		if (false === $oRemark && $oRemark->state !== '1') {
			return new \ObjectNotFoundError('（1）访问的资源不可用');
		}

		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($oRemark->aid, ['cascaded' => 'N']);
		if (false === $oApp && $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->getUser($oApp);
		if ($oUser->uid !== $oRemark->userid) {
			return new \ResponseError('没有撤销指定留言的权限');
		}

		$current = time();
		$rst = $modelRem->update(
			'xxt_enroll_record_remark',
			[
				'state' => 0,
				'modify_at' => $current,
			],
			['id' => $oRemark->id]
		);

		/* 留言总数 */
		$rst = $modelEnl->update("update xxt_enroll_record set remark_num=remark_num-1 where enroll_key='{$oRemark->enroll_key}'");
		if ($oRemark->remark_id !== '0') {
			$modelRem->update("update xxt_enroll_record_remark set remark_num=remark_num-1 where id='{$oRemark->remark_id}'");
		} else if ($oRemark->data_id !== '0') {
			$modelRecData = $this->model('matter\enroll\data');
			$oRecData = $modelRecData->byId($oRemark->data_id);
			$modelRem->update("update xxt_enroll_record_data set remark_num=remark_num-1 where id=" . $oRemark->data_id);
			if ($oRecData->multitext_seq > 0) {
				$modelRem->update("update xxt_enroll_record_data set remark_num=remark_num-1 where multitext_seq=0 and enroll_key='{$oRecData->enroll_key}' and schema_id='{$oRecData->schema_id}'");
			}
		} else {
			$modelEnl->update("update xxt_enroll_record set rec_remark_num=rec_remark_num-1 where enroll_key='{$oRemark->enroll_key}'");

		}

		/* 记录日志 */
		$this->model('matter\enroll\event')->removeRemark($oApp, $oRemark, $oUser);

		return new \ResponseData($rst);
	}
	/**
	 * 通知留言填写记录事件
	 */
	private function _notifyReceivers($oApp, $oRecord, $oRemark) {
		/* 通知接收人 */
		$receivers = $this->model('matter\enroll\user')->getRemarkReceivers($oApp, $oRecord, $oRemark, $oApp->notifyConfig->remark);
		if (empty($receivers)) {
			return false;
		}

		$page = empty($oApp->notifyConfig->remark->page) ? 'cowork' : $oApp->notifyConfig->remark->page;
		$noticeURL = $oApp->entryUrl . '&ek=' . $oRecord->enroll_key . '&page=cowork' . '#remark-' . $oRemark->id;

		$noticeName = 'site.enroll.remark';

		/*获取模板消息id*/
		$oTmpConfig = $this->model('matter\tmplmsg\config')->getTmplConfig($oApp, $noticeName, ['onlySite' => false, 'noticeURL' => $noticeURL]);
		if ($oTmpConfig[0] === false) {
			return false;
		}
		$oTmpConfig = $oTmpConfig[1];

		$modelTmplBat = $this->model('matter\tmplmsg\batch');
		$oCreator = new \stdClass;
		$oCreator->uid = $noticeName;
		$oCreator->name = 'system';
		$oCreator->src = 'pl';
		$modelTmplBat->send($oApp->siteid, $oTmpConfig->tmplmsgId, $oCreator, $receivers, $oTmpConfig->oParams, ['send_from' => $oApp->type . ':' . $oApp->id]);

		return true;
	}
	/**
	 * 点赞填写记录中的某一个留言
	 *
	 * @param string $remark remark'id
	 *
	 */
	public function like_action($remark) {
		$modelRem = $this->model('matter\enroll\remark');
		$oRemark = $modelRem->byId($remark, ['fields' => 'id,aid,rid,enroll_key,userid,like_log']);
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
		/* 检查是否满足给评论点赞的条件 */
		if (!isset($oApp->entryRule->exclude_action) || $oApp->entryRule->exclude_action->like != "Y") {
			$checkEntryRule = $this->checkEntryRule($oApp, false, $oUser);
			if ($checkEntryRule[0] === false) {
				return new \ResponseError($checkEntryRule[1]);
			}
		}

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
		$oRemark = $modelRem->byId($remark, ['fields' => 'id,aid,rid,enroll_key,userid,agreed,agreed_log']);
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
			if ($oGrpMemb && !empty($oGrpMemb->round_id)) {
				/* 填写记录的用户属于一个分组 */
				if ($oGrpMemb->round_id !== $oGrpLeader->round_id) {
					return new \ParameterError('只允许组长对本组成员的留言表态');
				}
			} else {
				if (empty($oUser->is_editor) || $oUser->is_editor !== 'Y') {
					return new \ParameterError('只允许编辑组的组长对不属于任何分组的成员的留言表态');
				}
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
	 * 将留言作为记录的协作填写内容
	 *
	 * @param string $remark remark'id
	 *
	 */
	public function asCowork_action($remark, $schema) {
		$modelRem = $this->model('matter\enroll\remark');
		$oRemark = $modelRem->byId($remark, ['fields' => 'id,aid,rid,enroll_key,userid,nickname,group_id,content']);
		if (false === $oRemark) {
			return new \ObjectNotFoundError();
		}

		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($oRemark->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$modelRec = $this->model('matter\enroll\record');
		$oRecord = $modelRec->byId($oRemark->enroll_key, ['fields' => 'id,state,data']);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$modelData = $this->model('matter\enroll\data');
		$oRecData = $modelData->byRecord($oRemark->enroll_key, ['schema' => $schema]);
		if (false === $oRecData || $oRecData->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$oRecData->value = empty($oRecData->value) ? [] : json_decode($oRecData->value);

		$oUser = $this->getUser($oApp);

		$current = time();
		$oCowork = (object) [
			'aid' => $oApp->id,
			'rid' => $oRemark->rid,
			'enroll_key' => $oRemark->enroll_key,
			'submit_at' => $current,
			'userid' => $oRemark->userid,
			'nickname' => $this->escape($oRemark->nickname),
			'group_id' => $oRemark->group_id,
			'schema_id' => $oRecData->schema_id,
			'multitext_seq' => count($oRecData->value) + 1,
			'value' => $this->escape($oRemark->content),
			'agreed' => 'A',
		];
		$oCowork->id = $modelData->insert('xxt_enroll_record_data', $oCowork, true);

		$modelData->update(
			'xxt_enroll_record_remark',
			['as_cowork_id' => $oCowork->id],
			['id' => $oRemark->id]
		);

		$oRecData->value[] = (object) ['id' => $oCowork->id, 'value' => $oRemark->content];
		$modelData->update(
			'xxt_enroll_record_data',
			['value' => $this->escape($modelData->toJson($oRecData->value))],
			['id' => $oRecData->id]
		);

		$oRecord->data->{$oRecData->schema_id} = $oRecData->value;
		$modelRec->update(
			'xxt_enroll_record',
			['data' => $this->escape($modelRec->toJson($oRecord->data))],
			['id' => $oRecord->id]
		);

		/* 记操作日志 */
		$modelEvt = $this->model('matter\enroll\event');
		$modelEvt->remarkAsCowork($oApp, $oRecData, $oCowork, $oRemark, $oUser);

		return new \ResponseData($oCowork);
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
				if (!empty($oRule->remark->likeNum)) {
					if (empty($oRule->desc)) {
						$oRule->desc = '留言获得【' . $oRule->remark->likeNum . '个】点赞（投票）后会显示在共享页。';
					}
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
							if (empty($oRule->desc)) {
								$desc = '组长需要选择【' . $oRule->min . ($oRule->max > $oRule->min ? ('-' . $oRule->max) : '') . '条】留言推荐，';
							} else {
								$desc = $oRule->desc;
								if (!in_array(mb_substr($desc, -1), ['。', '，', '；', '.', ',', ';'])) {
									$desc .= '，';
								}
							}
							$oRule->desc .= $desc . '还需【' . $oRule->_no[0] . '条】。';
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