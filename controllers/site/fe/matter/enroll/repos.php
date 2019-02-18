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
		// 填写记录过滤条件
		$oPosted = $this->getPostJson();
		// 填写记录过滤条件
		$oOptions = new \stdClass;
		$oOptions->page = $page;
		$oOptions->size = $size;

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
			}
		}
		/* 指定了分组过滤条件 */
		if (!isset($oCriteria->record->group_id)) {
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

		$oResult = $modelRec->byApp($oApp, $oOptions, $oCriteria, $oUser);
		if (!empty($oResult->records)) {
			$modelData = $this->model('matter\enroll\data');
			$modelTag = $this->model('matter\enroll\tag2');
			/* 是否限制了匿名规则 */
			$bAnonymous = $this->_requireAnonymous($oApp);
			if (false === $bAnonymous) {
				/* 是否设置了编辑组 */
				$oEditorGrp = $this->getEditorGroup($oApp);
			}
			foreach ($oResult->records as $oRecord) {
				/* 获取记录的投票信息 */
				if (!empty($oApp->voteConfig)) {
					$aVoteRules = $this->model('matter\enroll\task', $oApp)->getVoteRule($oUser, $oRecord->round);
				}
				$aCoworkState = [];
				$recordDirs = [];
				/* 清除非共享数据 */
				if (isset($oRecord->data)) {
					$oRecordData = new \stdClass;
					foreach ($oApp->dynaDataSchemas as $oSchema) {
						$schemaId = $oSchema->id;
						// 分类目录
						if ($this->getDeepValue($oSchema, 'asdir') === 'Y' && !empty($oSchema->ops) && !empty($oRecord->data->{$schemaId})) {
							foreach ($oSchema->ops as $op) {
								if ($op->v === $oRecord->data->{$schemaId}) {
									$recordDirs[] = $op->l;
								}
							}
						}
						/* 清除非共享数据 */
						if (isset($oSchema->shareable) && $oSchema->shareable !== 'Y') {
							continue;
						}
						/* 协作填写题 */
						if ($this->getDeepValue($oSchema, 'cowork') === 'Y') {
							$aOptions = ['fields' => 'id', 'agreed' => ['Y', 'A']];
							$countItems = $modelData->getCowork($oRecord->enroll_key, $oSchema->id, $aOptions);
							$aCoworkState[$oSchema->id] = (object) ['length' => count($countItems)];
							continue;
						} else {
							if (null !== ($recDataVal = $this->getDeepValue($oRecord->data, $schemaId, null))) {
								$this->setDeepValue($oRecordData, $schemaId, $recDataVal);
							}
						}
					}
					$oRecord->data = $oRecordData;
					if (!empty($aCoworkState)) {
						$oRecord->coworkState = (object) $aCoworkState;
						// 协作填写题数据总数量
						$sum = 0;
						foreach ($aCoworkState as $k => $v) {
							$sum += (int) $v->length;
						}
						$oRecord->coworkDataTotal = $sum;
					}
					if (!empty($recordDirs)) {
						$oRecord->recordDir = $recordDirs;
					}
					/* 获取记录的投票信息 */
					if (!empty($aVoteRules)) {
						$oVoteResult = new \stdClass;
						foreach ($aVoteRules as $schemaId => $oVoteRule) {
							if ($this->getDeepValue($oVoteRule->schema, 'cowork') === 'Y') {continue;}
							$oRecData = $modelData->byRecord($oRecord->enroll_key, ['schema' => $schemaId, 'fields' => 'id,vote_num']);
							if ($oRecData) {
								$oVoteResult->{$schemaId} = $oRecData;
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
				/* 设置昵称 */
				if ($bAnonymous) {
					unset($oRecord->nickname);
				} else {
					$this->setNickname($oRecord, $oUser, isset($oEditorGrp) ? $oEditorGrp : null);
				}
				/* 清除不必要的内容 */
				unset($oRecord->comment);
				unset($oRecord->verified);
			}
		}
		// 记录搜索事件
		if (!empty($oPosted->keyword)) {
			$rest = $this->model('matter\enroll\search')->addUserSearch($oApp, $oUser, $oPosted->keyword);
			// 记录日志
			$this->model('matter\enroll\event')->searchRecord($oApp, $rest['search'], $oUser);
		}

		return new \ResponseData($oResult);
	}
	/**
	 * 返回指定活动的填写记录的共享内容
	 * 答案视图
	 */
	public function coworkDataList_action($app, $page = 1, $size = 12) {
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$coworkSchemaIds = [];
		foreach ($oApp->dynaDataSchemas as $oSchema) {
			if (isset($oSchema->cowork) && $oSchema->cowork === 'Y') {
				$coworkSchemaIds[] = $oSchema->id;
			}
		}
		if (empty($coworkSchemaIds)) {
			return new \ObjectNotFoundError('活动中没有协作题');
		}

		$oUser = $this->getUser($oApp);
		// 填写记录过滤条件
		$oOptions = new \stdClass;
		$oOptions->page = $page;
		$oOptions->size = $size;
		$oOptions->regardRemarkRoundAsRecordRound = true; // 将留言的轮次作为记录的轮次

		$oPosted = $this->getPostJson();
		if (!empty($oPosted->orderby)) {
			switch ($oPosted->orderby) {
			case 'earliest':
				$oOptions->orderby = ['submit_at asc'];
				break;
			case 'lastest':
				$oOptions->orderby = ['submit_at'];
				break;
			case 'mostvoted':
				$oOptions->orderby = ['vote_num', 'submit_at'];
				break;
			case 'mostliked':
				$oOptions->orderby = ['like_num', 'submit_at'];
				break;
			case 'agreed':
				$oOptions->orderby = ['agreed', 'submit_at'];
				break;
			}
		}

		// 查询结果
		$modelRecDat = $this->model('matter\enroll\data');
		$oCriteria = new \stdClass;
		!empty($oPosted->keyword) && $oCriteria->keyword = $oPosted->keyword;
		// 按指定题的值筛选
		!empty($oPosted->data) && $oCriteria->data = $oPosted->data;

		$oActionRule = $oApp->actionRule;

		$oCriteria->recordData = new \stdClass;
		$oCriteria->recordData->rid = !empty($oPosted->rid) ? $oPosted->rid : 'all';

		/* 用户分组限制 */
		if (empty($oUser->is_leader) || $oUser->is_leader !== 'S') {
			$bSameGroup = $this->_requireSameGroup($oApp);
			if ($bSameGroup) {
				$oCriteria->recordData->group_id = isset($oUser->group_id) ? $oUser->group_id : '';
			}
		}
		/* 指定了分组过滤条件 */
		if (!isset($oCriteria->recordData->group_id)) {
			if (!empty($oPosted->userGroup)) {
				$oCriteria->recordData->group_id = $oPosted->userGroup;
			}
		}
		/* 答案的创建人 */
		if (!empty($oPosted->mine) && $oPosted->mine === 'creator') {
			$oCriteria->recordData->userid = $oUser->uid;
		}
		/* 答案的表态 */
		if (!empty($oPosted->agreed) && stripos($oPosted->agreed, 'all') === false) {
			$oCriteria->recordData->agreed = $oPosted->agreed;
		}

		$oResult = $modelRecDat->coworkDataByApp($oApp, $oOptions, $oCriteria, $oUser, 'cowork');
		if (!empty($oResult->recordDatas)) {
			$modelData = $this->model('matter\enroll\data');
			/* 是否限制了匿名规则 */
			$bAnonymous = $this->_requireAnonymous($oApp);
			if (false === $bAnonymous) {
				$oEditorGrp = $this->getEditorGroup($oApp);
			}

			foreach ($oResult->recordDatas as $oRecData) {
				/* 获取记录的投票信息 */
				if (!empty($oApp->voteConfig)) {
					$aVoteRules = $this->model('matter\enroll\task', $oApp)->getVoteRule($oUser, $oRecData->round);
				}
				$recordDirs = [];
				/* 清除非共享数据 */
				if (isset($oRecData->data)) {
					$oNewRecData = new \stdClass;
					foreach ($oApp->dynaDataSchemas as $oSchema) {
						$schemaId = $oSchema->id;
						// 分类目录
						if ($this->getDeepValue($oSchema, 'asdir') === 'Y' && !empty($oSchema->ops) && !empty($oRecData->data->{$schemaId})) {
							foreach ($oSchema->ops as $op) {
								if ($op->v === $oRecData->data->{$schemaId}) {
									$recordDirs[] = $op->l;
								}
							}
						}
						/* 清除非共享数据 */
						if ($this->getDeepValue($oSchema, 'shareable') !== 'Y') {
							continue;
						}
						/* 协作填写题 */
						if ($this->getDeepValue($oSchema, 'cowork') === 'Y') {
							$item = new \stdClass;
							$item->id = $oRecData->data_id;
							$item->value = $oRecData->value;
							$this->setDeepValue($oNewRecData, $schemaId, [$item]);
							unset($oRecData->value);
						} else if (null !== ($recDataVal = $this->getDeepValue($oRecData->data, $schemaId, null))) {
							$this->setDeepValue($oNewRecData, $schemaId, $recDataVal);
						}
					}
					$oRecData->data = $oNewRecData;
					if (!empty($recordDirs)) {
						$oRecData->recordDir = $recordDirs;
					}
				}
				/* 设置昵称 */
				if ($bAnonymous) {
					unset($oRecData->nickname);
				} else {
					$this->setNickname($oRecData, $oUser, isset($oEditorGrp) ? $oEditorGrp : null);
				}
				/* 答案关联素材 */
				if (!isset($modelAss)) {
					$modelAss = $this->model('matter\enroll\assoc');
					$oAssocsOptions = [
						'fields' => 'id,assoc_mode,assoc_num,first_assoc_at,last_assoc_at,entity_a_id,entity_a_type,entity_b_id,entity_b_type,public,assoc_text,assoc_reason',
					];
				}
				$entityA = new \stdClass;
				$entityA->id = $oRecData->data_id;
				$entityA->type = 'data';
				$oAssocsOptions['entityA'] = $entityA;
				$record = new \stdClass;
				$record->id = $oRecData->record_id;
				$oAssocs = $modelAss->byRecord($record, $oUser, $oAssocsOptions);
				if (count($oAssocs)) {
					foreach ($oAssocs as $oAssoc) {
						$modelAss->adapt($oAssoc);
					}
				}
				$oRecData->oAssocs = $oAssocs;
				//
				$oRecData->id = $oRecData->record_id;
			}
		}

		// 记录搜索事件
		if (!empty($oPosted->keyword)) {
			$rest = $this->model('matter\enroll\search')->addUserSearch($oApp, $oUser, $oPosted->keyword);
			// 记录日志
			$this->model('matter\enroll\event')->searchRecord($oApp, $rest['search'], $oUser);
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
			}
		}

		$modelTop = $this->model('matter\enroll\topic', $oApp);
		$oTopic = $modelTop->byId($topic);

		$oResult = $modelTop->records($oTopic, ['fields' => $modelRec::REPOS_FIELDS]);
		if (!empty($oResult->records)) {
			$modelData = $this->model('matter\enroll\data');
			/* 是否限制了匿名规则 */
			$bAnonymous = $this->_requireAnonymous($oApp);
			/* 是否设置了编辑组 */
			if (false === $bAnonymous) {
				$oEditorGrp = $this->getEditorGroup($oApp);
			}
			/* 获取记录的投票信息 */
			if (!empty($oApp->voteConfig)) {
				$aVoteRules = $this->model('matter\enroll\task', $oApp)->getVoteRule($oUser);
			}
			foreach ($oResult->records as $oRecord) {
				$aCoworkState = [];
				$recordDirs = [];
				/* 清除非共享数据 */
				if (isset($oRecord->data)) {
					$oRecData = new \stdClass;
					foreach ($oApp->dynaDataSchemas as $oSchema) {
						$schemaId = $oSchema->id;
						/* 分类目录 */
						if ($this->getDeepValue($oSchema, 'asdir') === 'Y' && !empty($oRecord->data->{$schemaId})) {
							foreach ($oSchema->ops as $op) {
								if ($op->v === $oRecord->data->{$schemaId}) {
									$recordDirs[] = $op->l;
								}
							}
						}
						/* 清除非共享数据 */
						if ($this->getDeepValue($oSchema, 'shareable') !== 'Y') {
							continue;
						}
						/* 协作填写题 */
						if ($this->getDeepValue($oSchema, 'cowork') === 'Y') {
							$items = $modelData->getCowork($oRecord->enroll_key, $oSchema->id, ['excludeRoot' => true, 'agreed' => ['Y', 'A'], 'fields' => 'id,agreed,like_num,nickname,value']);
							$aCoworkState[$oSchema->id] = (object) ['length' => count($items)];
							$oRecData->{$schemaId} = $items;
						} else {
							if (null !== ($recDataVal = $this->getDeepValue($oRecord->data, $schemaId, null))) {
								$this->setDeepValue($oRecData, $schemaId, $recDataVal);
							}
						}
					}
					$oRecord->data = $oRecData;
					if (!empty($aCoworkState)) {
						$oRecord->coworkState = (object) $aCoworkState;
					}
					if (!empty($recordDirs)) {
						$oRecord->recordDir = $recordDirs;
					}
				}
				/* 是否已经被当前用户收藏 */
				if (!empty($oUser->unionid) && $oRecord->favor_num > 0) {
					$q = ['id', 'xxt_enroll_record_favor', ['record_id' => $oRecord->id, 'favor_unionid' => $oUser->unionid, 'state' => 1]];
					if ($modelRec->query_obj_ss($q)) {
						$oRecord->favored = true;
					}
				}
				/* 设置昵称 */
				if ($bAnonymous) {
					unset($oRecord->nickname);
				} else {
					$this->setNickname($oRecord, $oUser, isset($oEditorGrp) ? $oEditorGrp : null);
				}
				/* 获取记录的投票信息 */
				if (!empty($aVoteRules)) {
					$oVoteResult = new \stdClass;
					foreach ($aVoteRules as $schemaId => $oVoteRule) {
						if ($this->getDeepValue($oVoteRule->schema, 'cowork') === 'Y') {continue;}
						$oRecData = $modelData->byRecord($oRecord->enroll_key, ['schema' => $schemaId, 'fields' => 'id,vote_num']);
						if ($oRecData) {
							$vote_at = (int) $modelData->query_val_ss(['vote_at', 'xxt_enroll_vote', ['rid' => $oApp->appRound->rid, 'data_id' => $oRecData->id, 'state' => 1, 'userid' => $oUser->uid]]);
							$oRecData->vote_at = $vote_at;
							$oRecData->state = $oVoteRule->state;
							$oVoteResult->{$schemaId} = $oRecData;
						}
					}
					$oRecord->voteResult = $oVoteResult;
				}
			}
			/**
			 * 根据任务进行排序
			 * 1、投票任务结束后，根据投票数排序
			 */
			if (!empty($oTopic->task_id)) {
				$oTask = $this->model('matter\enroll\task', $oApp)->byId($oTopic->task_id);
				if ($oTask->state === 'AE') {
					if ($oTask->config_type === 'vote' && !empty($oTask->schemas)) {
						$p = 'voteResult.' . $oTask->schemas[0] . '.vote_num';
						usort($oResult->records, function ($a, $b) use ($p) {
							$anum = $this->getDeepValue($a, $p, 0);
							$bnum = $this->getDeepValue($b, $p, 0);
							return $bnum - $anum;
						});
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

		/* 设置昵称 */
		if ($this->_requireAnonymous($oApp)) {
			unset($oRecord->nickname);
		} else {
			$oEditorGrp = $this->getEditorGroup($oApp);
			$this->setNickname($oRecord, $oUser, $oEditorGrp);
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
			/* 清除非共享数据 以及获取分类目录*/
			$recordDirs = [];
			$oShareableSchemas = new \stdClass;
			foreach ($oApp->dynaDataSchemas as $oSchema) {
				if (isset($oSchema->shareable) && $oSchema->shareable === 'Y') {
					$oShareableSchemas->{$oSchema->id} = $oSchema;
				}
				$schemaId2 = $oSchema->id;
				if (!empty($oSchema->asdir) && $oSchema->asdir === 'Y' && !empty($oSchema->ops) && !empty($oRecord->data->{$schemaId2})) {
					foreach ($oSchema->ops as $op) {
						if ($op->v === $oRecord->data->{$schemaId2}) {
							$recordDirs[] = $op->l;
						}
					}
				}
			}
			if (!empty($recordDirs)) {
				$oRecord->recordDir = $recordDirs;
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
				$aVoteRules = $this->model('matter\enroll\task', $oApp)->getVoteRule($oUser, $oRecord->round);
				$oVoteResult = new \stdClass;
				foreach ($aVoteRules as $schemaId => $oVoteRule) {
					if ($this->getDeepValue($oVoteRule, 'schema.cowork') === 'Y') {continue;}
					$oRecData = $modelRecDat->byRecord($oRecord->enroll_key, ['schema' => $schemaId, 'fields' => 'id,vote_num']);
					if ($oRecData) {
						$vote_at = (int) $modelRecDat->query_val_ss(['vote_at', 'xxt_enroll_vote', ['data_id' => $oRecData->id, 'state' => 1, 'userid' => $oUser->uid]]);
						$oRecData->vote_at = $vote_at;
						$oVoteResult->{$schemaId} = $oRecData;
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
	public function criteriaGet_action($app, $viewType = 'record') {
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['fields' => 'id,siteid,state,repos_config,data_schemas,entry_rule,action_rule,mission_id,sync_mission_round,assigned_nickname,round_cron', 'cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->getUser($oApp);

		$oCriterias = $this->_originCriteriaGet();
		$result = $this->_packCriteria($oApp, $oUser, $oCriterias, $viewType);
		if ($result[0] === false) {
			return new \ParameterError($result[1]);
		}

		$criterias = $result[1];
		return new \ResponseData($criterias);
	}
	/**
	 * 按当前用户角色过滤筛选条件
	 */
	private function _packCriteria($oApp, $oUser, $criterias, $viewType = 'record') {
		$varType = gettype($criterias);
		if ($varType === 'object') {
			$criterias = (array) $criterias;
		} else if ($varType !== 'array') {
			return [false, '参数格式错误！'];
		}

		foreach ($criterias as $key => $oCriteria) {
			// 默认排序
			if ($oCriteria->type === 'orderby') {
				if ($viewType === 'topic') {
					$oCriteria->menus = [];
					$oCriteria->menus[] = (object) ['id' => 'lastest', 'title' => '最近创建'];
					$oCriteria->menus[] = (object) ['id' => 'earliest', 'title' => '最早创建'];
					$oCriteria->default = $oCriteria->menus[0];
				} else {
					if (!empty($oApp->reposConfig->defaultOrder)) {
						foreach ($oCriteria->menus as $i => $v) {
							if ($v->id === $oApp->reposConfig->defaultOrder) {
								$oCriteria->default = $oCriteria->menus[$i];
								break;
							}
						}
					}
				}
			}
			//获取轮次
			if ($oCriteria->type === 'rid') {
				if ($viewType === 'topic') {
					unset($criterias[$key]);
				} else {
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
								$oCriteria->menus[] = (object) ['id' => $round->rid, 'title' => '(当前填写轮次) ' . $round->title];
							} else {
								$oCriteria->menus[] = (object) ['id' => $round->rid, 'title' => $round->title];
							}
						}
					}
				}
			}
			// 如果有答案的题型才显示筛选答案的按钮
			if ($oCriteria->type === 'coworkAgreed') {
				$coworkState = false;
				if ($viewType === 'record') {
					foreach ($oApp->dynaDataSchemas as $oSchema) {
						if (isset($oSchema->cowork) && $oSchema->cowork === 'Y') {
							$coworkState = true;
							break;
						}
					}
				}
				if (!$coworkState) {
					unset($criterias[$key]);
				}
			}
			// 获取分组
			if ($oCriteria->type === 'userGroup') {
				if ($viewType === 'topic') {
					unset($criterias[$key]);
				} else if (empty($oApp->entryRule->group->id)) {
					unset($criterias[$key]);
				} else {
					$assocGroupAppId = $oApp->entryRule->group->id;
					$modelGrpTeam = $this->model('matter\group\team');
					$groups = $modelGrpTeam->byApp($assocGroupAppId, ['fields' => "team_id,title"]);
					if (empty($groups)) {
						unset($criterias[$key]);
					} else {
						foreach ($groups as $group) {
							$oCriteria->menus[] = (object) ['id' => $group->team_id, 'title' => $group->title];
						}
					}
				}
			}
			/*
				 *表态 当用户为编辑或者超级管理员或者有组时才会出现“接受”，“关闭” ，“讨论”，“未表态” ，否则只有推荐和不限两种
			*/
			if ($oCriteria->type === 'agreed') {
				if ($viewType === 'topic') {
					unset($criterias[$key]);
				} else if (!empty($oUser->group_id) || (isset($oUser->is_leader) && $oUser->is_leader === 'S') || (isset($oUser->is_editor) && $oUser->is_editor === 'Y')) {
					$oCriteria->menus[] = (object) ['id' => 'A', 'title' => '接受'];
					$oCriteria->menus[] = (object) ['id' => 'D', 'title' => '讨论'];
					$oCriteria->menus[] = (object) ['id' => 'N', 'title' => '关闭'];
				}
			}
			// 只有登录用户才会显示我的记录和我的收藏
			if ($oCriteria->type === 'mine') {
				if (empty($oUser->unionid)) {
					unset($criterias[$key]);
				} else if ($viewType === 'record') {
					$oCriteria->menus[] = (object) ['id' => 'creator', 'title' => '我的记录'];
					$oCriteria->menus[] = (object) ['id' => 'favored', 'title' => '我的收藏'];
				} else if ($viewType === 'coworkData') {
					$oCriteria->menus[] = (object) ['id' => 'creator', 'title' => '我的回答'];
				} else {
					unset($criterias[$key]);
				}
			}
			// 搜索历史
			if ($oCriteria->type === 'keyword') {
				if ($viewType === 'topic') {
					unset($criterias[$key]);
				} else {
					$search = $this->model('matter\enroll\search')->listUserSearch($oApp, $oUser);
					$userSearchs = $search->userSearch;
					foreach ($userSearchs as $userSearch) {
						$oCriteria->menus[] = (object) ['id' => $userSearch->keyword, 'title' => $userSearch->keyword];
					}
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
		// 搜索历史
		$keyword = new \stdClass;
		$keyword->type = 'keyword';
		$keyword->title = '历史';
		$keyword->menus = [
			(object) ['id' => null, 'title' => '不限'],
		];
		$keyword->default = $keyword->menus[0];
		$criterias[] = $keyword;
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
			(object) ['id' => null, 'title' => '不限'],
		];
		$round->default = $round->menus[0];
		$criterias[] = $round;
		// 分组
		$group = new \stdClass;
		$group->type = 'userGroup';
		$group->title = '分组';
		$group->menus = [
			(object) ['id' => null, 'title' => '不限'],
		];
		$group->default = $group->menus[0];
		$criterias[] = $group;
		// 表态
		$agreed = new \stdClass;
		$agreed->type = 'agreed';
		$agreed->title = '表态';
		$agreed->menus = [
			(object) ['id' => null, 'title' => '不限'],
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
		];
		$mine->default = $mine->menus[0];
		$criterias[] = $mine;

		return $criterias;
	}
}