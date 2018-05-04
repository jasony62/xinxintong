<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记记录数据汇总
 */
class repos extends base {
	/**
	 * 获得活动中作为内容分类目录使用的题目
	 */
	public function dirSchemasGet_action($app) {
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N', 'id,state,data_schemas']);
		if ($oApp === false || $oApp->state !== '1') {
			$this->outputError('指定的登记活动不存在，请检查参数是否正确');
		}

		$dirSchemas = [];
		$oSchemasById = new \stdClass;
		foreach ($oApp->dataSchemas as $oSchema) {
			if (isset($oSchema->asdir) && $oSchema->asdir === 'Y') {
				$oSchemasById->{$oSchema->id} = $oSchema;
				if (empty($oSchema->optGroups)) {
					/* 根分类 */
					foreach ($oSchema->ops as $oOp) {
						$oRootDir = new \stdClass;
						$oRootDir->schema_id = $oSchema->id;
						$oRootDir->op = $oOp;
						$dirSchemas[] = $oRootDir;
					}
				} else {
					foreach ($oSchema->optGroups as $oOptGroup) {
						if (isset($oOptGroup->assocOp) && isset($oOptGroup->assocOp->v) && $oSchemasById->{$oOptGroup->assocOp->schemaId}) {
							$oParentSchema = $oSchemasById->{$oOptGroup->assocOp->schemaId};
							foreach ($oParentSchema->ops as $oAssocOp) {
								if ($oAssocOp->v === $oOptGroup->assocOp->v) {
									$oAssocOp->childrenDir = [];
									foreach ($oSchema->ops as $oOp) {
										if (isset($oOp->g) && $oOp->g === $oOptGroup->i) {
											$oAssocOp->childrenDir[] = (object) ['schema_id' => $oSchema->id, 'op' => $oOp];
										}
									}
								}
							}
						}
					}
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

		// 登记记录过滤条件
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
	 * 返回指定登记活动，指定登记项的填写内容
	 *
	 * @param string $app
	 * @param string $schema schema'id
	 * @param string $rid 轮次id，如果不指定为当前轮次，如果为ALL，所有轮次
	 * @param string $onlyMine 只返回当前用户自己的
	 *
	 */
	public function dataBySchema_action($app, $schema, $rid = '', $onlyMine = 'N', $page = 1, $size = 10) {
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		if (empty($oApp->dataSchemas)) {
			return new \ResponseError('活动【' . $oApp->title . '】没有定义登记项');
		}
		foreach ($oApp->dataSchemas as $dataSchema) {
			if ($dataSchema->id === $schema) {
				$oSchema = $dataSchema;
				break;
			}
		}
		if (!isset($oSchema)) {
			return new \ObjectNotFoundError();
		}

		$modelData = $this->model('matter\enroll\data');
		$oOptions = new \stdClass;
		$oOptions->rid = $rid;
		$oOptions->page = $page;
		$oOptions->size = $size;

		$oResult = $modelData->bySchema($oApp, $oSchema, $oOptions);

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
	 * 返回指定活动的登记记录的共享内容
	 */
	public function recordList_action($app, $page = 1, $size = 12, $role = null) {
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
		$remarkReposLikeNum = 0;
		if (isset($oApp->actionRule->remark->repos->pre)) {
			$oRule = $oApp->actionRule->remark->repos->pre;
			if (!empty($oRule->remark->likeNum)) {
				$remarkReposLikeNum = (int) $oRule->remark->likeNum;
			}
		}

		$modelRnd = $this->model('matter\enroll\round');
		$oActiveRnd = $modelRnd->getActive($oApp);

		// 登记数据过滤条件
		$oPosted = $this->getPostJson();
		// 登记记录过滤条件
		$oOptions = new \stdClass;
		$oOptions->page = $page;
		$oOptions->size = $size;
		!empty($oPosted->keyword) && $oOptions->keyword = $oPosted->keyword;

		if (!empty($oPosted->orderby)) {
			switch ($oPosted->orderby) {
			case 'earliest':
				$oOptions->orderby = ['enroll_at asc'];
				break;
			case 'earliest_first':
				$oOptions->orderby = ['first_enroll_at asc'];
				break;
			case 'lastest_first':
				$oOptions->orderby = ['first_enroll_at desc'];
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
		!empty($oPosted->rid) && $oCriteria->record->rid = $oPosted->rid;

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
		if (!empty($oPosted->creator)) {
			$oCriteria->record->userid = $oUser->uid;
		}
		/* 记录的表态 */
		if (!empty($oPosted->agreed) && $oPosted->agreed !== 'all') {
			$oCriteria->record->agreed = $oPosted->agreed;
		}
		!empty($oPosted->data) && $oCriteria->data = $oPosted->data;

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

		$oResult = $modelRec->byApp($oApp, $oOptions, $oCriteria, $oMockUser);
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
								$items = $modelData->getMultitext($oRecord->enroll_key, $oSchema->id, ['excludeRoot' => true, 'agreed' => ['Y', 'A'], 'fields' => 'id,agreed,like_num,nickname,value']);
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
						}
					}
				}
				/* 清除不必要的内容 */
				unset($oRecord->comment);
				unset($oRecord->verified);
				unset($oRecord->wx_openid);
				unset($oRecord->yx_openid);
				unset($oRecord->qy_openid);
				unset($oRecord->headimgurl);
				/* 获得推荐的评论数据 */
				$q = [
					'id,group_id,agreed,like_num,like_log,nickname,content,create_at',
					'xxt_enroll_record_remark',
					"enroll_key='{$oRecord->enroll_key}' and state=1",
				];
				if ($remarkReposLikeNum) {
					$q[2] .= " and (agreed='Y' or like_num>={$remarkReposLikeNum})";
				} else {
					$q[2] .= " and agreed='Y'";
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

		$oUser = $this->getUser($oApp);

		/* 是否设置了编辑组统一名称 */
		if (isset($oApp->actionRule->role->editor->group)) {
			if (isset($oApp->actionRule->role->editor->nickname)) {
				$oEditor = new \stdClass;
				$oEditor->group = $oApp->actionRule->role->editor->group;
				$oEditor->nickname = $oApp->actionRule->role->editor->nickname;
			}
		}

		$fields = 'id,aid,state,enroll_key,userid,group_id,nickname,verified,enroll_at,first_enroll_at,supplement,data_tag,score,like_num,like_log,remark_num,rec_remark_num,agreed,data';
		$oRecord = $modelRec->byId($ek, ['verbose' => 'Y', 'fields' => $fields]);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
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
					}
				}
			}
		}

		if (isset($oRecord->verbose)) {
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
			foreach ($oApp->dataSchemas as $oSchema) {
				if (isset($oSchema->shareable) && $oSchema->shareable === 'Y') {
					$oShareableSchemas->{$oSchema->id} = $oSchema;
				}
			}
			foreach ($oRecord->verbose as $schemaId => $value) {
				/* 清除空值 */
				if (!isset($oShareableSchemas->{$schemaId})) {
					unset($oRecord->verbose->{$schemaId});
					continue;
				}
				/* 清除不可见的题 */
				$oSchema = $oShareableSchemas->{$schemaId};
				if (!$fnCheckSchemaVisibility($oSchema, $oRecord->data)) {
					unset($oRecord->verbose->{$schemaId});
					continue;
				}
				if ($oShareableSchemas->{$schemaId}->type === 'multitext') {
					if (!empty($oRecord->verbose->{$schemaId}->value)) {
						$oRecord->verbose->{$schemaId}->value = json_decode($oRecord->verbose->{$schemaId}->value);
					}
				}
			}
		}

		return new \ResponseData($oRecord);
	}
}