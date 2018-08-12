<?php
namespace matter\enroll;
/**
 *
 */
class schema_model extends \TMS_MODEL {
	/**
	 * 去除掉无效的内容
	 *
	 * 1、无效的字段
	 * 2、无效的设置，例如隐藏条件
	 *
	 * 支持的静态类型
	 * type
	 * title
	 * parent 父题目，表示题目之间的从属关系
	 * dsSchema 题目定义的来源
	 *		app 定义来源的应用
	 *		schema 定义来源的题目
	 * dynamic 是否为动态生成的题目
	 * prototype 动态题目的原始定义
	 * dsOps 动态选项来源
	 *
	 * 支持的动态属性
	 * cloneSchema
	 * referSchema
	 * referOption
	 * referRercord
	 * schema_id 通讯录id
	 */
	public function purify($aAppSchemas) {
		$validProps = ['id', 'type', 'parent', 'title', 'content', 'mediaType', 'description', 'format', 'limitChoice', 'range', 'required', 'unique', 'remarkable', 'shareable', 'supplement', 'history', 'count', 'requireScore', 'scoreMode', 'score', 'answer', 'weight', 'fromApp', 'requireCheck', 'ds', 'dsOps', 'showOpNickname', 'showOpDsLink', 'dsSchema', 'visibility', 'optGroups', 'defaultValue', 'cowork', 'filterWhiteSpace', 'ops', 'schema_id', 'asdir'];
		$validPropsBySchema = [
			'html' => ['id', 'type', 'content', 'title'],
		];

		$purified = [];
		$schemasById = [];
		foreach ($aAppSchemas as $oSchema) {
			$schemasById[$oSchema->id] = $oSchema;
		}
		foreach ($aAppSchemas as $oSchema) {
			if (isset($validPropsBySchema[$oSchema->type])) {
				foreach ($oSchema as $prop => $val) {
					if (!in_array($prop, $validPropsBySchema[$oSchema->type])) {
						unset($oSchema->{$prop});
					}
				}
			} else {
				foreach ($oSchema as $prop => $val) {
					if (!in_array($prop, $validProps)) {
						unset($oSchema->{$prop});
					}
				}
			}
			// 删除多选题答案中被删除的选项
			switch ($oSchema->type) {
			case 'multiple':
				/* 设置了答案 */
				if (!empty($oSchema->answer)) {
					if (is_array($oSchema->answer)) {
						$answers = $oSchema->answer;
						$allOptionValues = [];
						foreach ($oSchema->ops as $op) {
							$allOptionValues[] = $op->v;
						}
						$oSchema->answer = array_intersect($answers, $allOptionValues);
					} else {
						$oSchema->answer = [];
					}
				}
				/* 设置了选项数量限制 */
				if (isset($oSchema->limitChoice)) {
					if (!in_array($oSchema->limitChoice, ['Y', 'N'])) {
						unset($oSchema->limitChoice);
					} else {
						if ('Y' === $oSchema->limitChoice) {

						}
					}
				}
				break;
			case 'single':
				// 删除单选题答案中被删除的选项
				if (!empty($oSchema->answer)) {
					$del = true;
					foreach ($oSchema->ops as $op) {
						if ($op->v === $oSchema->answer) {
							$del = false;
							break;
						}
					}
					if ($del) {
						unset($oSchema->answer);
					}
				}
				break;
			}
			/* 关联到其他应用时才需要检查 */
			if (empty($oSchema->fromApp)) {
				unset($oSchema->requireCheck);
			}
			/* 题目来源 */
			if (isset($oSchema->dsSchema)) {
				foreach ($oSchema->dsSchema as $prop2 => $val2) {
					if (!in_array($prop2, ['app', 'schema', 'limit'])) {
						unset($oSchema->dsSchema->{$prop2});
					}
				}
			}
			/* 检查父题目是否存在 */
			if (isset($oSchema->parent)) {
				if (empty($oSchema->parent->id) || empty($oSchema->parent->type)) {
					unset($oSchema->parent);
				} else {
					if (empty($schemasById[$oSchema->parent->id])) {
						unset($oSchema->parent);
					} else {
						$oParentSchema = $schemasById[$oSchema->parent->id];
						if ($oSchema->parent->type !== $oParentSchema->type) {
							unset($oSchema->parent);
						}
					}
				}
			}
			/* 是否可见 */
			if (isset($oSchema->visibility)) {
				if (empty($oSchema->visibility->rules)) {
					unset($oSchema->visibility);
				} else {
					for ($i = count($oSchema->visibility->rules) - 1; $i >= 0; $i--) {
						$oRule = $oSchema->visibility->rules[$i];
						if (empty($oRule->schema) || empty($oRule->op) || empty($schemasById[$oRule->schema])) {
							array_splice($oSchema->visibility->rules, $i, 1);
						} else {
							$oDependentSchema = $schemasById[$oRule->schema];
							if (!in_array($oDependentSchema->type, ['single', 'multiple']) || empty($oDependentSchema->ops)) {
								array_splice($oSchema->visibility->rules, $i, 1);
							} else {
								$bExistent = false;
								foreach ($oDependentSchema->ops as $oOp) {
									if ($oOp->v === $oRule->op) {
										$bExistent = true;
										break;
									}
								}
								if (!$bExistent) {
									array_splice($oSchema->visibility->rules, $i, 1);
								}
							}
						}
					}
					if (empty($oSchema->visibility->rules)) {
						unset($oSchema->visibility);
					}
				}
			}
			/* 选项可见条件 */
			if (isset($oSchema->optGroups)) {
				if (empty($oSchema->optGroups)) {
					unset($oSchema->optGroups);
				} else {
					for ($i = count($oSchema->optGroups) - 1; $i >= 0; $i--) {
						$bValid = true;
						$oOptGroup = $oSchema->optGroups[$i];
						if (empty($oOptGroup->assocOp->schemaId) || empty($oOptGroup->assocOp->v) || empty($schemasById[$oOptGroup->assocOp->schemaId])) {
							$bValid = false;
						} else {
							$oDependentSchema = $schemasById[$oOptGroup->assocOp->schemaId];
							if ($oDependentSchema->type !== 'single' || empty($oDependentSchema->ops)) {
								$bValid = false;
							} else {
								$bExistent = false;
								foreach ($oDependentSchema->ops as $oOp) {
									if ($oOp->v === $oOptGroup->assocOp->v) {
										$bExistent = true;
										break;
									}
								}
								if (!$bExistent) {
									$bValid = false;
								}
							}
						}
						if (false === $bValid) {
							array_splice($oSchema->optGroups, $i, 1);
							if (!empty($oSchema->ops)) {
								foreach ($oSchema->ops as $oOp) {
									if (isset($oOp->g) && $oOp->g === $oOptGroup->i) {
										unset($oOp->g);
									}
								}
							}
						}
					}
					if (empty($oSchema->optGroups)) {
						unset($oSchema->optGroups);
					}
				}
			}
			/* 单选题和多选题默认选项 */
			if (isset($oSchema->defaultValue)) {
				if ($oSchema->type === 'single') {
					if (!is_string($oSchema->defaultValue)) {
						unset($oSchema->defaultValue);
					} else {
						$bExistent = false;
						foreach ($oSchema->ops as $oOp) {
							if ($oOp->v === $oSchema->defaultValue) {
								$bExistent = true;
								break;
							}
						}
						if (false === $bExistent) {
							unset($oSchema->defaultValue);
						}
					}
				} else if ($oSchema->type === 'multiple') {
					if (!is_object($oSchema->defaultValue)) {
						unset($oSchema->defaultValue);
					} else {
						foreach ($oSchema->defaultValue as $oOpV => $bChecked) {
							if (false === $bChecked) {
								unset($oSchema->defaultValue->$oOpV);
							} else {
								$bExistent = false;
								foreach ($oSchema->ops as $oOp) {
									if ($oOp->v === $oOpV) {
										$bExistent = true;
										break;
									}
								}
								if (false === $bExistent) {
									unset($oSchema->defaultValue->$oOpV);
								}
							}
						}
						if (count((array) $oSchema->defaultValue) === 0) {
							unset($oSchema->defaultValue);
						}
					}
				}
			}

			$purified[] = $oSchema;
		}

		return $purified;
	}
	/**
	 * 去除题目中的通讯录信息
	 */
	public function wipeMschema(&$oSchema, $oMschema) {
		if ($oSchema->type === 'member' && $oSchema->schema_id === $oMschema->id) {
			/* 更新题目 */
			$oSchema->type = 'shorttext';
			$oSchema->id = str_replace('member.', '', $oSchema->id);
			if (in_array($oSchema->id, ['name', 'mobile', 'email'])) {
				$oSchema->format = $oSchema->id;
			} else {
				$oSchema->format = '';
			}
			unset($oSchema->schema_id);

			return true;
		}

		return false;
	}
	/**
	 * 去除和其他活动的题目的关联
	 */
	public function wipeAssoc(&$oSchema, $aAssocAppIds) {
		if (isset($oSchema->fromApp) && in_array($oSchema->fromApp, $aAssocAppIds)) {
			unset($oSchema->fromApp);
			unset($oSchema->requieCheck);

			return true;
		}

		return false;
	}
	/**
	 * 设置活动题目的动态选项
	 *
	 * @param object $oApp
	 * @param object $oAppRound
	 *
	 * @return object $oApp
	 */
	public function setDynaOptions(&$oApp, $oAppRound = null) {
		if (empty($oAppRound)) {
			$oAppRound = $this->model('matter\enroll\round')->getActive($oApp, ['fields' => 'id,rid,title,start_at,end_at,mission_rid']);
		}

		foreach ($oApp->dynaDataSchemas as $oSchema) {
			if (isset($oSchema->type) && in_array($oSchema->type, ['single', 'multiple'])) {
				if (!empty($oSchema->dsOps->app->id) && !empty($oSchema->dsOps->schema->id)) {
					if (!empty($oAppRound->mission_rid)) {
						if (!isset($modelRnd)) {
							$modelRnd = $this->model('matter\enroll\round');
						}
						$oDsAppRnd = $modelRnd->byMissionRid($oSchema->dsOps->app, $oAppRound->mission_rid, ['fields' => 'rid']);
					}
					$oSchema->ops = [];
					$q = [
						'enroll_key,value,userid,nickname',
						"xxt_enroll_record_data t0",
						['state' => 1, 'aid' => $oSchema->dsOps->app->id, 'schema_id' => $oSchema->dsOps->schema->id],
					];
					/* 设置轮次条件 */
					if (!empty($oDsAppRnd)) {
						$q[2]['rid'] = $oDsAppRnd->rid;
					}
					/* 设置顾虑条件 */
					if (!empty($oSchema->dsOps->filters)) {
						foreach ($oSchema->dsOps->filters as $index => $oFilter) {
							if (!empty($oFilter->schema->id) && !empty($oFilter->schema->type)) {
								switch ($oFilter->schema->type) {
								case 'single':
									if (!empty($oFilter->schema->op->v)) {
										$tbl = 't' . ($index + 1);
										$sql = "select 1 from xxt_enroll_record_data {$tbl} where state=1 and aid='{$oSchema->dsOps->app->id}'and schema_id='{$oFilter->schema->id}' and value='{$oFilter->schema->op->v}' and t0.enroll_key={$tbl}.enroll_key";
										$q[2]['enroll_key'] = (object) ['op' => 'exists', 'pat' => $sql];
									}
									break;
								}
							}
						}
					}
					/* 处理数据 */
					$datas = $this->query_objs_ss($q);
					foreach ($datas as $index => $oRecData) {
						$oNewOp = new \stdClass;
						$oNewOp->v = 'v' . ($index + 1);
						$oNewOp->l = $oRecData->value;
						$oNewOp->referRecord = (object) [
							'schema' => (object) ['id' => $oSchema->dsOps->schema->id],
							'ds' => (object) ['ek' => $oRecData->enroll_key, 'user' => $oRecData->userid, 'nickname' => $oRecData->nickname],
						];
						$oSchema->ops[] = $oNewOp;
					}
				}
			}
		}

		return $oApp;
	}
	/**
	 * 设置活动动态题目
	 *
	 * @param object $oApp
	 * @param object $oAppRound
	 *
	 * @return object $oApp
	 */
	public function setDynaSchemas(&$oApp) {
		if (empty($oApp->appRound)) {
			$modelRnd = $this->model('matter\enroll\round');
			$oAppRound = $modelRnd->getActive($oApp, ['fields' => 'id,rid,title,start_at,end_at,mission_rid']);
		} else {
			$oAppRound = $oApp->appRound;
		}

		/* 从题目生成题目 */
		$fnMakeDynaSchemaBySchema = function ($oSchema, $oDsAppRnd, $schemaIndex, &$dynaSchemasByIndex) use ($oApp) {
			$modelEnl = $this->model('matter\enroll');
			$oTargetApp = $modelEnl->byId($oSchema->dsSchema->app->id, ['fields' => 'siteid,state,mission_id,data_schemas,sync_mission_round']);
			if (false === $oTargetApp || $oTargetApp->state !== '1') {
				return [false, '指定的目标活动不可用'];
			}
			if ($oApp->mission_id !== $oTargetApp->mission_id) {
				return [false, '仅支持在同一个项目的活动间通过记录生成题目'];
			}

			$targetSchemas = []; // 目标应用中选择的题目
			foreach ($oTargetApp->dynaDataSchemas as $oTargetSchema) {
				if (empty($oTargetSchema->dynamic) || $oTargetSchema->dynamic !== 'Y' || empty($oTargetSchema->cloneSchema->id)) {
					continue;
				}
				if ($oSchema->dsSchema->schema->id === $oTargetSchema->cloneSchema->id) {
					$targetSchemas[$oTargetSchema->id] = $oTargetSchema;
				}
			}
			if (empty($targetSchemas)) {
				return [false, '指定的题目无效'];
			}

			foreach ($targetSchemas as $oReferSchema) {
				$oNewDynaSchema = clone $oSchema;
				$oNewDynaSchema->cloneSchema = (object) ['id' => $oSchema->id, 'title' => $oSchema->title];
				$oNewDynaSchema->referSchema = (object) ['id' => $oReferSchema->id, 'title' => $oReferSchema->title, 'type' => $oReferSchema->type];
				$oNewDynaSchema->id = $oReferSchema->id;
				$oNewDynaSchema->title = $oReferSchema->title;
				$oNewDynaSchema->dynamic = 'Y';
				if (isset($oReferSchema->referRecord)) {
					$oNewDynaSchema->referRecord = $oReferSchema->referRecord;
				}
				$dynaSchemasByIndex[$schemaIndex][] = $oNewDynaSchema;

				/* 原型题目中设置了动态选项，且和题目指向了相同的题目 */
				if (!empty($oNewDynaSchema->dsOps->app->id) && !empty($oNewDynaSchema->dsOps->schema->id) && $oNewDynaSchema->dsOps->app->id === $oSchema->dsSchema->app->id && $oNewDynaSchema->dsOps->schema->id === $oSchema->dsSchema->schema->id) {
					$oNewDynaSchema->dsOps->schema->id = $oReferSchema->id;
					$oNewDynaSchema->dsOps->schema->title = $oReferSchema->title;
					$oNewDynaSchema->dsOps->schema->type = $oReferSchema->type;
				}
			}
		};

		/* 根据填写数据生成题目 */
		$fnMakeDynaSchemaByData = function ($oSchema, $oDsAppRnd, $schemaIndex, &$dynaSchemasByIndex) {
			/* 如果题目本身是动态题目，需要先生成题目 */
			$targetSchemas = [];
			if (!empty($oSchema->dsSchema->app->id)) {
				$modelEnl = $this->model('matter\enroll');
				$aTargetAppOptions = ['fields' => 'siteid,state,mission_id,data_schemas,sync_mission_round'];
				/* 设置轮次条件 */
				if (!empty($oDsAppRnd)) {
					$aTargetAppOptions['appRid'] = $oDsAppRnd->rid;
				}
				$oTargetApp = $modelEnl->byId($oSchema->dsSchema->app->id, $aTargetAppOptions);
				if (false === $oTargetApp || $oTargetApp->state !== '1') {
					return [false, '指定的目标活动不可用'];
				}
				foreach ($oTargetApp->dynaDataSchemas as $oDynaSchema) {
					if ($oDynaSchema->id === $oSchema->dsSchema->schema->id) {
						$targetSchemas[] = $oDynaSchema;
					} else if (!empty($oDynaSchema->dynamic) && $oDynaSchema->dynamic === 'Y' && !empty($oDynaSchema->cloneSchema->id) && $oDynaSchema->cloneSchema->id === $oSchema->dsSchema->schema->id) {
						$targetSchemas[] = $oDynaSchema;
					}
				}
			}

			foreach ($targetSchemas as $oTargetSchema) {
				$q = [
					'id,enroll_key,value,userid,nickname',
					"xxt_enroll_record_data t0",
					['state' => 1, 'aid' => $oSchema->dsSchema->app->id, 'schema_id' => $oTargetSchema->id],
				];
				/* 设置轮次条件 */
				if (!empty($oDsAppRnd)) {
					$q[2]['rid'] = (object) ['op' => 'or', 'pat' => ["rid='{$oDsAppRnd->rid}'", "exists (select 1 from xxt_enroll_record_remark rr where t0.enroll_key=rr.enroll_key and rr.state=1 and rr.rid='{$oDsAppRnd->rid}')"]];
				}
				/* 设置过滤条件 */
				if (!empty($oSchema->dsSchema->filters)) {
					foreach ($oSchema->dsSchema->filters as $index => $oFilter) {
						if (!empty($oFilter->schema->id) && !empty($oFilter->schema->type)) {
							switch ($oFilter->schema->type) {
							case 'single':
								if (!empty($oFilter->schema->op->v)) {
									$tbl = 't' . ($index + 1);
									$sql = "select 1 from xxt_enroll_record_data {$tbl} where state=1 and aid='{$oSchema->dsSchema->app->id}'and schema_id='{$oFilter->schema->id}' and value='{$oFilter->schema->op->v}' and t0.enroll_key={$tbl}.enroll_key";
									$q[2]['enroll_key'] = (object) ['op' => 'exists', 'pat' => $sql];
								}
								break;
							}
						}
					}
				}
				/* 处理数据 */
				$datas = $this->query_objs_ss($q);
				foreach ($datas as $index => $oRecData) {
					$oNewDynaSchema = clone $oSchema;
					$oNewDynaSchema->cloneSchema = (object) ['id' => $oSchema->id, 'title' => $oSchema->title];
					$oNewDynaSchema->id = 'dyna' . $oRecData->id;
					$oNewDynaSchema->title = $oRecData->value;
					$oNewDynaSchema->dynamic = 'Y';
					/* 记录题目的数据来源 */
					$oNewDynaSchema->referRecord = (object) [
						'schema' => (object) ['id' => $oTargetSchema->id, 'type' => $oTargetSchema->type, 'title' => $oTargetSchema->title],
						'ds' => (object) ['ek' => $oRecData->enroll_key, 'user' => $oRecData->userid, 'nickname' => $oRecData->nickname],
					];
					$dynaSchemasByIndex[$schemaIndex][] = $oNewDynaSchema;
				}
			}
		};

		/* 根据打分题获得的分数生成题目 */
		$fnMakeDynaSchemaByScore = function ($oSchema, $oDsAppRnd, $schemaIndex, &$dynaSchemasByIndex) use ($oApp) {
			$modelEnl = $this->model('matter\enroll');
			$oTargetApp = $modelEnl->byId($oSchema->dsSchema->app->id, ['fields' => 'siteid,state,mission_id,data_schemas,sync_mission_round']);
			if (false === $oTargetApp || $oTargetApp->state !== '1') {
				return [false, '指定的目标活动不可用'];
			}
			if ($oApp->mission_id !== $oTargetApp->mission_id) {
				return [false, '仅支持在同一个项目的活动间通过记录生成题目'];
			}

			$targetSchemas = []; // 目标应用中选择的题目
			foreach ($oTargetApp->dynaDataSchemas as $oTargetSchema) {
				if (empty($oTargetSchema->dynamic) || $oTargetSchema->dynamic !== 'Y' || empty($oTargetSchema->cloneSchema->id)) {
					continue;
				}
				if ($oSchema->dsSchema->schema->id === $oTargetSchema->cloneSchema->id) {
					$targetSchemas[$oTargetSchema->id] = $oTargetSchema;
				}
			}
			if (empty($targetSchemas)) {
				return [false, '指定的题目无效'];
			}

			/* 匹配的轮次 */
			$modelRnd = $this->model('matter\enroll\round');
			$oTargetAppRnd = $modelRnd->byMissionRid($oTargetApp, $oDsAppRnd->mission_rid, ['fields' => 'rid,mission_rid']);

			// 查询结果
			$modelRec = $this->model('matter\enroll\record');
			$oResult = $modelRec->score4Schema($oTargetApp, isset($oTargetAppRnd->rid) ? $oTargetAppRnd->rid : '');
			unset($oResult->sum);
			$aResult = (array) $oResult;
			uasort($aResult, function ($a, $b) {
				return (int) $b - (int) $a;
			});
			$newSchemaNum = 0;
			foreach ($aResult as $schemaId => $score) {
				if (empty($targetSchemas[$schemaId])) {
					continue;
				}
				/* 检查显示规则 */
				if (isset($oSchema->dsSchema->limit->scope)) {
					if (isset($oSchema->dsSchema->limit->num) && is_int($oSchema->dsSchema->limit->num)) {
						$limitNum = $oSchema->dsSchema->limit->num;
					} else {
						$limitNum = 1;
					}
					if ($oSchema->dsSchema->limit->scope === 'top') {
						if ($newSchemaNum >= $limitNum) {
							break;
						}
					} else if ($oSchema->dsSchema->limit->scope === 'greater') {
						if ($score < $limitNum) {
							continue;
						}
					}
				}

				$oReferSchema = $targetSchemas[$schemaId];
				$oNewDynaSchema = clone $oSchema;
				$oNewDynaSchema->cloneSchema = (object) ['id' => $oSchema->id, 'title' => $oSchema->title];
				$oNewDynaSchema->referSchema = (object) ['id' => $oReferSchema->id, 'title' => $oReferSchema->title, 'type' => $oReferSchema->type];
				$oNewDynaSchema->id = $oReferSchema->id;
				$oNewDynaSchema->title = $oReferSchema->title;
				$oNewDynaSchema->dynamic = 'Y';
				if (isset($oReferSchema->referRecord)) {
					$oNewDynaSchema->referRecord = $oReferSchema->referRecord;
				}
				$dynaSchemasByIndex[$schemaIndex][] = $oNewDynaSchema;
				$newSchemaNum++;
			}
		};

		/* 根据选择题获得的票数生成题目 */
		$fnMakeDynaSchemaByOption = function ($oSchema, $oDsAppRnd, $schemaIndex, &$dynaSchemasByIndex) use ($oApp) {
			$modelEnl = $this->model('matter\enroll');

			$oTargetApp = $modelEnl->byId($oSchema->dsSchema->app->id, ['fields' => 'siteid,state,mission_id,data_schemas,sync_mission_round']);
			if (false === $oTargetApp || $oTargetApp->state !== '1') {
				return [false, '指定的目标活动不可用'];
			}
			if ($oApp->mission_id !== $oTargetApp->mission_id) {
				return [false, '仅支持在同一个项目的活动间通过记录生成题目'];
			}

			$targetSchemas = []; // 目标应用中选择的题目
			foreach ($oTargetApp->dynaDataSchemas as $oSchema2) {
				if ($oSchema->dsSchema->schema->id === $oSchema2->id) {
					$targetSchemas[] = $oSchema2;
					break;
				}
			}
			if (empty($targetSchemas)) {
				return [false, '指定的题目无效'];
			}

			/* 匹配的轮次 */
			$oAssignedRnd = $oApp->appRound;
			if ($oAssignedRnd) {
				$modelRnd = $this->model('matter\enroll\round');
				$oTargetAppRnd = $modelRnd->byMissionRid($oTargetApp, $oAssignedRnd->mission_rid, ['fields' => 'rid,mission_rid']);
			}

			/* 目标活动的统计结果 */
			$modelRec = $this->model('matter\enroll\record');
			$aTargetData = $modelRec->getStat($oTargetApp, !empty($oTargetAppRnd) ? $oTargetAppRnd->rid : '', 'N');
			$modelDat = $this->model('matter\enroll\data');
			$newSchemas = []; // 根据记录创建的题目
			foreach ($targetSchemas as $oTargetSchema) {
				switch ($oTargetSchema->type) {
				case 'single':
				case 'multiple':
					if (!empty($aTargetData[$oTargetSchema->id]->ops)) {
						$options = $aTargetData[$oTargetSchema->id]->ops;
						usort($options, function ($a, $b) {
							return $a->c < $b->c;
						});
						if (isset($oSchema->dsSchema->limit->scope)) {
							if (isset($oSchema->dsSchema->limit->num) && is_int($oSchema->dsSchema->limit->num)) {
								$limitNum = $oSchema->dsSchema->limit->num;
							} else {
								$limitNum = 1;
							}
							switch ($oSchema->dsSchema->limit->scope) {
							case 'top':
								$this->genSchemaByTopOptions($oTargetSchema, $options, $limitNum, $newSchemas, $oSchema);
								break;
							case 'checked':
								$this->genSchemaByCheckedOptions($oTargetSchema, $options, $limitNum, $newSchemas, $oSchema);
								break;
							}
						} else {
							$this->genSchemaByTopOptions($oTargetSchema, $options, count($options), $newSchemas, $oSchema);
						}
						foreach ($newSchemas as $oNewDynaSchema) {
							$oNewDynaSchema->dynamic = 'Y';
							$oNewDynaSchema->cloneSchema = (object) ['id' => $oSchema->id, 'title' => $oSchema->title];
						}
					}
					break;
				}
			}
			/* 原型题目中设置了动态选项 */
			if (isset($oSchema->dsOps->app->id)) {
				$oDynaOptionsApp = $modelEnl->byId($oSchema->dsOps->app->id, ['cascaded' => 'N']);
				if ($oDynaOptionsApp && $oDynaOptionsApp->state === '1') {
					foreach ($newSchemas as $oNewDynaSchema) {
						if (isset($oNewDynaSchema->dsOps)) {
							foreach ($oDynaOptionsApp->dynaDataSchemas as $oDynaOptionSchema) {
								if ($oNewDynaSchema->id === $oDynaOptionSchema->id) {
									/* 修改为新的动态选项源 */
									$oNewDsOps = new \stdClass;
									$oNewDsOps->app = $oNewDynaSchema->dsOps->app; // 指向的应用不改变
									$oNewDsOps->schema = new \stdClass; // 指向的题目变为应用中的动态题目
									$oNewDsOps->schema->id = $oDynaOptionSchema->id;
									$oNewDsOps->schema->title = $oDynaOptionSchema->title;
									$oNewDynaSchema->dsOps = $oNewDsOps;
									break;
								}
							}
						}
					}
				}
			}
			$dynaSchemasByIndex[$schemaIndex] = $newSchemas;
		};
		/* 生成动态题目 */
		$dynaSchemasByIndex = []; // 动态创建的题目
		foreach ($oApp->dataSchemas as $schemaIndex => $oSchema) {
			if (!in_array($oSchema->type, ['single', 'multiple', 'score', 'longtext', 'html'])) {
				continue;
			}
			if (!empty($oSchema->dsSchema->app->id) && !empty($oSchema->dsSchema->schema->id) && !empty($oSchema->dsSchema->schema->type)) {
				$oDsSchema = $oSchema->dsSchema;
				if (!empty($oAppRound->mission_rid)) {
					if (!isset($modelRnd)) {
						$modelRnd = $this->model('matter\enroll\round');
					}
					$oDsAppRnd = $modelRnd->byMissionRid($oDsSchema->app, $oAppRound->mission_rid, ['fields' => 'rid,mission_rid']);
					switch ($oDsSchema->schema->type) {
					case 'shorttext':
					case 'longtext':
						if ((!empty($oSchema->dsOps->app->id) && $oSchema->dsOps->app->id === $oSchema->dsSchema->app->id) || $oSchema->type === 'html') {
							/* 如果动态选项指向了相同的题目，就是直接复制题目 */
							$fnMakeDynaSchemaBySchema($oSchema, $oDsAppRnd, $schemaIndex, $dynaSchemasByIndex);
						} else {
							$fnMakeDynaSchemaByData($oSchema, $oDsAppRnd, $schemaIndex, $dynaSchemasByIndex);
						}
						break;
					case 'score':
						$fnMakeDynaSchemaByScore($oSchema, $oDsAppRnd, $schemaIndex, $dynaSchemasByIndex);
						break;
					case 'single':
					case 'multiple':
						$fnMakeDynaSchemaByOption($oSchema, $oDsAppRnd, $schemaIndex, $dynaSchemasByIndex);
						break;
					}
				}
			}
		}
		/* 加入动态创建的题目 */
		if (count($dynaSchemasByIndex)) {
			$protoSchemaOffset = 0;
			foreach ($dynaSchemasByIndex as $index => $dynaSchemas) {
				array_splice($oApp->dataSchemas, $index + $protoSchemaOffset, 1, $dynaSchemas);
				$protoSchemaOffset += count($dynaSchemas) - 1;
			}
		}
		/* 修改对动态创建的父题目的引用 */
		$schemasByCloneId = [];
		foreach ($oApp->dataSchemas as $oSchema) {
			if ($oSchema->type === 'html' && isset($oSchema->cloneSchema->id)) {
				$schemasByCloneId[$oSchema->cloneSchema->id][] = $oSchema;
			}
		}
		foreach ($oApp->dataSchemas as $oSchema) {
			if (isset($oSchema->parent->id) && !empty($schemasByCloneId[$oSchema->parent->id])) {
				if (isset($oSchema->referRecord->schema)) {
					$oDynaParentSchemas = $schemasByCloneId[$oSchema->parent->id];
					foreach ($oDynaParentSchemas as $oDynaParentSchema) {
						if (isset($oDynaParentSchema->referSchema->id) && $oDynaParentSchema->referSchema->id === $oSchema->referRecord->schema->id) {
							$oSchema->referParent = $oSchema->parent;
							$oSchema->parent = (object) ['id' => $oDynaParentSchema->id, 'type' => $oDynaParentSchema->type, 'title' => $oDynaParentSchema->title];
						}
					}
				}
			}
		}

		return $oApp;
	}
	/**
	 * 根据指定的数量，从选项生成题目
	 */
	public function genSchemaByTopOptions($oTargetSchema, $votingOptions, $limitNum, &$newSchemas, $oProtoSchema = null) {
		if ($limitNum > count($votingOptions)) {
			$limitNum = count($votingOptions);
		}

		$originalOptionsByValue = [];
		foreach ($oTargetSchema->ops as $oOption) {
			$originalOptionsByValue[$oOption->v] = $oOption;
		}

		for ($i = 0; $i < $limitNum; $i++) {
			$oVotingOption = $votingOptions[$i];
			if (isset($originalOptionsByValue[$oVotingOption->v])) {
				$oOriginalOption = $originalOptionsByValue[$oVotingOption->v];
				if (empty($oProtoSchema)) {
					$oNewSchema = new \stdClass;
					$oNewSchema->type = 'longtext';
				} else {
					$oNewSchema = clone $oProtoSchema;
				}
				$oNewSchema->id = $oTargetSchema->id . $oOriginalOption->v;
				$oNewSchema->title = $oOriginalOption->l;
				$oNewSchema->referOption = (object) ['l' => $oOriginalOption->l, 'v' => $oOriginalOption->v];
				if (isset($oOriginalOption->referRecord)) {
					$oNewSchema->referRecord = $oOriginalOption->referRecord;
				}

				$newSchemas[] = $oNewSchema;
			}
		}
	}
	/**
	 * 根据选项获得的选择数量生成题目
	 */
	public function genSchemaByCheckedOptions($oTargetSchema, $votingOptions, $checkedNum, &$newSchemas, $oProtoSchema = null) {
		$originalOptionsByValue = [];
		foreach ($oTargetSchema->ops as $oOption) {
			$originalOptionsByValue[$oOption->v] = $oOption;
		}
		for ($i = 0, $ii = count($votingOptions); $i < $ii; $i++) {
			$oVotingOption = $votingOptions[$i];
			if (!isset($originalOptionsByValue[$oVotingOption->v]) && $oVotingOption->c < $checkedNum) {
				break;
			}
			$oOriginalOption = $originalOptionsByValue[$oVotingOption->v];

			if (empty($oProtoSchema)) {
				$oNewSchema = new \stdClass;
				$oNewSchema->type = 'longtext';
			} else {
				$oNewSchema = clone $oProtoSchema;
			}
			$oNewSchema->id = $oTargetSchema->id . $oOriginalOption->v;
			$oNewSchema->title = $oOriginalOption->l;
			/* 题目定义的来源 */
			if (!isset($oNewSchema->dsSchema)) {
				$oNewSchema->dsSchema = new \stdClass;
			}
			$oNewSchema->dsSchema->op = clone $oOriginalOption;
			$oNewSchema->dsSchema->op->schema_id = $oTargetSchema->id;

			$newSchemas[] = $oNewSchema;
		}
	}
}