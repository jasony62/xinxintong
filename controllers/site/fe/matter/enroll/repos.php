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
		$app = $this->escape($app);
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
		$oUser = $this->who;

		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['fields' => 'id,state,data_schemas', 'cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
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
		if ($onlyMine === 'Y') {
			//$oOptions->userid = $this->who->uid;
		}
		$oResult = $modelData->bySchema($oApp, $oSchema, $oOptions);

		return new \ResponseData($oResult);
	}
	/**
	 * 返回指定活动的登记记录的共享内容
	 */
	public function recordList_action($app, $page = 1, $size = 12) {
		$oUser = $this->who;

		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['fields' => 'id,state,scenario,assigned_nickname,data_schemas', 'cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		// 登记数据过滤条件
		$oPosted = $this->getPostJson();

		// 登记记录过滤条件
		$oOptions = new \stdClass;
		$oOptions->page = $page;
		$oOptions->size = $size;
		$oOptions->orderby = 'agreed';
		!empty($oPosted->keyword) && $oOptions->keyword = $oPosted->keyword;

		// 查询结果
		$mdoelRec = $this->model('matter\enroll\record');
		$oCriteria = new \stdClass;
		$oCriteria->record = new \stdClass;
		!empty($oPosted->rid) && $oCriteria->record->rid = $oPosted->rid;
		!empty($oPosted->userGroup) && $oCriteria->record->group_id = $oPosted->userGroup;
		if (!empty($oPosted->creator) && $oPosted->creator !== 'all') {
			$oCriteria->record->user_id = $oPosted->creator;
		}
		!empty($oPosted->data) && $oCriteria->data = $oPosted->data;

		$oResult = $mdoelRec->byApp($oApp, $oOptions, $oCriteria);
		if (!empty($oResult->records)) {
			$aSchareableSchemas = [];
			foreach ($oApp->dataSchemas as $oSchema) {
				if (isset($oSchema->shareable) && $oSchema->shareable === 'Y') {
					$aSchareableSchemas[] = $oSchema->id;
				}
			}
			foreach ($oResult->records as $oRecord) {
				/* 清除非共享数据 */
				if (isset($oRecord->data)) {
					$oRecordData = new \stdClass;
					foreach ($aSchareableSchemas as $schemaId) {
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
							$oRecordData->{$schemaId} = $oRecord->data->{$schemaId};
						}
					}
					$oRecord->data = $oRecordData;
				}
				/* 清除不必要的内容 */
				unset($oRecord->comment);
				unset($oRecord->verified);
				unset($oRecord->wx_openid);
				unset($oRecord->yx_openid);
				unset($oRecord->qy_openid);
				unset($oRecord->headimgurl);
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

		$oApp = $modelApp->byId($app, ['cascaded' => 'N', 'fields' => 'id,state,data_schemas']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$fields = 'id,aid,state,enroll_key,userid,group_id,nickname,verified,enroll_at,first_enroll_at,supplement,data_tag,score,like_num,like_log,remark_num,agreed,data';
		$oRecord = $modelRec->byId($ek, ['verbose' => 'Y', 'fields' => $fields]);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
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