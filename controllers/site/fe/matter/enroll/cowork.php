<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记记录数据的项
 */
class cowork extends base {
	/**
	 * 添加题目数据的项
	 * 1、需要记录修改日志
	 * 2、需要支持积分
	 *
	 * @param int $data 填写记录数据id
	 */
	public function add_action($data, $ek = '', $schema = '') {
		$modelData = $this->model('matter\enroll\data')->setOnlyWriteDbConn(true);
		$modelRec = $this->model('matter\enroll\record');
		if (empty($data)) {
			/* 要更新的记录 */
			$oRecord = $modelRec->byId($ek, ['fields' => 'id,state,data,aid,rid,enroll_key,userid,group_id,like_num,agreed']);
			if (false === $oRecord || $oRecord->state !== '1') {
				return new \ObjectNotFoundError();
			}
			$oRecData = $modelData->byRecord($oRecord->enroll_key, ['schema' => $schema, 'fields' => '*']);
			if ($oRecData) {
				$oRecData->value = empty($oRecData->value) ? [] : json_decode($oRecData->value);
			} else {
				/* 补充创建新的题目数据 */
				$oRecData = new \stdClass;
				$oRecData->aid = $oRecord->aid;
				$oRecData->rid = $oRecord->rid;
				$oRecData->enroll_key = $oRecord->enroll_key;
				$oRecData->submit_at = time();
				$oRecData->userid = $oRecord->userid;
				$oRecData->group_id = $oRecord->group_id;
				$oRecData->schema_id = $schema;
				$oRecData->multitext_seq = 0;
				$oRecData->value = '[]';
				$oRecData->id = $modelData->insert('xxt_enroll_record_data', $oRecData, true);
				$oRecData->value = [];
			}
		} else {
			/* 要更新的题目 */
			$oRecData = $modelData->byId($data, ['fields' => 'id,userid,state,aid,rid,enroll_key,schema_id,multitext_seq,value']);
			if (false === $oRecData || $oRecData->state !== '1') {
				return new \ObjectNotFoundError();
			}
			/* 要更新的记录 */
			$oRecord = $modelRec->byId($oRecData->enroll_key, ['fields' => 'id,state,data,like_num,agreed']);
			if (false === $oRecord || $oRecord->state !== '1') {
				return new \ObjectNotFoundError();
			}
			if ($oRecData->multitext_seq !== '0') {
				return new \ParameterError();
			}
			$oRecData->value = empty($oRecData->value) ? [] : json_decode($oRecData->value);
		}

		$oApp = $this->model('matter\enroll')->byId($oRecData->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
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
		if ($oUpdatedSchema->type !== 'multitext') {
			return new \ParameterError('题目的类型不是多项填写题');
		}

		/* 检查数量限制 */
		if (isset($oApp->actionRule->record->cowork->pre)) {
			$oRule = $oApp->actionRule->record->cowork->pre;
			/* 限制了最多点赞次数 */
			if (!empty($oRule->record->likeNum)) {
				if ((int) $oRecord->like_num < (int) $oRule->record->likeNum) {
					$desc = empty($oRule->desc) ? ('点赞次数至少【' . $oRule->record->likeNum . '】个') : $oRule->desc;
					return new \ResponseError($desc);
				}
			}
		}

		$oUser = $this->getUser($oApp);

		$oPosted = $this->getPostJson();
		$current = time();

		$oNewItem = new \stdClass;
		$oNewItem->aid = $oApp->id;
		$oNewItem->rid = $oRecData->rid;
		$oNewItem->enroll_key = $oRecData->enroll_key;
		$oNewItem->submit_at = $current;
		$oNewItem->userid = isset($oUser->uid) ? $oUser->uid : '';
		$oNewItem->nickname = isset($oUser->nickname) ? $oUser->nickname : '';
		$oNewItem->group_id = isset($oUser->group_id) ? $oUser->group_id : '';
		$oNewItem->schema_id = $oUpdatedSchema->id;
		$oNewItem->value = $this->escape($oPosted->value);
		$oNewItem->multitext_seq = count($oRecData->value) + 1;
		if ($oRecord->agreed === 'D') {
			$oNewItem->agreed = 'D';
		}
		$oNewItem->id = $modelData->insert('xxt_enroll_record_data', $oNewItem, true);

		/* 更新题目数据 */
		$oRecData->value[] = (object) ['id' => $oNewItem->id, 'value' => $oNewItem->value];
		$modelData->update(
			'xxt_enroll_record_data',
			['value' => $this->escape($modelData->toJson($oRecData->value))],
			['id' => $oRecData->id]
		);
		/* 更新记录数据 */
		$oRecord->data->{$oRecData->schema_id} = $oRecData->value;
		$modelData->update(
			'xxt_enroll_record',
			['data' => $this->escape($modelData->toJson($oRecord->data))],
			['id' => $oRecord->id]
		);

		$oNewItem = $modelData->byId($oNewItem->id, ['fields' => '*']);

		/* 更新用户汇总信息及积分 */
		$modelEvt = $this->model('matter\enroll\event');
		$modelEvt->submitCowork($oApp, $oRecData, $oNewItem, $oUser);

		return new \ResponseData([$oNewItem, $oRecData]);
	}
	/**
	 * 更新题目中的一个项
	 */
	public function update_action($data, $item) {
		$modelData = $this->model('matter\enroll\data')->setOnlyWriteDbConn(true);
		/* 要更新的项 */
		$oItem = $modelData->byId($item, ['fields' => 'id,state,userid,aid,rid,enroll_key,schema_id,multitext_seq,value']);
		if (false === $oItem || $oItem->state !== '1') {
			return new \ObjectNotFoundError();
		}
		/* 要更新的数据 */
		$oRecData = $modelData->byId($data, ['fields' => 'id,state,aid,rid,enroll_key,schema_id,multitext_seq,value']);
		if (false === $oRecData || $oRecData->state !== '1') {
			return new \ObjectNotFoundError();
		}
		/* 要更新的记录 */
		$oRecord = $this->model('matter\enroll\record')->byId($oRecData->enroll_key, ['fields' => 'id,state,data']);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}

		if ($oRecData->multitext_seq !== '0') {
			return new \ParameterError();
		}
		$oRecData->value = empty($oRecData->value) ? [] : json_decode($oRecData->value);

		if (!isset($oRecData->value[$oItem->multitext_seq - 1]) || $oRecData->value[$oItem->multitext_seq - 1]->id !== (int) $oItem->id) {
			return new \ParameterError('填写项与填写数据中的内容不一致');
		}

		$oApp = $this->model('matter\enroll')->byId($oRecData->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$oUser = $this->getUser($oApp);
		if ($oUser->uid !== $oItem->userid) {
			return new \ResponseError('不允许修改其他用户提交的数据');
		}
		/**
		 * 更新数据
		 */
		$oPosted = $this->getPostJson();
		$modelData->update(
			'xxt_enroll_record_data',
			['value' => $modelData->escape($oPosted->value)],
			['id' => $oItem->id]
		);

		$oRecData->value[$oItem->multitext_seq - 1]->value = $oPosted->value;
		$modelData->update(
			'xxt_enroll_record_data',
			['value' => $this->escape($modelData->toJson($oRecData->value))],
			['id' => $oRecData->id]
		);

		$oRecord->data->{$oRecData->schema_id} = $oRecData->value;
		$modelData->update(
			'xxt_enroll_record',
			['data' => $this->escape($modelData->toJson($oRecord->data))],
			['id' => $oRecord->id]
		);

		return new \ResponseData($oRecData->value);
	}
	/**
	 * 从题目删除一个项
	 */
	public function remove_action($data, $item) {
		$modelData = $this->model('matter\enroll\data')->setOnlyWriteDbConn(true);
		$oItem = $modelData->byId($item, ['fields' => 'id,state,userid,aid,rid,enroll_key,schema_id,multitext_seq,value']);
		if (false === $oItem || $oItem->state !== '1') {
			return new \ObjectNotFoundError();
		}
		/* 要更新的题目 */
		$oRecData = $modelData->byId($data, ['fields' => 'id,userid,state,aid,rid,enroll_key,schema_id,multitext_seq,value']);
		if (false === $oRecData || $oRecData->state !== '1') {
			return new \ObjectNotFoundError();
		}
		/* 要更新的记录 */
		$oRecord = $this->model('matter\enroll\record')->byId($oRecData->enroll_key, ['fields' => 'id,state,data']);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}
		if ($oRecData->multitext_seq !== '0') {
			return new \ParameterError();
		}
		$oRecData->value = empty($oRecData->value) ? [] : json_decode($oRecData->value);

		if (!isset($oRecData->value[$oItem->multitext_seq - 1]) || $oRecData->value[$oItem->multitext_seq - 1]->id !== (int) $oItem->id) {
			return new \ParameterError('填写项与填写数据中的内容不一致');
		}

		$oApp = $this->model('matter\enroll')->byId($oRecData->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$oUser = $this->getUser($oApp);
		if ($oUser->uid !== $oItem->userid) {
			return new \ResponseError('不允许删除其他用户提交的数据');
		}

		/**
		 * 更新数据
		 */
		array_splice($oRecData->value, $oItem->multitext_seq - 1, 1);

		$modelData->update(
			'xxt_enroll_record_data',
			['state' => 101],
			['id' => $oItem->id]
		);

		if ($oItem->multitext_seq <= count($oRecData->value)) {
			$modelData->update(
				'xxt_enroll_record_data',
				['multitext_seq' => (object) ['op' => '-=', 'pat' => 1]],
				['state' => 1, 'enroll_key' => $oRecData->enroll_key, 'schema_id' => $oRecData->schema_id, 'multitext_seq' => (object) ['op' => '>=', 'pat' => $oItem->multitext_seq]]
			);
		}

		/* 更新题目数据 */
		$modelData->update(
			'xxt_enroll_record_data',
			['value' => $this->escape($modelData->toJson($oRecData->value))],
			['id' => $oRecData->id]
		);
		/* 更新记录数据 */
		$oRecord->data->{$oRecData->schema_id} = $oRecData->value;
		$modelData->update(
			'xxt_enroll_record',
			['data' => $this->escape($modelData->toJson($oRecord->data))],
			['id' => $oRecord->id]
		);

		/* 更新用户汇总信息及积分 */
		$modelEvt = $this->model('matter\enroll\event');
		$modelEvt->removeCowork($oApp, $oRecData, $oItem, $oUser);

		return new \ResponseData($oRecData->value);
	}
	/**
	 * 获得和协作填写相关的任务定义
	 */
	public function task_action($app, $ek, $schema) {
		$modelApp = $this->model('matter\enroll');
		$modelRec = $this->model('matter\enroll\record');

		$oApp = $modelApp->byId($app, ['cascaded' => 'N', 'fields' => 'id,siteid,state,entry_rule,action_rule']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$oUser = $this->getUser($oApp);

		$oStat = new \stdClass;
		$tasks = [];
		if (isset($oApp->actionRule)) {
			$oActionRule = $oApp->actionRule;
			/* 参与用户的任务要求 */
			if (isset($oActionRule->cowork)) {
				$oCoworkRule = $oActionRule->cowork;
				/* 对提交填写记录的投票数量有要求 */
				if (isset($oCoworkRule->submit->end)) {
					$oRule = $oCoworkRule->submit->end;
					$bPassed = true;
					/* 检查是否提交了协作填写数据，或进行了留言 */
					if (!empty($oRule->cowork->num) || !empty($oRule->coworkOrRemark->num)) {
						$modelData = $this->model('matter\enroll\data');
						$items = $modelData->getMultitext($ek, $schema, ['excludeRoot' => true]);
						$oStat->itemNum = count($items);
						$oStat->items = $items;
						if (!empty($oRule->cowork->num)) {
							$bPassed = $oStat->itemNum >= (int) $oRule->cowork->num;
						}
					}
					if ($bPassed && (!empty($oRule->remark->num) || !empty($oRule->coworkOrRemark->num))) {
						$modelRem = $this->model('matter\enroll\remark');
						$remarks = $modelRem->byUser($oApp, $oUser, ['ek' => $ek]);
						$oStat->remarkNum = count($remarks);
						if (!empty($oRule->remark->num)) {
							$bPassed = $oStat->remarkNum >= (int) $oRule->remark->num;
						}
					}
					if ($bPassed && !empty($oRule->coworkOrRemark->num)) {
						$bPassed = $oStat->itemNum + $oStat->remarkNum >= (int) $oRule->coworkOrRemark->num;
					}
					if (!$bPassed) {
						$oRule->id = 'cowork.submit.end';
						/* 积分奖励 */
						require_once TMS_APP_DIR . '/models/matter/enroll/event.php';
						$modelCoinRule = $this->model('matter\enroll\coin');
						$aCoin = $modelCoinRule->coinByMatter(\matter\enroll\event_model::DoSubmitCoworkEventName, $oApp);
						if ($aCoin && $aCoin[0]) {
							$oRule->coin = $aCoin[1];
						}
						$tasks[] = $oRule;
					}
				}
				/* 对开启投票有限制 */
				if (isset($oCoworkRule->like->pre)) {
					$oRule = $oCoworkRule->like->pre;
					$bPassed = true;
					/* 检查是否提交了协作填写数据，或进行了留言 */
					if (!empty($oRule->cowork->num)) {
						if (!isset($oStat->itemNum)) {
							$modelData = $this->model('matter\enroll\data');
							$items = $modelData->getMultitext($ek, $schema, ['excludeRoot' => true]);
							$oStat->itemNum = count($items);
							$oStat->items = $items;
						}
						$bPassed = $oStat->itemNum >= (int) $oRule->cowork->num;
					}
					if (!$bPassed) {
						$oRule->id = 'cowork.like.pre';
						$tasks[] = $oRule;
					}
				}
				/* 对投票数量有限制 */
				if (isset($oCoworkRule->like->end)) {
					$oRule = $oCoworkRule->like->end;
					$bPassed = true;
					if (!empty($oRule->min)) {
						if (!isset($oStat->items)) {
							$modelData = $this->model('matter\enroll\data');
							$items = $modelData->getMultitext($ek, $schema, ['excludeRoot' => true]);
							$oStat->itemNum = count($items);
							$oStat->items = $items;
						}
						$likeNum = 0;
						foreach ($oStat->items as $oItem) {
							if (isset($oItem->like_log->{$oUser->uid})) {
								$likeNum++;
							}
						}
						$bPassed = $likeNum >= (int) $oRule->min;
					}
					if (!$bPassed) {
						$oRule->id = 'cowork.like.end';
						$tasks[] = $oRule;
					}
				}
			}
			/* 对组长的任务要求 */
			if (!empty($oUser->group_id) && isset($oUser->is_leader) && $oUser->is_leader === 'Y') {
				/* 对组长推荐记录的要求 */
				if (isset($oActionRule->leader->cowork->agree->end)) {
					$oRule = $oActionRule->leader->cowork->agree->end;
					if (!empty($oRule->min)) {
						$modelData = $this->model('matter\enroll\data');
						$items = $modelData->getMultitext($ek, $schema, ['excludeRoot' => true, 'agreed' => 'Y']);
						$coworkNum = count($items);
						if ($coworkNum >= $oRule->min) {
							$oRule->_ok = [$coworkNum];
						} else {
							$oRule->_no = [(int) $oRule->min - $coworkNum];
						}
						$oRule->id = 'leader.cowork.agree.end';
						$tasks[] = $oRule;
					}
				}
			}
		}

		return new \ResponseData($tasks);
	}
}