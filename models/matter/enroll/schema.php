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
		foreach ($oApp->dataSchemas as $oSchema) {
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
		$dynaSchemasByIndex = [];
		foreach ($oApp->dataSchemas as $schemaIndex => $oSchema) {
			if (!empty($oSchema->dsSchemas->app->id) && !empty($oSchema->dsSchemas->schema->id)) {
				if (!empty($oAppRound->mission_rid)) {
					if (!isset($modelRnd)) {
						$modelRnd = $this->model('matter\enroll\round');
					}
					$oDsAppRnd = $modelRnd->byMissionRid($oSchema->dsSchemas->app, $oAppRound->mission_rid, ['fields' => 'rid']);
				}
				$q = [
					'id,enroll_key,value,userid,nickname',
					"xxt_enroll_record_data t0",
					['state' => 1, 'aid' => $oSchema->dsSchemas->app->id, 'schema_id' => $oSchema->dsSchemas->schema->id],
				];
				/* 设置轮次条件 */
				if (!empty($oDsAppRnd)) {
					$q[2]['rid'] = $oDsAppRnd->rid;
				}
				/* 设置顾虑条件 */
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
			}
		}

		if (count($dynaSchemasByIndex)) {
			foreach ($dynaSchemasByIndex as $index => $dynaSchemas) {
				array_splice($oApp->dataSchemas, $index, 1, $dynaSchemas);
			}
		}

		return $oApp;
	}
}