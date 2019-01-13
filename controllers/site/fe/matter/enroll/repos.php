<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 填写记录数据汇总
 */
class repos extends base {
	/**
	 * 获得活动中作为内容分类目录使用的题目
	 */
	public function dirSchemasGet_action($app) {
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N', 'id,state,data_schemas']);
		if ($oApp === false || $oApp->state !== '1') {
			$this->outputError('指定的记录活动不存在，请检查参数是否正确');
		}

		$dirSchemas = []; // 作为分类的题目
		$oSchemasById = new \stdClass;
		foreach ($oApp->dataSchemas as $oSchema) {
			if (isset($oSchema->asdir) && $oSchema->asdir === 'Y') {
				$oSchemasById->{$oSchema->id} = $oSchema;
				switch ($oSchema->type) {
				case 'single':
					if (empty($oSchema->optGroups)) {
						/* 根分类 */
						foreach ($oSchema->ops as $oOp) {
							$oRootDir = new \stdClass;
							$oRootDir->schema_id = $oSchema->id;
							$oRootDir->schema_type = 'single';
							$oRootDir->op = $oOp;
							$dirSchemas[] = $oRootDir;
						}
					} else {
						foreach ($oSchema->optGroups as $oOptGroup) {
							if (isset($oOptGroup->assocOp) && isset($oOptGroup->assocOp->v) && isset($oSchemasById->{$oOptGroup->assocOp->schemaId})) {
								$oParentSchema = $oSchemasById->{$oOptGroup->assocOp->schemaId};
								foreach ($oParentSchema->ops as $oAssocOp) {
									if ($oAssocOp->v === $oOptGroup->assocOp->v) {
										if (!isset($oAssocOp->childrenDir)) {
											$oAssocOp->childrenDir = [];
										}
										foreach ($oSchema->ops as $oOp) {
											if (isset($oOp->g) && $oOp->g === $oOptGroup->i) {
												$oAssocOp->childrenDir[] = (object) ['schema_id' => $oSchema->id, 'op' => $oOp];
											}
										}
										break;
									}
								}
							}
						}
					}
					break;
				case 'shorttext':
					$modelData = $this->model('matter\enroll\data');
					if (empty($oSchema->historyAssoc)) {
						$oOptions = new \stdClass;
						$oOptions->rid = empty($oApp->appRound) ? '' : $oApp->appRound->rid;
						$oOptions->page = 1;
						$oOptions->size = 99;
						$oResult = $modelData->bySchema($oApp, $oSchema, $oOptions);
						foreach ($oResult->records as $oRecData) {
							$oRootDir = new \stdClass;
							$oRootDir->schema_id = $oSchema->id;
							$oRootDir->schema_type = 'shorttext';
							$oRootDir->op = (object) ['v' => $oRecData->value, 'l' => $oRecData->value];
							$dirSchemas[] = $oRootDir;
						}
					} else {
						foreach ($dirSchemas as $oParentDirSchema) {
							if (in_array($oParentDirSchema->schema_id, $oSchema->historyAssoc)) {
								$aChildrenDir = [];
								$oOptions = new \stdClass;
								$oOptions->rid = empty($oApp->appRound) ? '' : $oApp->appRound->rid;
								$oOptions->page = 1;
								$oOptions->size = 99;
								$oOptions->assocData = (object) [$oParentDirSchema->schema_id => $oParentDirSchema->op->v];
								$oResult = $modelData->bySchema($oApp, $oSchema, $oOptions);
								foreach ($oResult->records as $oRecData) {
									$oChildOption = new \stdClass;
									$oChildOption = (object) ['schema_id' => $oSchema->id, 'op' => (object) ['v' => $oRecData->value, 'l' => $oRecData->value]];
									$aChildrenDir[] = $oChildOption;
								}
								$oParentDirSchema->op->childrenDir = $aChildrenDir;
							}
						}

					}
					break;
				}
			}
		}

		return new \ResponseData($dirSchemas);
	}
	/**
	 * 返回指定登记项的活动登记名单
	 */
	public function list4Schema_action($app, $page = 1, $size = 12) {
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$oUser = $this->getUser($oApp);

		// 登记数据过滤条件
		$oCriteria = $this->getPostJson();

		// 填写记录过滤条件
		$oOptions = new \stdClass;
		$oOptions->page = $page;
		$oOptions->size = $size;

		!empty($oCriteria->keyword) && $oOptions->keyword = $oCriteria->keyword;
		!empty($oCriteria->rid) && $oOptions->rid = $oCriteria->rid;
		!empty($oCriteria->agreed) && $oOptions->agreed = $oCriteria->agreed;
		!empty($oCriteria->owner) && $oOptions->owner = $oCriteria->owner;
		!empty($oCriteria->userGroup) && $oOptions->userGroup = $oCriteria->userGroup;
		!empty($oCriteria->tag) && $oOptions->tag = $oCriteria->tag;
		if (empty($oCriteria->schema)) {
			$oOptions->schemas = [];
			foreach ($oApp->dataSchemas as $dataSchema) {
				if (isset($dataSchema->shareable) && $dataSchema->shareable === 'Y') {
					$oOptions->schemas[] = $dataSchema->id;
				}
			}
			if (empty($oOptions->schemas)) {
				return new \ResponseData(['total' => 0]);
			}
		} else {
			$oOptions->schemas = [$oCriteria->schema];
		}

		// 查询结果
		$mdoelData = $this->model('matter\enroll\data');
		$oResult = $mdoelData->byApp($oApp, $oUser, $oOptions);
		if (count($oResult->records)) {
			/* 处理获得的数据 */
			$modelRem = $this->model('matter\enroll\remark');
			foreach ($oResult->records as $oRecData) {
				if ($oRecData->remark_num) {
					$agreedRemarks = $modelRem->listByRecord($oUser, $oRecData->enroll_key, $oRecData->schema_id, $page = 1, $size = 10, ['agreed' => 'Y', 'fields' => 'id,content,create_at,nickname,like_num,like_log']);
					if ($agreedRemarks->total) {
						$oRecData->agreedRemarks = $agreedRemarks;
					}
				}
				$oRecData->tag = empty($oRecData->tag) ? [] : json_decode($oRecData->tag);
			}
		}

		return new \ResponseData($oResult);
	}
	/**
	 * 返回指定记录活动，指定登记项的填写内容
	 *
	 * @param string $app
	 * @param string $schema schema'id
	 * @param string $rid 轮次id，如果不指定为当前轮次，如果为ALL，所有轮次
	 * @param string $onlyMine 只返回当前用户自己的
	 *
	 */
	public function dataBySchema_action($app, $schema, $rid = '', $onlyMine = 'N', $page = 1, $size = 10) {
		$schemaIds = explode(',', $schema);
		if (empty($schemaIds)) {
			return new \ParameterError('没有指定有效参数');
		}

		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		if (empty($oApp->dataSchemas)) {
			return new \ResponseError('活动【' . $oApp->title . '】没有定义登记项');
		}
		$oSchemas = [];
		foreach ($oApp->dataSchemas as $dataSchema) {
			if (in_array($dataSchema->id, $schemaIds)) {
				$oSchemas[] = $dataSchema;
			}
		}
		if (empty($oSchemas)) {
			return new \ObjectNotFoundError();
		}

		$oRecData = $this->getPostJson();
		$modelData = $this->model('matter\enroll\data');
		$oResult = new \stdClass;

		$oOptions = new \stdClass;
		$oOptions->rid = $rid;
		$oOptions->page = $page;
		$oOptions->size = $size;
		if (count((array) $oRecData)) {
			$oOptions->assocData = $oRecData;
		}

		foreach ($oSchemas as $oSchema) {
			$oResult->{$oSchema->id} = $modelData->bySchema($oApp, $oSchema, $oOptions);
		}

		return new \ResponseData($oResult);
	}
	/**
	 * 按照活动规则是否需要隐藏记录的用户名称
	 */
	private function _requireAnonymous($oApp) {
		$bAnonymous = false;
		if (isset($oApp->actionRule->record->anonymous)) {
			$oRule = $oApp->actionRule->record->anonymous;
			/* 记录点赞截止时间关联 */
			if (!empty($oRule->time->record->like->end)) {
				if (isset($oApp->actionRule->record->like->end->time)) {
					$oRule2 = $oApp->actionRule->record->like->end->time;
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

		return $bAnonymous;
	}
	/**
	 * 按照活动规则是否只能查看同组数据
	 */
	private function _requireSameGroup($oApp) {
		$bSameGroup = false;
		if (isset($oApp->actionRule->record->group)) {
			$oRule = $oApp->actionRule->record->group;
			/* 记录点赞截止时间关联 */
			if (!empty($oRule->time->record->like->end)) {
				if (isset($oApp->actionRule->record->like->end->time)) {
					$oRule2 = $oApp->actionRule->record->like->end->time;
					if (isset($oRule2->mode) && isset($oRule2->unit) && isset($oRule2->value)) {
						if ($oRule2->mode === 'after_round_start_at') {
							$modelRnd = $this->model('matter\enroll\round');
							$oActiveRnd = $modelRnd->getActive($oApp);
							if ($oActiveRnd && !empty($oActiveRnd->start_at)) {
								$endtime = (int) $oActiveRnd->start_at + (3600 * $oRule2->value);
								$bSameGroup = time() < $endtime;
							}
						}
					}
				}
			}
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
								$bSameGroup = time() < $endtime;
							}
						}
					}
				}
			}
		}

		return $bSameGroup;
	}
	/**
	 * 返回指定活动的填写记录的共享内容
	 */
	public function recordList_action($app, $page = 1, $size = 12) {
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->getUser($oApp);

		$oActionRule = $oApp->actionRule;
		/* 非同组记录显示在共享页需要的赞同数 */
		$recordReposLikeNum = 0;
		if (isset($oActionRule->record->repos->pre)) {
			$oRule = $oActionRule->record->repos->pre;
			if (!empty($oRule->record->likeNum)) {
				$recordReposLikeNum = (int) $oRule->record->likeNum;
			}
		}
		/* 协作填写显示在共享页所需点赞数量 */
		$coworkReposLikeNum = 0;
		if (isset($oActionRule->cowork->repos->pre)) {
			$oRule = $oActionRule->cowork->repos->pre;
			if (!empty($oRule->cowork->likeNum)) {
				$coworkReposLikeNum = (int) $oRule->cowork->likeNum;
			}
		}
		/* 留言显示在共享页所需点赞数量 */
		$remarkReposAgreed = ['Y'];
		$remarkReposLikeNum = 0;
		if (isset($oActionRule->remark->repos->pre)) {
			$oRule = $oActionRule->remark->repos->pre;
			if (!empty($oRule->remark->likeNum)) {
				$remarkReposLikeNum = (int) $oRule->remark->likeNum;
			}
			if (!empty($oRule->remark->agreed) && is_array($oRule->remark->agreed)) {
				$remarkReposAgreed = $oRule->remark->agreed;
			}
		}
		// 填写记录过滤条件
		$oPosted = $this->getPostJson();
		// 填写记录过滤条件
		$oOptions = new \stdClass;
		$oOptions->page = $page;
		$oOptions->size = $size;
		$oOptions->regardRemarkRoundAsRecordRound = true; // 将留言的轮次作为记录的轮次

		!empty($oPosted->keyword) && $oOptions->keyword = $oPosted->keyword;

		if (!empty($oPosted->orderby)) {
			switch ($oPosted->orderby) {
			case 'earliest':
				$oOptions->orderby = ['enroll_at asc'];
				break;
			case 'lastest':
				$oOptions->orderby = ['enroll_at'];
				break;
			case 'earliest_first':
				$oOptions->orderby = ['first_enroll_at asc'];
				break;
			case 'lastest_first':
				$oOptions->orderby = ['first_enroll_at desc'];
				break;
			case 'mostvoted':
				$oOptions->orderby = ['vote_schema_num', 'enroll_at'];
				break;
			case 'mostliked':
				$oOptions->orderby = ['like_num', 'enroll_at'];
				break;
			case 'agreed':
				$oOptions->orderby = ['agreed', 'enroll_at'];
				break;
			}
		}

		// 查询结果
		$modelRec = $this->model('matter\enroll\record');
		$oCriteria = new \stdClass;
		$oCriteria->record = new \stdClass;
		$oCriteria->record->rid = !empty($oPosted->rid) ? $oPosted->rid : 'all';

		/* 用户分组限制 */
		if (empty($oUser->is_leader) || $oUser->is_leader !== 'S') {
			$bSameGroup = $this->_requireSameGroup($oApp);
			if ($bSameGroup) {
				$oCriteria->record->group_id = isset($oUser->group_id) ? $oUser->group_id : '';
			} else if ($recordReposLikeNum) {
				/* 限制同组数据或赞同数大于等于 */
				$oCriteria->GroupOrLikeNum = new \stdClass;
				$oCriteria->GroupOrLikeNum->group_id = isset($oUser->group_id) ? $oUser->group_id : '';
				$oCriteria->GroupOrLikeNum->like_num = $recordReposLikeNum;
			}
		}
		/* 指定了分组过滤条件 */
		if (!isset($oCriteria->record->group_id) && !isset($oCriteria->GroupOrLikeNum)) {
			if (!empty($oPosted->userGroup)) {
				$oCriteria->record->group_id = $oPosted->userGroup;
			}
		}
		/* 记录的创建人 */
		if (!empty($oPosted->mine) && $oPosted->mine === 'creator') {
			$oCriteria->record->userid = $oUser->uid;
		} else if (!empty($oPosted->mine) && $oPosted->mine === 'favored') {
			// 当前用户收藏
			$oCriteria->record->favored = true;
		}
		/* 记录的表态 */
		if (!empty($oPosted->agreed) && stripos($oPosted->agreed, 'all') === false) {
			$oCriteria->record->agreed = $oPosted->agreed;
		}
		/* 记录的标签 */
		if (!empty($oPosted->tags)) {
			$oCriteria->record->tags = $oPosted->tags;
		}
		!empty($oPosted->data) && $oCriteria->data = $oPosted->data;

		/* 答案的筛选 */
		if (!empty($oPosted->coworkAgreed) && stripos($oPosted->coworkAgreed, 'all') === false) {
			foreach ($oApp->dynaDataSchemas as $oSchema) {
				if (isset($oSchema->cowork) && $oSchema->cowork === 'Y') {
					$oCriteria->cowork = new \stdClass;
					$oCriteria->cowork->agreed = $oPosted->coworkAgreed;
					break;
				}
			}
		}

		$oEditor = null; // 作为编辑用户的信息

		$oResult = $modelRec->byApp($oApp, $oOptions, $oCriteria, $oUser);
		if (!empty($oResult->records)) {
			$modelData = $this->model('matter\enroll\data');
			$modelTag = $this->model('matter\enroll\tag2');
			/* 是否限制了匿名规则 */
			$bAnonymous = $this->_requireAnonymous($oApp);
			/* 是否设置了编辑组统一名称 */
			if (isset($oActionRule->role->editor->group)) {
				if (isset($oActionRule->role->editor->nickname)) {
					$oEditor = new \stdClass;
					$oEditor->group = $oActionRule->role->editor->group;
					$oEditor->nickname = $oActionRule->role->editor->nickname;
					// 如果记录活动指定了编辑组需要获取，编辑组中所有的用户
					$modelGrpUsr = $this->model('matter\group\player');
					$groupEditor = $modelGrpUsr->byApp($oApp->entryRule->group->id, ['roleRoundId' => $oEditor->group, 'fields' => 'role_rounds,userid']);
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
			$aSchareableSchemas = [];
			foreach ($oApp->dynaDataSchemas as $oSchema) {
				if (isset($oSchema->shareable) && $oSchema->shareable === 'Y') {
					$aSchareableSchemas[] = $oSchema;
				}
			}
			foreach ($oResult->records as $oRecord) {
				/* 获取记录的投票信息 */
				if (!empty($oApp->voteConfig)) {
					$aCanVoteSchemas = $this->model('matter\enroll\task', $oApp)->getVoteRule($oUser, $oRecord->round);
				}
				$aCoworkState = [];
				/* 清除非共享数据 */
				if (isset($oRecord->data)) {
					$oRecordData = new \stdClass;
					foreach ($aSchareableSchemas as $oSchema) {
						$schemaId = $oSchema->id;
						if (strpos($schemaId, 'member.extattr.') === 0) {
							$memberSchemaId = str_replace('member.extattr.', '', $schemaId);
							if (!empty($oRecord->data->member->extattr->{$memberSchemaId})) {
								if (!isset($oRecordData->member)) {
									$oRecordData->member = new \stdClass;
								}
								if (!isset($oRecordData->member->extattr)) {
									$oRecordData->member->extattr = new \stdClass;
								}
								$oRecordData->member->extattr->{$memberSchemaId} = $oRecord->data->member->extattr->{$memberSchemaId};
							}
						} else if (strpos($schemaId, 'member.') === 0) {
							$memberSchemaId = str_replace('member.', '', $schemaId);
							if (!empty($oRecord->data->member->{$memberSchemaId})) {
								if (!isset($oRecordData->member)) {
									$oRecordData->member = new \stdClass;
								}
								$oRecordData->member->{$memberSchemaId} = $oRecord->data->member->{$memberSchemaId};
							}
						} else if (!empty($oRecord->data->{$schemaId})) {
							/* 协作填写题 */
							if (isset($oSchema->cowork) && $oSchema->cowork === 'Y') {
								$aOptions = ['excludeRoot' => true, 'fields' => 'id,agreed,like_num,nickname,value,multitext_seq,vote_num,score'];
								// 展示在共享页的协作数据表态类型
								if (!empty($oApp->actionRule->cowork->repos->pre->cowork->agreed)) {
									$aOptions['agreed'] = $oApp->actionRule->cowork->repos->pre->cowork->agreed;
								} else {
									$aOptions['agreed'] = ['Y', 'A'];
								}
								$items = $modelData->getCowork($oRecord->enroll_key, $oSchema->id, $aOptions);
								if (!empty($oApp->actionRule->cowork->repos->pre->cowork->agreed)) {
									$countItems = $modelData->getCowork($oRecord->enroll_key, $oSchema->id, ['agreed' => ['Y', 'A'], 'fields' => 'id']);
									$aCoworkState[$oSchema->id] = (object) ['length' => count($countItems)];
								} else {
									$aCoworkState[$oSchema->id] = (object) ['length' => count($items)];
								}
								if ($coworkReposLikeNum) {
									$reposItems = [];
									foreach ($items as $oItem) {
										if ($oItem->like_num >= $coworkReposLikeNum || $oItem->agreed === 'Y') {
											$reposItems[] = $oItem;
										}
									}
									$items = $reposItems;
								}
								/* 当前用户投票情况 */
								if (!empty($aCanVoteSchemas[$oSchema->id])) {
									foreach ($items as $oItem) {
										$oVoteResult = new \stdClass;
										$vote_at = (int) $modelData->query_val_ss(['vote_at', 'xxt_enroll_vote', ['data_id' => $oItem->id, 'state' => 1, 'userid' => $oUser->uid]]);
										$oVoteResult->vote_at = $vote_at;
										$oVoteResult->vote_num = $oItem->vote_num;
										$oVoteResult->state = $aCanVoteSchemas[$oSchema->id]->vote->state;
										unset($oItem->vote_num);
										$oItem->voteResult = $oVoteResult;
									}
								}
								$oRecordData->{$schemaId} = $items;
							} else {
								$oRecordData->{$schemaId} = $oRecord->data->{$schemaId};
							}
						}
					}
					$oRecord->data = $oRecordData;
					if (!empty($aCoworkState)) {
						$oRecord->coworkState = (object) $aCoworkState;
					}
					/* 获取记录的投票信息 */
					if (!empty($aCanVoteSchemas)) {
						$oVoteResult = new \stdClass;
						foreach ($aCanVoteSchemas as $oCanVoteSchema) {
							if ($this->getDeepValue($oCanVoteSchema, 'cowork') === 'Y') {continue;}
							$oRecData = $modelData->byRecord($oRecord->enroll_key, ['schema' => $oCanVoteSchema->id, 'fields' => 'id,vote_num']);
							if ($oRecData) {
								$vote_at = (int) $modelData->query_val_ss(['vote_at', 'xxt_enroll_vote', ['data_id' => $oRecData->id, 'state' => 1, 'userid' => $oUser->uid]]);
								$oRecData->vote_at = $vote_at;
								$oRecData->state = $oCanVoteSchema->vote->state;
								$oVoteResult->{$oCanVoteSchema->id} = $oRecData;
							}
						}
						$oRecord->voteResult = $oVoteResult;
					}
				}
				/* 是否已经被当前用户收藏 */
				if (!empty($oUser->unionid) && $oRecord->favor_num > 0) {
					$q = ['id', 'xxt_enroll_record_favor', ['record_id' => $oRecord->id, 'favor_unionid' => $oUser->unionid, 'state' => 1]];
					if ($modelRec->query_obj_ss($q)) {
						$oRecord->favored = true;
					}
				}
				/* 记录的标签 */
				$oRecordTags = $modelTag->byRecord($oRecord, $oUser, ['UserAndPublic' => empty($oPosted->favored)]);
				if (!empty($oRecordTags->user)) {
					$oRecord->userTags = $oRecordTags->user;
				}
				if (!empty($oRecordTags->public)) {
					$oRecord->tags = $oRecordTags->public;
				}
				/* 隐藏昵称 */
				if ($bAnonymous) {
					unset($oRecord->nickname);
				} else {
					/* 修改默认访客昵称 */
					if ($oRecord->userid === $oUser->uid) {
						$oRecord->nickname = '我';
					} else if (preg_match('/用户[^\W_]{13}/', $oRecord->nickname)) {
						$oRecord->nickname = '访客';
					} else if (isset($oEditor) && (empty($oUser->is_editor) || $oUser->is_editor !== 'Y')) {
						/* 设置编辑统一昵称 */
						if (!empty($oRecord->group_id) && $oRecord->group_id === $oEditor->group) {
							$oRecord->nickname = $oEditor->nickname;
						} else if (isset($oEditorUsers) && isset($oEditorUsers->{$oRecord->userid})) {
							// 记录提交者是否有编辑组角色
							$oRecord->nickname = $oEditor->nickname;
						}
					}
				}
				/* 清除不必要的内容 */
				unset($oRecord->comment);
				unset($oRecord->verified);

				/* 获得推荐的评论数据 */
				$fnRemarksByRecord = function ($ek, $agreed, $rid = '') use ($modelRec, $oEditor, $oUser) {
					$q = [
						'id,group_id,agreed,like_num,like_log,userid,nickname,content,create_at',
						'xxt_enroll_record_remark',
						['enroll_key' => $ek, 'state' => 1, 'agreed' => $agreed],
					];
					if (!empty($rid)) {
						$q[2]['rid'] = $rid;
					}
					$q2 = [
						'o' => 'agreed desc,like_num desc,create_at desc',
					];
					$remarks = $modelRec->query_objs_ss($q, $q2);
					foreach ($remarks as $oRemark) {
						if (isset($oEditor) && (empty($oUser->is_editor) || $oUser->is_editor !== 'Y')) {
							/* 设置编辑统一昵称 */
							if (!empty($oRemark->group_id) && $oRemark->group_id === $oEditor->group) {
								$oRemark->nickname = $oEditor->nickname;
							} else if (isset($oEditorUsers) && isset($oEditorUsers->{$oRemark->userid})) {
								// 记录提交者是否有编辑组角色
								$oRemark->nickname = $oEditor->nickname;
							}
						}
					}
					return $remarks;
				};
				//if ($remarkReposLikeNum) {
				//	$q[2] .= " and (agreed in ('" . implode("','", $remarkReposAgreed) . "') or like_num>={$remarkReposLikeNum})";
				//} else {
				//	$q[2] .= " and agreed in ('" . implode("','", $remarkReposAgreed) . "')";
				//}
				/* 推荐的留言 */
				if (in_array('Y', $remarkReposAgreed)) {
					$oRecord->agreedRemarks = $fnRemarksByRecord($oRecord->enroll_key, 'Y');
				}
				/* 同一个轮次的留言 */
				if (in_array('A', $remarkReposAgreed)) {
					if (empty($oCriteria->record->rid) || 0 !== strcasecmp($oCriteria->record->rid, 'all')) {
						$rid = empty($oCriteria->record->rid) ? $oApp->appRound->rid : $oCriteria->record->rid;
						$oRecord->roundRemarks = $fnRemarksByRecord($oRecord->enroll_key, 'A', $rid);
					}
				}
			}
		}

		return new \ResponseData($oResult);
	}
	/**
	 * 返回指定活动的填写记录的共享内容
	 */
	public function recordByTopic_action($app, $topic, $page = 1, $size = 12) {
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->getUser($oApp);

		/* 非同组记录显示在共享页需要的赞同数 */
		$recordReposLikeNum = 0;
		if (isset($oApp->actionRule->record->repos->pre)) {
			$oRule = $oApp->actionRule->record->repos->pre;
			if (!empty($oRule->record->likeNum)) {
				$recordReposLikeNum = (int) $oRule->record->likeNum;
			}
		}
		/* 协作填写显示在共享页所需点赞数量 */
		$coworkReposLikeNum = 0;
		if (isset($oApp->actionRule->cowork->repos->pre)) {
			$oRule = $oApp->actionRule->cowork->repos->pre;
			if (!empty($oRule->cowork->likeNum)) {
				$coworkReposLikeNum = (int) $oRule->cowork->likeNum;
			}
		}
		/* 留言显示在共享页所需点赞数量 */
		$remarkReposAgreed = ['Y'];
		$remarkReposLikeNum = 0;
		if (isset($oApp->actionRule->remark->repos->pre)) {
			$oRule = $oApp->actionRule->remark->repos->pre;
			if (!empty($oRule->remark->likeNum)) {
				$remarkReposLikeNum = (int) $oRule->remark->likeNum;
			}
			if (!empty($oRule->remark->agreed)) {
				$remarkReposAgreed = $oRule->remark->agreed;
			}
		}

		// 登记数据过滤条件
		$oPosted = $this->getPostJson();

		// 填写记录过滤条件
		$oOptions = new \stdClass;
		$oOptions->page = $page;
		$oOptions->size = $size;

		// 查询结果
		$modelRec = $this->model('matter\enroll\record');
		$oCriteria = new \stdClass;
		$oCriteria->record = new \stdClass;
		$oCriteria->record->topic = $topic;

		/* 用户分组限制 */
		if (empty($oUser->is_leader) || $oUser->is_leader !== 'S') {
			$bSameGroup = $this->_requireSameGroup($oApp);
			if ($bSameGroup) {
				$oCriteria->record->group_id = isset($oUser->group_id) ? $oUser->group_id : '';
			} else if ($recordReposLikeNum) {
				/* 限制同组数据或赞同数大于等于 */
				$oCriteria->GroupOrLikeNum = new \stdClass;
				$oCriteria->GroupOrLikeNum->group_id = isset($oUser->group_id) ? $oUser->group_id : '';
				$oCriteria->GroupOrLikeNum->like_num = $recordReposLikeNum;
			}
		}

		$modelTop = $this->model('matter\enroll\topic', $oApp);
		$oTopic = $modelTop->byId($topic);

		$oResult = $modelTop->records($oApp, $oTopic);
		if (!empty($oResult->records)) {
			$modelData = $this->model('matter\enroll\data');
			/* 是否限制了匿名规则 */
			$bAnonymous = $this->_requireAnonymous($oApp);
			/* 是否设置了编辑组统一名称 */
			if (isset($oApp->actionRule->role->editor->group)) {
				if (isset($oApp->actionRule->role->editor->nickname)) {
					$oEditor = new \stdClass;
					$oEditor->group = $oApp->actionRule->role->editor->group;
					$oEditor->nickname = $oApp->actionRule->role->editor->nickname;
					// 如果记录活动指定了编辑组需要获取，编辑组中所有的用户
					$modelGrpUsr = $this->model('matter\group\player');
					$groupEditor = $modelGrpUsr->byApp($oApp->entryRule->group->id, ['roleRoundId' => $oEditor->group, 'fields' => 'role_rounds,userid']);
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

			$aSchareableSchemas = [];
			foreach ($oApp->dataSchemas as $oSchema) {
				if (isset($oSchema->shareable) && $oSchema->shareable === 'Y') {
					$aSchareableSchemas[] = $oSchema;
				}
			}
			foreach ($oResult->records as $oRecord) {
				$aCoworkState = [];
				/* 清除非共享数据 */
				if (isset($oRecord->data)) {
					$oRecordData = new \stdClass;
					foreach ($aSchareableSchemas as $oSchema) {
						$schemaId = $oSchema->id;
						if (strpos($schemaId, 'member.extattr.') === 0) {
							$memberSchemaId = str_replace('member.extattr.', '', $schemaId);
							if (!empty($oRecord->data->member->extattr->{$memberSchemaId})) {
								if (!isset($oRecordData->member)) {
									$oRecordData->member = new \stdClass;
								}
								if (!isset($oRecordData->member->extattr)) {
									$oRecordData->member->extattr = new \stdClass;
								}
								$oRecordData->member->extattr->{$memberSchemaId} = $oRecord->data->member->extattr->{$memberSchemaId};
							}
						} else if (strpos($schemaId, 'member.') === 0) {
							$memberSchemaId = str_replace('member.', '', $schemaId);
							if (!empty($oRecord->data->member->{$memberSchemaId})) {
								if (!isset($oRecordData->member)) {
									$oRecordData->member = new \stdClass;
								}
								$oRecordData->member->{$memberSchemaId} = $oRecord->data->member->{$memberSchemaId};
							}
						} else if (!empty($oRecord->data->{$schemaId})) {
							/* 协作填写题 */
							if (isset($oSchema->cowork) && $oSchema->cowork === 'Y') {
								$items = $modelData->getCowork($oRecord->enroll_key, $oSchema->id, ['excludeRoot' => true, 'agreed' => ['Y', 'A'], 'fields' => 'id,agreed,like_num,nickname,value']);
								$aCoworkState[$oSchema->id] = (object) ['length' => count($items)];
								if ($coworkReposLikeNum) {
									$reposItems = [];
									foreach ($items as $oItem) {
										if ($oItem->like_num >= $coworkReposLikeNum || $oItem->agreed === 'Y') {
											$reposItems[] = $oItem;
										}
									}
									$items = $reposItems;
								}
								$oRecordData->{$schemaId} = $items;
							} else {
								$oRecordData->{$schemaId} = $oRecord->data->{$schemaId};
							}
						}
					}
					$oRecord->data = $oRecordData;
					if (!empty($aCoworkState)) {
						$oRecord->coworkState = (object) $aCoworkState;
					}
				}
				/* 是否已经被当前用户收藏 */
				if (!empty($oUser->unionid) && $oRecord->favor_num > 0) {
					$q = ['id', 'xxt_enroll_record_favor', ['record_id' => $oRecord->id, 'favor_unionid' => $oUser->unionid, 'state' => 1]];
					if ($modelRec->query_obj_ss($q)) {
						$oRecord->favored = true;
					}
				}
				/* 隐藏昵称 */
				if ($bAnonymous) {
					unset($oRecord->nickname);
				} else {
					/* 修改默认访客昵称 */
					if ($oRecord->userid === $oUser->uid) {
						$oRecord->nickname = '我';
					} else if (preg_match('/用户[^\W_]{13}/', $oRecord->nickname)) {
						$oRecord->nickname = '访客';
					} else if (isset($oEditor) && (empty($oUser->is_editor) || $oUser->is_editor !== 'Y')) {
						/* 设置编辑统一昵称 */
						if (!empty($oRecord->group_id) && $oRecord->group_id === $oEditor->group) {
							$oRecord->nickname = $oEditor->nickname;
						} else if (isset($oEditorUsers) && isset($oEditorUsers->{$oRecord->userid})) {
							// 记录提交者是否有编辑组角色
							$oRecord->nickname = $oEditor->nickname;
						}
					}
				}
				/* 清除不必要的内容 */
				unset($oRecord->comment);
				unset($oRecord->verified);
				/* 获得推荐的评论数据 */
				$q = [
					'id,group_id,agreed,like_num,like_log,userid,nickname,content,create_at',
					'xxt_enroll_record_remark',
					"enroll_key='{$oRecord->enroll_key}' and state=1",
				];
				if ($remarkReposLikeNum) {
					$q[2] .= " and (agreedin ('" . implode("','", $remarkReposAgreed) . "') or like_num>={$remarkReposLikeNum})";
				} else {
					$q[2] .= " and agreed in ('" . implode("','", $remarkReposAgreed) . "')";
				}
				$q2 = [
					'o' => 'agreed desc,like_num desc,create_at desc',
				];
				$oRecord->agreedRemarks = $modelRec->query_objs_ss($q, $q2);
				foreach ($oRecord->agreedRemarks as $oRemark) {
					if (isset($oEditor) && (empty($oUser->is_editor) || $oUser->is_editor !== 'Y')) {
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
		}

		return new \ResponseData($oResult);
	}
	/**
	 * 获得一条记录可共享的内容
	 */
	public function recordGet_action($app, $ek) {
		$modelApp = $this->model('matter\enroll');
		$modelRec = $this->model('matter\enroll\record');

		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$fields = 'id,state,aid,rid,enroll_key,userid,group_id,nickname,verified,enroll_at,first_enroll_at,supplement,score,like_num,like_log,remark_num,rec_remark_num,favor_num,agreed,data,dislike_num,dislike_log';
		$oRecord = $modelRec->byId($ek, ['fields' => $fields]);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->getUser($oApp);

		/* 是否设置了编辑组统一名称 */
		if (isset($oApp->actionRule->role->editor->group)) {
			if (isset($oApp->actionRule->role->editor->nickname)) {
				$oEditor = new \stdClass;
				$oEditor->group = $oApp->actionRule->role->editor->group;
				$oEditor->nickname = $oApp->actionRule->role->editor->nickname;
				// 如果记录活动指定了编辑组需要获取，编辑组中所有的用户
				$modelGrpUsr = $this->model('matter\group\user');
				$groupEditor = $modelGrpUsr->byApp($oApp->entryRule->group->id, ['roleRoundId' => $oEditor->group, 'fields' => 'role_rounds,userid']);
				if (isset($groupEditor->users)) {
					$groupEditorUsers = $groupEditor->users;
					$oEditorUsers = new \stdClass;
					foreach ($groupEditorUsers as $player) {
						$oEditorUsers->{$player->userid} = $player->role_rounds;
					}
					unset($groupEditorUsers);
				}
			}
		}

		if (!empty($oUser->unionid) && $oRecord->favor_num > 0) {
			$q = ['id', 'xxt_enroll_record_favor', ['record_id' => $oRecord->id, 'favor_unionid' => $oUser->unionid, 'state' => 1]];
			if ($modelRec->query_obj_ss($q)) {
				$oRecord->favored = true;
			}
		}

		/* 记录的标签 */
		$modelTag = $this->model('matter\enroll\tag2');
		$oRecordTags = $modelTag->byRecord($oRecord, $oUser);
		if (!empty($oRecordTags->user)) {
			$oRecord->userTags = $oRecordTags->user;
		}

		/* 是否限制了匿名规则 */
		$bAnonymous = $this->_requireAnonymous($oApp);
		if ($bAnonymous) {
			unset($oRecord->nickname);
		} else {
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
		}

		if (isset($oRecord->data) && !empty($oApp->dynaDataSchemas)) {
			$fnCheckSchemaVisibility = function ($oSchema, $oRecordData) {
				if (!empty($oSchema->visibility->rules)) {
					foreach ($oSchema->visibility->rules as $oRule) {
						if (strpos($oSchema->id, 'member.extattr') === 0) {
							$memberSchemaId = str_replace('member.extattr.', '', $oSchema->id);
							if (!isset($oRecordData->member->extattr->{$memberSchemaId}) || ($oRecordData->member->extattr->{$memberSchemaId} !== $oRule->op && empty($oRecordData->member->extattr->{$memberSchemaId}))) {
								return false;
							}
						} else if (!isset($oRecordData->{$oRule->schema}) || ($oRecordData->{$oRule->schema} !== $oRule->op && empty($oRecordData->{$oRule->schema}->{$oRule->op}))) {
							return false;
						}
					}
				}
				return true;
			};
			/* 清除非共享数据 */
			$oShareableSchemas = new \stdClass;
			foreach ($oApp->dynaDataSchemas as $oSchema) {
				if (isset($oSchema->shareable) && $oSchema->shareable === 'Y') {
					$oShareableSchemas->{$oSchema->id} = $oSchema;
				}
			}
			$modelRecDat = $this->model('matter\enroll\data');
			/* 避免因为清除数据导致影响数据的可见关系 */
			$oFullRecData = clone $oRecord->data;
			foreach ($oRecord->data as $schemaId => $value) {
				/* 清除空值 */
				if (!isset($oShareableSchemas->{$schemaId})) {
					unset($oRecord->data->{$schemaId});
					continue;
				}
				/* 清除不可见的题 */
				$oSchema = $oShareableSchemas->{$schemaId};
				if (!$fnCheckSchemaVisibility($oSchema, $oFullRecData)) {
					unset($oRecord->data->{$schemaId});
					continue;
				}
			}
			/* 获取记录的投票信息 */
			if (!empty($oApp->voteConfig)) {
				$aCanVoteSchemas = $this->model('matter\enroll\task', $oApp)->getVoteRule($oUser, $oRecord->round);
				$oVoteResult = new \stdClass;
				foreach ($aCanVoteSchemas as $oCanVoteSchema) {
					if ($this->getDeepValue($oCanVoteSchema, 'cowork') === 'Y') {continue;}
					$oRecData = $modelRecDat->byRecord($oRecord->enroll_key, ['schema' => $oCanVoteSchema->id, 'fields' => 'id,vote_num']);
					if ($oRecData) {
						$vote_at = (int) $modelRecDat->query_val_ss(['vote_at', 'xxt_enroll_vote', ['data_id' => $oRecData->id, 'state' => 1, 'userid' => $oUser->uid]]);
						$oRecData->vote_at = $vote_at;
						$oRecData->state = $oCanVoteSchema->vote->state;
						$oVoteResult->{$oCanVoteSchema->id} = $oRecData;
					}
				}
				$oRecord->voteResult = $oVoteResult;
			}
		}

		return new \ResponseData($oRecord);
	}
	/**
	 * 获取活动共享页筛选条件
	 */
	public function criteriaGet_action($app) {
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['fields' => 'id,siteid,state,repos_config,data_schemas,entry_rule,action_rule,mission_id,sync_mission_round,assigned_nickname,round_cron', 'cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->getUser($oApp);

		$oCriterias = $this->_originCriteriaGet();
		$result = $this->_packCriteria($oApp, $oUser, $oCriterias);
		if ($result[0] === false) {
			return new \ParameterError($result[1]);
		}

		$criterias = $result[1];
		return new \ResponseData($criterias);
	}
	/**
	 * 按当前用户角色过滤筛选条件
	 */
	private function _packCriteria($oApp, $oUser, $criterias) {
		$varType = gettype($criterias);
		if ($varType === 'object') {
			$criterias = (array) $criterias;
		} else if ($varType !== 'array') {
			return [false, '参数格式错误！'];
		}

		foreach ($criterias as $key => $criteria) {
			// 默认排序
			if ($criteria->type === 'orderby') {
				if (!empty($oApp->reposConfig->defaultOrder)) {
					foreach ($criteria->menus as $i => $v) {
						if ($v->id === $oApp->reposConfig->defaultOrder) {
							$criteria->default = $criteria->menus[$i];
							break;
						}
					}
				}
			}
			//获取轮次
			if ($criteria->type === 'rid') {
				$modelRun = $this->model('matter\enroll\round');
				$options = [
					'fields' => 'rid,title',
					'state' => ['1', '2'],
				];
				$result = $modelRun->byApp($oApp, $options);
				if (count($result->rounds) == 1) {
					unset($criterias[$key]);
				} else {
					foreach ($result->rounds as $round) {
						if ($round->rid === $result->active->rid) {
							$criteria->menus[] = (object) ['id' => $round->rid, 'title' => '(当前填写轮次) ' . $round->title];
						} else {
							$criteria->menus[] = (object) ['id' => $round->rid, 'title' => $round->title];
						}
					}
				}
			}
			// 如果有答案的题型才显示筛选答案的按钮
			if ($criteria->type === 'coworkAgreed') {
				$coworkState = false;
				foreach ($oApp->dynaDataSchemas as $oSchema) {
					if (isset($oSchema->cowork) && $oSchema->cowork === 'Y') {
						$coworkState = true;
						break;
					}
				}
				if (!$coworkState) {
					unset($criterias[$key]);
				}
			}
			// 获取分组
			if ($criteria->type === 'userGroup') {
				if (empty($oApp->entryRule->group->id)) {
					unset($criterias[$key]);
				} else {
					$assocGroupAppId = $oApp->entryRule->group->id;
					$modelGrpRnd = $this->model('matter\group\round');
					$groups = $modelGrpRnd->byApp($assocGroupAppId, ['fields' => "round_id,title"]);
					if (empty($groups)) {
						unset($criterias[$key]);
					} else {
						foreach ($groups as $group) {
							$criteria->menus[] = (object) ['id' => $group->round_id, 'title' => $group->title];
						}
					}
				}
			}
			/*
				 *表态 当用户为编辑或者超级管理员或者有组时才会出现“接受”，“关闭” ，“讨论”，“未表态” ，否则只有推荐和不限两种
			*/
			if ($criteria->type === 'agreed') {
				if (!empty($oUser->group_id) || (isset($oUser->is_leader) && $oUser->is_leader === 'S') || (isset($oUser->is_editor) && $oUser->is_editor === 'Y')) {
					$criteria->menus[] = (object) ['id' => 'A', 'title' => '接受'];
					$criteria->menus[] = (object) ['id' => 'D', 'title' => '讨论'];
					$criteria->menus[] = (object) ['id' => 'N', 'title' => '关闭'];
				}
			}
			// 只有登录用户才会显示我的记录和我的收藏
			if ($criteria->type === 'mine') {
				if (empty($oUser->unionid)) {
					unset($criterias[$key]);
				}
			}
		}

		$criterias = array_values($criterias);
		return [true, $criterias];
	}
	/**
	 * 获得所有条件
	 */
	private function _originCriteriaGet() {
		$criterias = [];
		// 排序
		$orderby = new \stdClass;
		$orderby->type = 'orderby';
		$orderby->title = '排序';
		$orderby->menus = [
			(object) ['id' => 'lastest', 'title' => '最近提交'],
			(object) ['id' => 'earliest', 'title' => '最早提交'],
			(object) ['id' => 'mostliked', 'title' => '最多赞同'],
			(object) ['id' => 'mostvoted', 'title' => '最多投票'],
		];
		$orderby->default = $orderby->menus[0];
		$criterias[] = $orderby;
		// 协作
		$coworkAgreed = new \stdClass;
		$coworkAgreed->type = 'coworkAgreed';
		$coworkAgreed->title = '协作';
		$coworkAgreed->menus = [
			(object) ['id' => null, 'title' => '所有问题'],
			(object) ['id' => 'answer', 'title' => '已回答'],
			(object) ['id' => 'unanswer', 'title' => '等待回答'],
		];
		$coworkAgreed->default = $coworkAgreed->menus[0];
		$criterias[] = $coworkAgreed;
		// 轮次
		$round = new \stdClass;
		$round->type = 'rid';
		$round->title = '轮次';
		$round->menus = [
			(object) ['id' => null, 'title' => '不限轮次'],
		];
		$round->default = $round->menus[0];
		$criterias[] = $round;
		// 分组
		$group = new \stdClass;
		$group->type = 'userGroup';
		$group->title = '分组';
		$group->menus = [
			(object) ['id' => null, 'title' => '不限分组'],
		];
		$group->default = $group->menus[0];
		$criterias[] = $group;
		// 表态
		$agreed = new \stdClass;
		$agreed->type = 'agreed';
		$agreed->title = '表态';
		$agreed->menus = [
			(object) ['id' => null, 'title' => '不限表态'],
			(object) ['id' => 'Y', 'title' => '推荐'],
		];
		$agreed->default = $agreed->menus[0];
		$criterias[] = $agreed;
		// 我的
		$mine = new \stdClass;
		$mine->type = 'mine';
		$mine->title = '我的';
		$mine->menus = [
			(object) ['id' => null, 'title' => '不限'],
			(object) ['id' => 'creator', 'title' => '我的记录'],
			(object) ['id' => 'favored', 'title' => '我的收藏'],
		];
		$mine->default = $mine->menus[0];
		$criterias[] = $mine;

		return $criterias;
	}
}