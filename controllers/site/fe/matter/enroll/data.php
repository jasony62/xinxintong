<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 填写记录数据
 */
class data extends base {
	/**
	 * 获得填写记录中的数据
	 *
	 * @param string $ek
	 * @param string $schema
	 * @param string $data
	 */
	public function get_action($ek, $schema = '', $data = '', $cascaded = 'N') {
		$oRecord = $this->model('matter\enroll\record')->byId($ek, ['fields' => 'id,aid,rid,enroll_key,userid,group_id,nickname,enroll_at']);
		if (false === $oRecord) {
			return new \ObjectNotFoundError('（1）指定的对象不存在或不可用');
		}

		$oApp = $this->model('matter\enroll')->byId($oRecord->aid, ['cascaded' => 'N', 'fields' => 'id,siteid,state,data_schemas,vote_config,entry_rule,action_rule']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError('（2）指定的对象不存在或不可用');
		}

		$oUser = $this->getUser($oApp);

		/* 是否限制了匿名规则 */
		$bAnonymous = false;
		if (isset($oApp->actionRule->cowork->anonymous)) {
			$oRule = $oApp->actionRule->cowork->anonymous;
			/* 协作点赞截止时间 */
			if (!empty($oRule->time->cowork->like->end)) {
				if (isset($oApp->actionRule->cowork->like->end->time)) {
					$oRule2 = $oApp->actionRule->cowork->like->end->time;
					if (isset($oRule2->mode) && isset($oRule2->unit) && isset($oRule2->value)) {
						if ($oRule2->mode === 'after_round_start_at') {
							$modelRnd = $this->model('matter\enroll\round');
							$oActiveRnd = $modelRnd->getActive($oApp);
							if ($oActiveRnd && !empty($oActiveRnd->start_at)) {
								$endtime = (int) $oActiveRnd->start_at + (3600 * $oRule2->value);
								$bAnonymous = time() < $endtime;
							}
						}
					}
				}
			}
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
		/* 修改默认访客昵称 */
		if ($oRecord->userid === $oUser->uid) {
			$oRecord->nickname = '我';
		} else if (preg_match('/用户[^\W_]{13}/', $oRecord->nickname)) {
			$oRecord->nickname = '访客';
		} else if (isset($oEditor)) {
			if ($oRecord->group_id === $oEditor->group) {
				$oRecord->is_editor = 'Y';
			}
			if (empty($oUser->is_editor) || $oUser->is_editor !== 'Y') {
				/* 设置编辑统一昵称 */
				if (!empty($oRecord->group_id) && $oRecord->group_id === $oEditor->group) {
					$oRecord->nickname = $oEditor->nickname;
				} else if (isset($oEditorUsers) && isset($oEditorUsers->{$oRecord->userid})) {
					// 记录提交者是否有编辑组角色
					$oRecord->nickname = $oEditor->nickname;
				}
			}
		}
		$oSchemas = new \stdClass;
		foreach ($oApp->dynaDataSchemas as $dataSchema) {
			$oSchemas->{$dataSchema->id} = $dataSchema;
		}
		/* 获取记录的投票信息 */
		if (!empty($oApp->voteConfig)) {
			$aCanVoteSchemas = $this->model('matter\enroll\task', $oApp)->getVoteRule($oUser, $oRecord->round);
		}

		$fields = 'id,state,userid,group_id,nickname,schema_id,multitext_seq,submit_at,agreed,value,supplement,like_num,like_log,remark_num,tag,score,dislike_num,dislike_log,vote_num';
		$modelRecDat = $this->model('matter\enroll\data');
		if (empty($data)) {
			$oRecData = $modelRecDat->byRecord($ek, ['schema' => $schema, 'fields' => $fields]);
		} else {
			$oRecData = $modelRecDat->byId($data, ['fields' => $fields]);
		}

		if (false === $oRecData || $oRecData->state !== '1') {
			return new \ObjectNotFoundError('（3）指定的对象不存在或不可用');
		}

		if (isset($oSchemas->{$oRecData->schema_id}) && $oSchemas->{$oRecData->schema_id}->type === 'multitext') {
			if ($oRecData->multitext_seq == 0) {
				/* 获得填写的项目 */
				$oRecData->value = empty($oRecData->value) ? [] : json_decode($oRecData->value);
				if ($cascaded === 'Y') {
					$q = [
						$fields,
						'xxt_enroll_record_data',
						"state=1 and enroll_key='{$ek}' and schema_id='{$oRecData->schema_id}' and multitext_seq>0",
					];
					/* 是否设置了对其他组用户在评论页可见条件 */
					$coworkRemarkLikeNum = 0;
					if (isset($oApp->actionRule->cowork->remark->pre)) {
						$oRule = $oApp->actionRule->cowork->remark->pre;
						if (!empty($oRule->cowork->likeNum)) {
							$coworkRemarkLikeNum = (int) $oRule->cowork->likeNum;
						}
						if ($coworkRemarkLikeNum) {
							$q[2] .= " and (group_id='" . (empty($oUser->group_id) ? '' : $oUser->group_id) . "' or like_num>={$coworkRemarkLikeNum})";
						}
					}
					/* 根据状态和用户角色过滤答案 */
					if ($oRecord->userid !== $oUser->uid) {
						if (empty($oUser->is_leader) || $oUser->is_leader !== 'S') {
							if (!empty($oUser->uid)) {
								$w = " and (";
								$w .= "(agreed<>'N' and agreed<>'D')";
								$w .= " or userid='{$oUser->uid}'";
								if (!empty($oUser->group_id)) {
									$w .= " or group_id='{$oUser->group_id}'";
								}
								if (isset($oUser->is_editor) && $oUser->is_editor === 'Y') {
									$w .= " or group_id=''";
								}
								$w .= ")";
								$q[2] .= $w;
							}
						}
					} else {
						if (empty($oUser->is_leader) || $oUser->is_leader !== 'S') {
							if (!empty($oUser->uid)) {
								$w = " and (";
								$w .= "agreed<>'N'";
								$w .= " or userid='{$oUser->uid}'";
								$w .= ")";
								$q[2] .= $w;
							}
						}
					}
					$oRecData->items = $modelRecDat->query_objs_ss($q);

					foreach ($oRecData->items as $oItem) {
						$oItem->like_log = empty($oItem->like_log) ? [] : json_decode($oItem->like_log);
						$oItem->dislike_log = empty($oItem->dislike_log) ? [] : json_decode($oItem->dislike_log);
						if ($bAnonymous) {
							unset($oItem->nickname);
						} else if ($oItem->userid === $oUser->uid) {
							$oItem->nickname = '我';
						} else if (preg_match('/用户[^\W_]{13}/', $oItem->nickname)) {
							$oItem->nickname = '访客';
						} else if (isset($oEditor) && (empty($oUser->is_editor) || $oUser->is_editor !== 'Y')) {
							/* 设置编辑统一昵称 */
							if (!empty($oItem->group_id) && $oItem->group_id === $oEditor->group) {
								$oItem->nickname = $oEditor->nickname;
							} else if (isset($oEditorUsers) && isset($oEditorUsers->{$oItem->userid})) {
								// 记录提交者是否有编辑组角色
								$oItem->nickname = $oEditor->nickname;
							}
						}
						/* 当前用户投票情况 */
						//if (!empty($aCanVoteSchemas[$oRecData->schema_id])) {
						$oVoteResult = new \stdClass;
						$vote_at = (int) $modelRecDat->query_val_ss(['vote_at', 'xxt_enroll_vote', ['data_id' => $oItem->id, 'state' => 1, 'userid' => $oUser->uid]]);
						$oVoteResult->vote_at = $vote_at;
						$oVoteResult->vote_num = $oItem->vote_num;
						//$oVoteResult->state = $aCanVoteSchemas[$oRecData->schema_id]->vote->state;
						unset($oItem->vote_num);
						$oItem->voteResult = $oVoteResult;
						//}
					}
				}
			} else {
				if ($bAnonymous) {
					unset($oRecData->nickname);
				} else if (isset($oEditor)) {
					if ($oRecData->group_id === $oEditor->group) {
						$oRecData->is_editor = 'Y';
					}
					if (empty($oUser->is_editor) || $oUser->is_editor !== 'Y') {
						/* 设置编辑统一昵称 */
						if (!empty($oRecData->group_id) && $oRecData->group_id === $oEditor->group) {
							$oRecData->nickname = $oEditor->nickname;
						} else if (isset($oEditorUsers) && isset($oEditorUsers->{$oRecData->userid})) {
							// 记录提交者是否有编辑组角色
							$oRecData->nickname = $oEditor->nickname;
						}
					}
				}
			}
		}

		$oRecord->schema_id = $oRecData->schema_id;
		$oRecord->verbose = new \stdClass;
		$oRecord->verbose->{$oRecData->schema_id} = $oRecData;

		return new \ResponseData($oRecord);
	}
	/**
	 * 提交一道题目的数据
	 * 1、需要记录修改日志
	 * 2、需要支持积分
	 *
	 * @param int $data 填写记录数据id
	 */
	public function submit_action($data) {
		$modelData = $this->model('matter\enroll\data')->setOnlyWriteDbConn(true);
		$oRecData = $modelData->byId($data, ['fields' => 'id,aid,rid,enroll_key,schema_id,multitext_seq']);
		if (false === $oRecData) {
			return new \ObjectNotFoundError();
		}

		$oApp = $this->model('matter\enroll')->byId($oRecData->aid, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$oUpdatedSchema = null;
		foreach ($oApp->dataSchemas as $oSchema) {
			if ($oSchema->id === $oRecData->schema_id) {
				$oUpdatedSchema = $oSchema;
				break;
			}
		}
		if (empty($oUpdatedSchema)) {
			return new \ParameterError('找不到题目定义');
		}

		$oUser = $this->getUser($oApp);

		$oPosted = $this->getPostJson();
		$current = time();
		$oResult = null;

		switch ($oUpdatedSchema->type) {
		case 'multitext':
			if ($oRecData->multitext_seq === '0') {
				/* 之前提交过现在要保留的数据 */
				$oReservedItems = new \stdClass;
				foreach ($oPosted as $oItem) {
					if (!empty($oItem->id)) {
						$oReservedItems->{$oItem->id} = $oItem;
					}
				}
				/* 已有的记录 */
				$oBeforeItems = new \stdClass;
				$q = [
					'id,userid',
					'xxt_enroll_record_data',
					['state' => 1, 'enroll_key' => $oRecData->enroll_key, 'schema_id' => $oRecData->schema_id, 'multitext_seq' => (object) ['op' => '<>', 'pat' => '0']],
				];
				$aBeforeItems = $modelData->query_objs_ss($q);
				foreach ($aBeforeItems as $oBeforeItem) {
					if (!isset($oReservedItems->{$oBeforeItem->id})) {
						/* 数据项将要被删除 */
						if ($oBeforeItem->userid !== $oUser->uid) {
							return new \ResponseError('不允许删除其他用户提交的数据');
						}
					}
					$oBeforeItems->{$oBeforeItem->id} = $oBeforeItem;
				}
				/* 更新数据 */
				foreach ($oPosted as $seq => $oItem) {
					if (empty($oItem->id)) {
						$aSchemaValue = [
							'aid' => $oApp->id,
							'rid' => $oRecData->rid,
							'enroll_key' => $oRecData->enroll_key,
							'submit_at' => $current,
							'userid' => isset($oUser->uid) ? $oUser->uid : '',
							'nickname' => isset($oUser->nickname) ? $oUser->nickname : '',
							'group_id' => isset($oUser->group_id) ? $oUser->group_id : '',
							'schema_id' => $oUpdatedSchema->id,
							'multitext_seq' => (int) $seq + 1,
							'value' => $this->escape($oItem->value),
						];
						$oItem->id = $modelData->insert('xxt_enroll_record_data', $aSchemaValue, true);
					} else {
						$oBeforeItem = $oBeforeItems->{$oItem->id};
						if ($oBeforeItem->userid === $oUser->uid) {
							$modelData->update(
								'xxt_enroll_record_data',
								[
									'value' => $this->escape($oItem->value),
									'multitext_seq' => (int) $seq + 1,
								],
								['id' => $oItem->id]
							);
						}
						unset($oBeforeItems->{$oItem->id});
					}
				}
				/* 删除的项目 */
				foreach ($oBeforeItems as $oItem) {
					$modelData->update(
						'xxt_enroll_record_data',
						['state' => 0],
						['id' => $oItem->id]
					);
				}
				/* 更新父记录数据 */
				$oResult = $oPosted;
				$modelData->update(
					'xxt_enroll_record_data',
					['value' => $this->escape($modelData->toJson($oResult))],
					['id' => $oRecData->id]
				);
			}
			break;
		}

		return new \ResponseData($oResult);
	}
	/**
	 * 推荐填写记录中的某一个题
	 * 只有组长才有权限做
	 *
	 * @param string $ek
	 * @param string $schema
	 * @param string $value
	 *
	 */
	public function agree_action($ek, $schema, $data = null, $value = '') {
		$modelData = $this->model('matter\enroll\data');
		if (!empty($data)) {
			$oRecData = $modelData->byId($data, ['fields' => 'id,aid,rid,enroll_key,schema_id,state,userid,agreed,agreed_log']);
		} else {
			$oRecData = $modelData->byRecord($ek, ['schema' => $schema, 'fields' => 'id,aid,rid,enroll_key,schema_id,state,userid,agreed,agreed_log']);
		}
		if (false === $oRecData || $oRecData->state !== '1') {
			return new \ObjectNotFoundError('（1）指定的对象不存在或不可用');
		}

		$oApp = $this->model('matter\enroll')->byId($oRecData->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError('（2）指定的对象不存在或不可用');
		}
		foreach ($oApp->dataSchemas as $oSchema) {
			if ($oSchema->id === $oRecData->schema_id) {
				$oDataSchema = $oSchema;
				break;
			}
		}
		if (!isset($oDataSchema)) {
			return new \ObjectNotFoundError('（3）指定的对象不存在或不可用');
		}
		if (empty($oApp->entryRule->group->id)) {
			if (empty($oApp->actionRule->cowork->agreed->pre->author)) {
				return new \ParameterError('只有进入条件为分组活动的登记活动才允许组长表态 或 【允许对协作填写(答案)表态的成员】的配置中勾选了允许提问者表态');
			}
		}
		// 获取记录信息
		$oRec = $this->model('matter\enroll\record')->byId($oRecData->enroll_key, ['fields' => 'id,userid,state']);
		if (false === $oRec || $oRec->state !== '1') {
			return new \ObjectNotFoundError('（4）指定的对象不存在或不可用');
		}

		$oUser = $this->getUser($oApp);
		$oUserCoworkAgreedPower = false;
		// 如果允许记录（问题）提交者对答案表态，那么提交者可以直接表态
		if (!empty($oApp->actionRule->cowork->agreed->pre->author) && $oRec->userid === $oUser->uid) {
			$oUserCoworkAgreedPower = true;
		}
		//
		if ($oUserCoworkAgreedPower === false) {
			$modelGrpUsr = $this->model('matter\group\player');
			/* 当前操作用户所属分组及角色 */
			$oGrpLeader = $modelGrpUsr->byUser($oApp->entryRule->group, $oUser->uid, ['fields' => 'is_leader,round_id', 'onlyOne' => true]);
			if (false === $oGrpLeader || !in_array($oGrpLeader->is_leader, ['Y', 'S'])) {
				return new \ParameterError('只允许组长进行推荐');
			}
			/* 检查是否在同一分组内 */
			if ($oGrpLeader->is_leader === 'Y') {
				$oGrpMemb = $modelGrpUsr->byUser($oApp->entryRule->group, $oRecData->userid, ['fields' => 'round_id', 'onlyOne' => true]);
				if ($oGrpMemb && !empty($oGrpMemb->round_id)) {
					/* 填写记录的用户属于一个分组 */
					if ($oGrpMemb->round_id !== $oGrpLeader->round_id) {
						return new \ParameterError('只允许组长对本组成员的数据表态');
					}
				} else {
					if (empty($oUser->is_editor) || $oUser->is_editor !== 'Y') {
						return new \ParameterError('只允许编辑组的组长对不属于任何分组的成员的数据表态');
					}
				}
			}
		}

		if (!in_array($value, ['Y', 'N', 'A', 'D'])) {
			$value = '';
		}
		$beforeValue = $oRecData->agreed;
		if ($beforeValue === $value) {
			return new \ParameterError('不能重复设置推荐状态');
		}

		$oAgreedLog = $oRecData->agreed_log;
		if (isset($oAgreedLog->{$oUser->uid})) {
			$oLog = $oAgreedLog->{$oUser->uid};
			$oLog->time = time();
			$oLog->value = $value;
		} else {
			$oAgreedLog->{$oUser->uid} = (object) ['time' => time(), 'value' => $value];
		}

		$rst = $modelData->update(
			'xxt_enroll_record_data',
			['agreed' => $value, 'agreed_log' => json_encode($oAgreedLog)],
			['id' => $oRecData->id]
		);

		/* 如果活动属于项目，更新项目内的推荐内容 */
		if (!empty($oApp->mission_id)) {
			$modelMisMat = $this->model('matter\mission\matter');
			$modelMisMat->agreed($oApp, 'D', $oRecData, $value);
		}
		/* 处理了用户汇总数据，积分数据 */
		if (isset($oDataSchema->cowork) && $oDataSchema->cowork === 'Y') {
			$this->model('matter\enroll\event')->agreeCowork($oApp, $oRecData, $oUser, $value);
		} else {
			$this->model('matter\enroll\event')->agreeRecData($oApp, $oRecData, $oUser, $value);
		}

		return new \ResponseData($rst);
	}
	/**
	 * 点赞填写记录中的某一个题
	 *
	 * @param string $ek
	 * @param string $schema
	 * @param int $data xxt_enroll_record_data 的id
	 *
	 */
	public function like_action($data) {
		if (empty($data)) {
			return new \ResponseError('参数错误：未指定被留言内容ID');
		}
		$modelData = $this->model('matter\enroll\data');
		$oRecData = $modelData->byId($data, ['fields' => 'id,aid,rid,enroll_key,schema_id,like_log,userid,multitext_seq,like_num']);
		if (false === $oRecData) {
			return new \ObjectNotFoundError('（1）指定的对象不存在或不可用');
		}

		$oApp = $this->model('matter\enroll')->byId($oRecData->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError('（2）指定的对象不存在或不可用');
		}

		$oUser = $this->getUser($oApp);
		/* 检查是否满足给答案点赞的条件 */
		if (empty($oApp->entryRule->exclude_action->like) || $oApp->entryRule->exclude_action->like != "Y") {
			$checkEntryRule = $this->checkEntryRule($oApp, false, $oUser);
			if ($checkEntryRule[0] === false) {
				return new \ResponseError($checkEntryRule[1]);
			}
		}

		$oRecord = $this->model('matter\enroll\record')->byId($oRecData->enroll_key, ['cascaded' => 'N', 'fields' => 'id,state,like_data_num']);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError('（3）指定的对象不存在或不可用');
		}

		/* 数据项的题目 */
		$oDataSchema = null;
		foreach ($oApp->dataSchemas as $dataSchema) {
			if ($dataSchema->id === $oRecData->schema_id) {
				$oDataSchema = $dataSchema;
				break;
			}
		}
		if (empty($oDataSchema)) {
			return new \ObjectNotFoundError('（4）指定的对象不存在或不可用');
		}

		$oLikeLog = $oRecData->like_log;
		if (isset($oLikeLog->{$oUser->uid})) {
			unset($oLikeLog->{$oUser->uid});
			$incLikeNum = -1;
		} else {
			$oLikeLog->{$oUser->uid} = time();
			$incLikeNum = 1;
		}
		$likeNum = $oRecData->like_num + $incLikeNum;
		$modelData->update(
			'xxt_enroll_record_data',
			['like_log' => json_encode($oLikeLog), 'like_num' => $likeNum],
			['id' => $oRecData->id]
		);

		$modelData->update(
			'xxt_enroll_record',
			['like_data_num' => $oRecord->like_data_num + $incLikeNum],
			['id' => $oRecord->id]
		);

		$modelEnlEvt = $this->model('matter\enroll\event');
		if ($incLikeNum > 0) {
			/* 发起点赞 */
			if (isset($oDataSchema->cowork) && $oDataSchema->cowork === 'Y') {
				$modelEnlEvt->likeCowork($oApp, $oRecData, $oUser);
			} else {
				$modelEnlEvt->likeRecData($oApp, $oRecData, $oUser);
			}
		} else {
			/* 撤销点赞 */
			if (isset($oDataSchema->cowork) && $oDataSchema->cowork === 'Y') {
				$modelEnlEvt->undoLikeCowork($oApp, $oRecData, $oUser);
			} else {
				$modelEnlEvt->undoLikeRecData($oApp, $oRecData, $oUser);
			}
		}

		$aResult = [];
		$aResult['like_log'] = $oLikeLog;
		$aResult['like_num'] = $likeNum;

		return new \ResponseData($aResult);
	}
	/**
	 * 反对填写记录中的某一个题
	 *
	 * @param int $data xxt_enroll_record_data 的id
	 *
	 */
	public function dislike_action($data) {
		$modelData = $this->model('matter\enroll\data');
		$oRecData = $modelData->byId($data, ['fields' => 'id,aid,rid,enroll_key,schema_id,dislike_log,userid,multitext_seq,dislike_num']);
		if (false === $oRecData) {
			return new \ObjectNotFoundError('（1）指定的对象不存在或不可用');
		}

		$oApp = $this->model('matter\enroll')->byId($oRecData->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError('（2）指定的对象不存在或不可用');
		}

		$oUser = $this->getUser($oApp);
		/* 检查是否满足给答案点赞/点踩的条件 */
		if (empty($oApp->entryRule->exclude_action->like) || $oApp->entryRule->exclude_action->like != "Y") {
			$checkEntryRule = $this->checkEntryRule($oApp, false, $oUser);
			if ($checkEntryRule[0] === false) {
				return new \ResponseError($checkEntryRule[1]);
			}
		}

		$oRecord = $this->model('matter\enroll\record')->byId($oRecData->enroll_key, ['cascaded' => 'N', 'fields' => 'id,state,dislike_data_num']);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError('（3）指定的对象不存在或不可用');
		}

		/* 数据项的题目 */
		$oDataSchema = null;
		foreach ($oApp->dataSchemas as $dataSchema) {
			if ($dataSchema->id === $oRecData->schema_id) {
				$oDataSchema = $dataSchema;
				break;
			}
		}
		if (empty($oDataSchema)) {
			return new \ObjectNotFoundError('（4）指定的对象不存在或不可用');
		}

		$oDislikeLog = $oRecData->dislike_log;
		if (isset($oDislikeLog->{$oUser->uid})) {
			unset($oDislikeLog->{$oUser->uid});
			$incDislikeNum = -1;
		} else {
			$oDislikeLog->{$oUser->uid} = time();
			$incDislikeNum = 1;
		}
		$dislikeNum = $oRecData->dislike_num + $incDislikeNum;
		$modelData->update(
			'xxt_enroll_record_data',
			['dislike_log' => json_encode($oDislikeLog), 'dislike_num' => $dislikeNum],
			['id' => $oRecData->id]
		);

		$modelData->update(
			'xxt_enroll_record',
			['dislike_data_num' => $oRecord->dislike_data_num + $incDislikeNum],
			['id' => $oRecord->id]
		);

		$modelEnlEvt = $this->model('matter\enroll\event');
		if ($incDislikeNum > 0) {
			/* 发起点赞 */
			if (isset($oDataSchema->cowork) && $oDataSchema->cowork === 'Y') {
				$modelEnlEvt->dislikeCowork($oApp, $oRecData, $oUser);
			} else {
				$modelEnlEvt->dislikeRecData($oApp, $oRecData, $oUser);
			}
		} else {
			/* 撤销点赞 */
			if (isset($oDataSchema->cowork) && $oDataSchema->cowork === 'Y') {
				$modelEnlEvt->undoDislikeCowork($oApp, $oRecData, $oUser);
			} else {
				$modelEnlEvt->undoDislikeRecData($oApp, $oRecData, $oUser);
			}
		}

		$aResult = [];
		$aResult['dislike_log'] = $oDislikeLog;
		$aResult['dislike_num'] = $dislikeNum;

		return new \ResponseData($aResult);
	}
}