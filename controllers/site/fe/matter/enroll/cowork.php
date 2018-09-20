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
	public function add_action($ek, $schema = '') {
		$modelData = $this->model('matter\enroll\data')->setOnlyWriteDbConn(true);
		$modelRec = $this->model('matter\enroll\record');
		/* 要更新的记录 */
		$oRecord = $modelRec->byId($ek, ['fields' => 'id,state,data,aid,rid,enroll_key,userid,nickname,group_id,like_num,agreed']);
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
			$oRecData->nickname = $oRecord->nickname;
			$oRecData->group_id = $oRecord->group_id;
			$oRecData->schema_id = $schema;
			$oRecData->multitext_seq = 0;
			$oRecData->value = '[]';
			$oRecData->id = $modelData->insert('xxt_enroll_record_data', $oRecData, true);
			$oRecData->value = [];
		}

		$oApp = $this->model('matter\enroll')->byId($oRecData->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->getUser($oApp);

		/* 检查是否满足添加答案的条件 */
		if (!isset($oApp->entryRule->exclude_action) || $oApp->entryRule->exclude_action->add_cowork != "Y") {
			$checkEntryRule = $this->checkEntryRule($oApp, false, $oUser);
			if ($checkEntryRule[0] === false) {
				return new \ResponseError($checkEntryRule[1]);
			}
		}

		if (!empty($oApp->actionRule->cowork->submit->pre->editor)) {
			if (empty($oUser->is_editor) || $oUser->is_editor !== 'Y') {
				return new \ParameterError('仅限活动编辑组用户提交填写数据');
			}
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

		/* 默认协作填写的表态 */
		if (isset($oApp->actionRule->cowork->default->agreed)) {
			$agreed = $oApp->actionRule->cowork->default->agreed;
			if (in_array($agreed, ['A', 'D'])) {
				$oNewItem->agreed = $agreed;
			}
		} else if ($oRecord->agreed === 'D') {
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
		$rst = $modelEvt->submitCowork($oApp, $oRecData, $oNewItem, $oUser);
		/* 生成提醒 */
		$this->model('matter\enroll\notice')->addCowork($oApp, $oRecData, $oNewItem, $oUser);

		$data = [$oNewItem, $oRecData];
		$data['getCoin'] = new \stdClass;
		if (isset($rst->userGetCoin)) {
			$data['getCoin']->userGetCoin = $rst->userGetCoin;
		}
		if (isset($rst->authorGetCoin)) {
			$data['getCoin']->authorGetCoin = $rst->authorGetCoin;
		}
		return new \ResponseData($data);
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
	public function remove_action($item) {
		$oResult = $this->_removeItem($item, true);
		if (false === $oResult[0]) {
			return new \ResponseError($oResult[1]);
		}

		$oRecData = $oResult[1]->data;

		return new \ResponseData($oRecData);
	}
	/**
	 * 将题目的协作填写项改为留言
	 */
	public function asRemark_action($item) {
		/* 删除协作填写项 */
		$oResult = $this->_removeItem($item, false);
		if (false === $oResult[0]) {
			return new \ResponseError($oResult[1]);
		}

		$modelRec = $this->model('matter\enroll\record');
		$oRecord = $oResult[1]->record;
		$oItem = $oResult[1]->item;

		/* 根据协作填写项添加留言 */
		$current = time();
		$oNewRemark = new \stdClass;
		$oNewRemark->siteid = $oRecord->siteid;
		$oNewRemark->aid = $oRecord->aid;
		$oNewRemark->rid = $oRecord->rid;
		$oNewRemark->userid = $oItem->userid;
		$oNewRemark->group_id = isset($oItem->group_id) ? $oItem->group_id : '';
		$oNewRemark->nickname = $modelRec->escape($oItem->nickname);
		$oNewRemark->enroll_key = $oRecord->enroll_key;
		$oNewRemark->enroll_group_id = $oRecord->group_id;
		$oNewRemark->enroll_userid = $oRecord->userid;
		$oNewRemark->schema_id = '';
		$oNewRemark->data_id = 0;
		$oNewRemark->remark_id = 0;
		$oNewRemark->create_at = $current;
		$oNewRemark->modify_at = $current;
		$oNewRemark->content = $modelRec->escape($oItem->value);
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

		$oNewRemark->id = $modelRec->insert('xxt_enroll_record_remark', $oNewRemark, true);

		/* 留言总数 */
		$modelRec->update("update xxt_enroll_record set remark_num=remark_num+1,rec_remark_num=rec_remark_num+1 where enroll_key='{$oRecord->enroll_key}'");

		return new \ResponseData($oNewRemark);
	}
	/**
	 * 从题目删除一个项
	 */
	private function _removeItem($itemId, $bCheckUser = false) {
		$modelData = $this->model('matter\enroll\data');
		/* 要更新的数据项 */
		$oItem = $modelData->byId($itemId, ['fields' => '*']);
		if (false === $oItem || $oItem->state !== '1') {
			return [false, '访问的对象不存在（1）'];
		}
		if ($oItem->multitext_seq === '0') {
			return [false, '访问的对象数据错误（1）'];
		}
		/* 要更新的数据 */
		$oRecData = $modelData->byRecord($oItem->enroll_key, ['schema' => $oItem->schema_id, 'fields' => '*']);
		if (false === $oRecData || $oRecData->state !== '1') {
			return [false, '访问的对象不存在（2）'];
		}
		$oRecData->value = empty($oRecData->value) ? [] : json_decode($oRecData->value);
		if (!isset($oRecData->value[$oItem->multitext_seq - 1]) || $oRecData->value[$oItem->multitext_seq - 1]->id !== (int) $oItem->id) {
			return [false, '填写项与填写数据中的内容不一致'];
		}

		/* 要更新的记录 */
		$oRecord = $this->model('matter\enroll\record')->byId($oItem->enroll_key, ['fields' => '*']);
		if (false === $oRecord || $oRecord->state !== '1') {
			return [false, '访问的对象不存在（3）'];
		}

		$oApp = $this->model('matter\enroll')->byId($oItem->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return [false, '访问的对象不存在（4）'];
		}

		$oCurrentUser = $this->getUser($oApp);
		if ($bCheckUser) {
			if ($oCurrentUser->uid !== $oItem->userid) {
				return [false, '不允许删除其他用户提交的数据'];
			}
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
		/* 更新作为题目来源的留言 */
		$modelData->update(
			'xxt_enroll_record_remark',
			['as_cowork_id' => 0],
			['as_cowork_id' => $oItem->id]
		);
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
		if ($bCheckUser) {
			$modelEvt = $this->model('matter\enroll\event');
			$modelEvt->removeCowork($oApp, $oRecData, $oItem, $oCurrentUser);
		}

		return [true, (object) ['record' => $oRecord, 'data' => $oRecData, 'item' => $oItem]];
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
		$oRecord = $this->model('matter\enroll\record')->byId($ek, ['fields' => 'id,state,rid']);
		if (false === $oRecord || $oRecord->state !== '1') {
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
				/* 对提交协作填写数据的数量有要求 */
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
							if (!$bPassed) {
								if (empty($oRule->desc)) {
									$desc = '每人需要提交【' . $oRule->cowork->num . '条】协作填写数据（答案），';
								} else {
									$desc = $oRule->desc;
									if (!in_array(mb_substr($desc, -1), ['。', '，', '；', '.', ',', ';'])) {
										$desc .= '，';
									}
								}
								$oRule->desc = $desc . '还需【' . ((int) $oRule->cowork->num - $oStat->itemNum) . '条】。';
								/* 积分奖励 */
								require_once TMS_APP_DIR . '/models/matter/enroll/event.php';
								$modelCoinRule = $this->model('matter\enroll\coin');
								$aCoin = $modelCoinRule->coinByMatter(\matter\enroll\event_model::DoSubmitCoworkEventName, $oApp);
								if ($aCoin && $aCoin[0]) {
									$oRule->coin = $aCoin[1];
									if (!in_array(mb_substr($oRule->desc, -1), ['。', '，', '；', '.', ',', ';'])) {
										$oRule->desc .= '。';
									}
									$oRule->desc .= '每条协作可获得【' . $oRule->coin . '个】积分。';
								}
							}
						}
					}
					if ($bPassed && (!empty($oRule->remark->num) || !empty($oRule->coworkOrRemark->num))) {
						$modelRem = $this->model('matter\enroll\remark');
						$remarks = $modelRem->byUser($oApp, $oUser, ['ek' => $ek]);
						$oStat->remarkNum = count($remarks);
						if (!empty($oRule->remark->num)) {
							$bPassed = $oStat->remarkNum >= (int) $oRule->remark->num;
							if (!$bPassed) {
								if (empty($oRule->desc)) {
									$desc = '每人需要提交【' . $oRule->remark->num . '条】留言，';
								} else {
									$desc = $oRule->desc;
									if (!in_array(mb_substr($desc, -1), ['。', '，', '；', '.', ',', ';'])) {
										$desc .= '，';
									}
								}
								$oRule->desc = $desc . '还需【' . ((int) $oRule->remark->num - $oStat->remarkNum) . '条】。';
								/* 积分奖励 */
								require_once TMS_APP_DIR . '/models/matter/enroll/event.php';
								$modelCoinRule = $this->model('matter\enroll\coin');
								$aCoin = $modelCoinRule->coinByMatter(\matter\enroll\event_model::DoRemarkEventName, $oApp);
								if ($aCoin && $aCoin[0]) {
									$oRule->coin = $aCoin[1];
									if (!in_array(mb_substr($oRule->desc, -1), ['。', '，', '；', '.', ',', ';'])) {
										$oRule->desc .= '。';
									}
									$oRule->desc .= '每条留言可获得【' . $oRule->coin . '个】积分。';
								}
							}
						}
					}
					if ($bPassed && !empty($oRule->coworkOrRemark->num)) {
						$bPassed = $oStat->itemNum + $oStat->remarkNum >= (int) $oRule->coworkOrRemark->num;
						if (!$bPassed && empty($oRule->desc)) {
							if (empty($oRule->desc)) {
								$desc = '每人需要提交【' . $oRule->coworkOrRemark->num . '条】协作填写数据（答案）或留言，';
							} else {
								$desc = $oRule->desc;
								if (!in_array(mb_substr($desc, -1), ['。', '，', '；', '.', ',', ';'])) {
									$desc .= '，';
								}
							}
							$oRule->desc = $desc . '还需【' . ((int) $oRule->coworkOrRemark->num - ($oStat->itemNum + $oStat->remarkNum)) . '条】。';
							/* 积分奖励 */
							require_once TMS_APP_DIR . '/models/matter/enroll/event.php';
							$modelCoinRule = $this->model('matter\enroll\coin');
							$aCoin = $modelCoinRule->coinByMatter(\matter\enroll\event_model::DoSubmitCoworkEventName, $oApp);
							if ($aCoin && $aCoin[0]) {
								$oRule->coin = $aCoin[1];
								if (!in_array(mb_substr($oRule->desc, -1), ['。', '，', '；', '.', ',', ';'])) {
									$oRule->desc .= '。';
								}
								$oRule->desc .= '每条协作数据可获得【' . $oRule->coin . '个】积分。';
							}
							$aCoin = $modelCoinRule->coinByMatter(\matter\enroll\event_model::DoRemarkEventName, $oApp);
							if ($aCoin && $aCoin[0]) {
								$oRule->coin = $aCoin[1];
								if (!in_array(mb_substr($oRule->desc, -1), ['。', '，', '；', '.', ',', ';'])) {
									$oRule->desc .= '。';
								}
								$oRule->desc .= '每条留言可获得【' . $oRule->coin . '个】积分。';
							}
						}
					}
					if (!$bPassed) {
						$oRule->id = 'cowork.submit.end';
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
						if (empty($oRule->desc)) {
							$desc = '需要提交【' . $oRule->cowork->num . '条】协作填写数据（答案）才能开启点赞（投票），';
						} else {
							$desc = $oRule->desc;
							if (!in_array(mb_substr($desc, -1), ['。', '，', '；', '.', ',', ';'])) {
								$desc .= '，';
							}
						}
						$oRule->desc = $desc . '还需【' . ((int) $oRule->cowork->num - $oStat->itemNum) . '条】。';
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
						$oRule->_no = [(int) $oRule->min - (int) $likeNum];
						$oRule->id = 'cowork.like.end';
						$desc = empty($oRule->desc) ? ('每轮次每人需要选择【' . $oRule->min . ((int) $oRule->max > (int) $oRule->min ? ('-' . $oRule->max) : '') . '条】协作填写数据（答案）点赞（投票）') : $oRule->desc;
						if (!in_array(mb_substr($desc, -1), ['。', '，', '；', '.', ',', ';'])) {
							$desc .= '，';
						}
						$oRule->desc = $desc . '还需【' . $oRule->_no[0] . '条】。';
						$tasks[] = $oRule;
					} else {
						$oRule->_ok = [$likeNum];
					}
				}
			}
			/* 提交留言的要求 */
			if (isset($oActionRule->remark)) {
				$oRemarkRule = $oActionRule->remark;
				/* 对提交数量有要求 */
				if (!empty($oRemarkRule->submit->end->min)) {
					$oRule = $oRemarkRule->submit->end;
					$modelRem = $this->model('matter\enroll\remark');
					$remarks = $modelRem->byUser($oApp, $oUser, ['rid' => $oRecord->rid, 'fields' => 'id']);
					$remarkNum = count($remarks);
					if ($remarkNum < $oRule->min) {
						$oRule->_no = [(int) $oRule->min - $remarkNum];
						$desc = empty($oRule->desc) ? ('每轮次每人需要至少提交【' . $oRule->min . '条】留言') : $oRule->desc;
						if (!in_array(mb_substr($desc, -1), ['。', '，', '；', '.', ',', ';'])) {
							$desc .= '，';
						}
						$oRule->desc = $desc . '还需【' . $oRule->_no[0] . '条】。';
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
							$desc = empty($oRule->desc) ? ('每轮次组长需要推荐【' . $oRule->min . ((int) $oRule->max > (int) $oRule->min ? ('-' . $oRule->max) : '') . '条】协作填写数据（答案）') : $oRule->desc;
							if (!in_array(mb_substr($desc, -1), ['。', '，', '；', '.', ',', ';'])) {
								$desc .= '，';
							}
							$oRule->desc = $desc . '还需【' . $oRule->_no[0] . '条】。';
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