<?php
namespace matter\enroll;
/**
 *
 */
class schema_model extends \TMS_MODEL {
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
	 * 去除掉无效的内容
	 *
	 * 1、无效的字段
	 * 2、无效的设置，例如隐藏条件
	 *
	 * 支持的类型
	 * type
	 * title
	 * dsOps 动态选项来源
	 */
	public function purify($aAppSchemas) {
		$validProps = ['id', 'type', 'title', 'content', 'description', 'format', 'limitChoice', 'range', 'required', 'unique', 'remarkable', 'shareable', 'supplement', 'history', 'count', 'requireScore', 'scoreMode', 'score', 'answer', 'weight', 'fromApp', 'requireCheck', 'ds', 'dsOps', 'showOpNickname', 'showOpDsLink', 'dsSchemas', 'visibility', 'cowork', 'filterWhiteSpace', 'ops'];

		$purified = [];
		foreach ($aAppSchemas as $oSchema) {
			foreach ($oSchema as $prop => $val) {
				if (!in_array($prop, $validProps)) {
					unset($oSchema->{$prop});
				}
			}
			// 删除多选题答案中被删除的选项
			switch ($oSchema->type) {
			case 'multiple':
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
			$purified[] = $oSchema;
		}

		return $purified;
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
						$oNewOp->ds = (object) ['ek' => $oRecData->enroll_key, 'user' => $oRecData->userid, 'nickname' => $oRecData->nickname];
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

		/* 根据填写数据生成题目 */
		$fnMakeDynaSchemaByData = function ($oSchema, $oDsAppRnd, $schemaIndex, &$dynaSchemasByIndex) {
			$q = [
				'id,enroll_key,value,userid,nickname',
				"xxt_enroll_record_data t0",
				['state' => 1, 'aid' => $oSchema->dsSchemas->app->id, 'schema_id' => $oSchema->dsSchemas->schema->id],
			];
			/* 设置轮次条件 */
			if (!empty($oDsAppRnd)) {
				$q[2]['rid'] = $oDsAppRnd->rid;
			}
			/* 设置过滤条件 */
			if (!empty($oSchema->dsSchemas->filters)) {
				foreach ($oSchema->dsSchemas->filters as $index => $oFilter) {
					if (!empty($oFilter->schema->id) && !empty($oFilter->schema->type)) {
						switch ($oFilter->schema->type) {
						case 'single':
							if (!empty($oFilter->schema->op->v)) {
								$tbl = 't' . ($index + 1);
								$sql = "select 1 from xxt_enroll_record_data {$tbl} where state=1 and aid='{$oSchema->dsSchemas->app->id}'and schema_id='{$oFilter->schema->id}' and value='{$oFilter->schema->op->v}' and t0.enroll_key={$tbl}.enroll_key";
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
				$oNewDynaSchema->id = 'dyna' . $oRecData->id;
				$oNewDynaSchema->title = $oRecData->value;
				$oNewDynaSchema->dynamic = 'Y';
				$oNewDynaSchema->prototype = (object) [
					'schema' => (object) ['id' => $oSchema->id, 'title' => $oSchema->title],
					'ds' => (object) ['ek' => $oRecData->enroll_key, 'user' => $oRecData->userid, 'nickname' => $oRecData->nickname],
				];
				$dynaSchemasByIndex[$schemaIndex][] = $oNewDynaSchema;
			}
		};

		/* 根据打分题获得的分数生成题目 */
		$fnMakeDynaSchemaByScore = function ($oSchema, $oDsAppRnd, $schemaIndex, &$dynaSchemasByIndex) use ($oApp) {
			$modelEnl = $this->model('matter\enroll');
			$oTargetApp = $modelEnl->byId($oSchema->dsSchemas->app->id, ['fields' => 'siteid,state,mission_id,data_schemas,sync_mission_round']);
			if (false === $oTargetApp || $oTargetApp->state !== '1') {
				return [false, '指定的目标活动不可用'];
			}
			if ($oApp->mission_id !== $oTargetApp->mission_id) {
				return [false, '仅支持在同一个项目的活动间通过记录生成题目'];
			}

			$targetSchemas = []; // 目标应用中选择的题目
			foreach ($oTargetApp->dynaDataSchemas as $oSchema2) {
				if (empty($oSchema2->dynamic) || $oSchema2->dynamic !== 'Y' || empty($oSchema2->prototype->schema->id)) {
					continue;
				}
				if ($oSchema->dsSchemas->schema->id === $oSchema2->prototype->schema->id) {
					$targetSchemas[$oSchema2->id] = $oSchema2;
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

			foreach ($aResult as $schemaId => $score) {
				$oProtoSchema = $targetSchemas[$schemaId];
				$oNewSchema = new \stdClass;
				$oNewSchema->id = $oProtoSchema->id;
				$oNewSchema->title = $oProtoSchema->title;
				$oNewSchema->type = 'longtext';
				$oNewSchema->dynamic = 'Y';
				$oNewSchema->prototype = (object) [
					'schema' => (object) ['id' => $oSchema->id, 'title' => $oSchema->title],
				];
				if (isset($oProtoSchema->ds)) {
					$oNewSchema->prototype->ds = $oProtoSchema->ds;
				}
				$dynaSchemasByIndex[$schemaIndex][] = $oNewSchema;
			}
		};

		/* 根据选择题获得的票数生成题目 */
		$fnMakeDynaSchemaByOption = function ($oSchema, $oDsAppRnd, $schemaIndex, &$dynaSchemasByIndex) use ($oApp) {
			$modelEnl = $this->model('matter\enroll');

			$oTargetApp = $modelEnl->byId($oSchema->dsSchemas->app->id, ['fields' => 'siteid,state,mission_id,data_schemas,sync_mission_round']);
			if (false === $oTargetApp || $oTargetApp->state !== '1') {
				return [false, '指定的目标活动不可用'];
			}
			if ($oApp->mission_id !== $oTargetApp->mission_id) {
				return [false, '仅支持在同一个项目的活动间通过记录生成题目'];
			}

			$targetSchemas = []; // 目标应用中选择的题目
			foreach ($oTargetApp->dataSchemas as $oSchema2) {
				if ($oSchema->dsSchemas->schema->id === $oSchema2->id) {
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
						//if (!empty($oTargetSchema->limit->scope) && !empty($oTargetSchema->limit->num) && (int) $oTargetSchema->limit->num) {
						//	if ($oTargetSchema->limit->scope === 'top') {
						$this->genSchemaByTopOptions($oTargetSchema, $options, count($options), $newSchemas, $oSchema);
						foreach ($newSchemas as $oNewDynaSchema) {
							$oNewDynaSchema->dynamic = 'Y';
							$oNewDynaSchema->prototype = (object) [
								'schema' => (object) ['id' => $oSchema->id, 'title' => $oSchema->title],
							];
						}
						//	} else if ($oTargetSchema->limit->scope === 'checked') {
						//		$this->genSchemaByCheckedOptions($oTargetSchema, $options, $oTargetSchema->limit->num, $newSchemas);
						//	}
						//}
					}
					break;
				}
			}
			if (isset($oSchema->dsOps->app->id)) {
				$oApp3 = $modelEnl->byId($oSchema->dsOps->app->id, ['cascaded' => 'N']);
				foreach ($newSchemas as $oNewDynaSchema1) {
					if (isset($oNewDynaSchema1->ds->ek) && isset($oNewDynaSchema1->dsOps->schema)) {
						foreach ($oApp3->dynaDataSchemas as $oDynaSchema2) {
							if (isset($oDynaSchema2->ds->ek) && $oDynaSchema2->ds->ek === $oNewDynaSchema1->ds->ek) {
								$oNewDynaSchema1->dsOps->schema->id = $oDynaSchema2->id;
								$oNewDynaSchema1->dsOps->schema->title = $oDynaSchema2->title;
							}
						}
					}
				}
			}
			$dynaSchemasByIndex[$schemaIndex] = $newSchemas;
		};

		$dynaSchemasByIndex = []; // 动态创建的题目
		foreach ($oApp->dataSchemas as $schemaIndex => $oSchema) {
			if (!empty($oSchema->dsSchemas->mode) && !empty($oSchema->dsSchemas->app->id) && !empty($oSchema->dsSchemas->schema->id)) {
				$oDsSchemas = $oSchema->dsSchemas;
				if (!empty($oAppRound->mission_rid)) {
					if (!isset($modelRnd)) {
						$modelRnd = $this->model('matter\enroll\round');
					}
					$oDsAppRnd = $modelRnd->byMissionRid($oDsSchemas->app, $oAppRound->mission_rid, ['fields' => 'rid,mission_rid']);
				}
				switch ($oDsSchemas->mode) {
				case 'fromData':
					$fnMakeDynaSchemaByData($oSchema, $oDsAppRnd, $schemaIndex, $dynaSchemasByIndex);
					break;
				case 'fromScore':
					$fnMakeDynaSchemaByScore($oSchema, $oDsAppRnd, $schemaIndex, $dynaSchemasByIndex);
					break;
				case 'fromOption':
					$fnMakeDynaSchemaByOption($oSchema, $oDsAppRnd, $schemaIndex, $dynaSchemasByIndex);
					break;
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
			$oOption = $votingOptions[$i];
			if (isset($originalOptionsByValue[$oOption->v])) {
				if (empty($oProtoSchema)) {
					$oNewSchema = new \stdClass;
					$oNewSchema->type = 'longtext';
				} else {
					$oNewSchema = clone $oProtoSchema;
				}
				$oNewSchema->id = $oTargetSchema->id . $oOption->v;
				$oNewSchema->title = $oOption->l;
				if (isset($originalOptionsByValue[$oOption->v]->ds)) {
					$oNewSchema->ds = $originalOptionsByValue[$oOption->v]->ds;
				}
				$newSchemas[] = $oNewSchema;
			}
		}
	}
	/**
	 * 根据选项获得的选择数量生成题目
	 */
	public function genSchemaByCheckedOptions($oTargetSchema, $votingOptions, $checkedNum, &$newSchemas) {
		for ($i = 0, $ii = count($votingOptions); $i < $ii; $i++) {
			$oOption = $votingOptions[$i];

			$originalOptionsByValue = [];
			foreach ($oTargetSchema->ops as $oOption) {
				$originalOptionsByValue[$oOption->v] = $oOption;
			}

			if (isset($originalOptionsByValue[$oOption->v])) {
				if ($oOption->c < $checkedNum) {
					break;
				}
				$oNewSchema = new \stdClass;
				$oNewSchema->id = $oTargetSchema->id . $oOption->v;
				$oNewSchema->title = $oOption->l;
				$oNewSchema->type = 'longtext';
				if (isset($originalOptionsByValue[$oOption->v]->ds)) {
					$oNewSchema->ds = $originalOptionsByValue[$oOption->v]->ds;
				}
				$newSchemas[] = $oNewSchema;
			}
		}
	}
}