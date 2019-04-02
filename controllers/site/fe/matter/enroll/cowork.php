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
	public function add_action($ek, $schema = '', $task = null) {
		$modelData = $this->model('matter\enroll\data')->setOnlyWriteDbConn(true);
		$modelRec = $this->model('matter\enroll\record');
		/* 要更新的记录 */
		$oRecord = $modelRec->byId($ek, ['fields' => 'id,state,data,aid,rid,enroll_key,userid,nickname,group_id,like_num,agreed']);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$oRecData = $modelData->byRecord($oRecord->enroll_key, ['schema' => $schema, 'fields' => '*']);
		if ($oRecData) {
			if (empty($oRecData->value)) {
				$oRecData->value = [];
			} else {
				$oRecData->value = json_decode($oRecData->value);
				if (empty($oRecData->value) || !is_array($oRecData->value)) {$oRecData->value = [];}
			}
		} else {
			/* 补充创建新的题目数据 */
			$oRecData = new \stdClass;
			$oRecData->aid = $oRecord->aid;
			$oRecData->rid = $oRecord->rid;
			$oRecData->record_id = $oRecord->id;
			$oRecData->enroll_key = $oRecord->enroll_key;
			$oRecData->submit_at = time();
			$oRecData->userid = $oRecord->userid;
			$oRecData->nickname = $this->escape($oRecord->nickname);
			$oRecData->group_id = $oRecord->group_id;
			$oRecData->schema_id = $schema;
			$oRecData->is_multitext_root = 'Y';
			$oRecData->multitext_seq = 0;
			$oRecData->value = '[]';
			$oRecData->id = $modelData->insert('xxt_enroll_record_data', $oRecData, true);
			$oRecData->nickname = $oRecord->nickname;
			$oRecData->value = [];
		}

		$oApp = $this->model('matter\enroll')->byId($oRecData->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		/* 检查指定的任务 */
		if (!empty($task)) {
			$modelTsk = $this->model('matter\enroll\task', $oApp);
			$oTask = $modelTsk->byId($task);
			if (false === $oTask || $oTask->state !== 'IP' || $oTask->config_type !== 'answer') {
				return new \ObjectNotFoundError('指定的任务不存在或不可用');
			}
		}

		$oUser = $this->getUser($oApp);

		/* 检查是否满足添加答案的条件 */
		if (empty($oApp->entryRule->exclude_action->add_cowork) || $oApp->entryRule->exclude_action->add_cowork != "Y") {
			$checkEntryRule = $this->checkEntryRule($oApp, false, $oUser);
			if ($checkEntryRule[0] === false) {
				return new \ResponseError($checkEntryRule[1]);
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

		$oPosted = $this->getPostJson();
		$current = time();

		/* 默认协作填写的表态 */
		$agreed = $this->getDeepValue($oApp, 'actionRule.cowork.default.agreed');
		if (empty($agreed) && $oRecord->agreed === 'D') {
			$agreed = 'D';
		}

		$oNewItem = $modelData->addCowork($oUser, $oApp, $oRecData, $oPosted->value, $agreed);

		/* 更新用户汇总信息及积分 */
		$modelEvt = $this->model('matter\enroll\event');
		$coworkResult = $modelEvt->submitCowork($oApp, $oRecData, $oNewItem, $oUser);
		/**
		 * 如果存在提问任务，将记录放到任务专题中
		 */
		if (isset($oTask)) {
			$modelTop = $this->model('matter\enroll\topic', $oApp);
			if ($oTopic = $modelTop->byTask($oTask)) {
				$modelTop->assign($oTopic, $oRecord, $oNewItem);
			}
		}
		/* 通知记录活动事件接收人 */
		if (isset($oApp->notifyConfig->cowork->valid) && $oApp->notifyConfig->cowork->valid === true) {
			$this->_notifyReceivers($oApp, $oRecord, $oNewItem);
		}

		$aResult = [];
		$aResult['oNewItem'] = $oNewItem;
		$aResult['oRecData'] = $oRecData;
		$aResult['coworkResult'] = $coworkResult;

		return new \ResponseData($aResult);
	}
	/**
	 * 通知协作填写记录事件
	 */
	private function _notifyReceivers($oApp, $oRecord, $oItem) {
		/* 通知接收人 */
		$receivers = $this->model('matter\enroll\user')->getCoworkReceivers($oApp, $oRecord, $oItem, $oApp->notifyConfig->cowork);
		if (empty($receivers)) {
			return false;
		}

		$page = empty($oApp->notifyConfig->cowork->page) ? 'cowork' : $oApp->notifyConfig->cowork->page;
		$noticeURL = $oApp->entryUrl . '&ek=' . $oRecord->enroll_key . '&page=cowork' . '#item-' . $oItem->id;

		$noticeName = 'site.enroll.cowork';

		/*获取模板消息id*/
		$oTmpConfig = $this->model('matter\tmplmsg\config')->getTmplConfig($oApp, $noticeName, ['onlySite' => false, 'noticeURL' => $noticeURL]);
		if ($oTmpConfig[0] === false) {
			return false;
		}
		$oTmpConfig = $oTmpConfig[1];

		$modelTmplBat = $this->model('matter\tmplmsg\batch');
		$oCreator = new \stdClass;
		$oCreator->uid = $noticeName;
		$oCreator->name = 'system';
		$oCreator->src = 'pl';
		$modelTmplBat->send($oApp->siteid, $oTmpConfig->tmplmsgId, $oCreator, $receivers, $oTmpConfig->oParams, ['send_from' => $oApp->type . ':' . $oApp->id]);

		return true;
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
		/**
		 * 检查提交人
		 */
		if ($this->getDeepValue($oApp->scenarioConfig, 'can_cowork') !== 'Y') {
			if ($oUser->uid !== $oItem->userid) {
				return new \ResponseError('不允许修改其他用户提交的数据');
			}
		} else {
			if ($oUser->uid !== $oItem->userid && $this->getDeepValue($oUser, 'is_editor') !== 'Y') {
				return new \ResponseError('不允许修改其他用户提交的数据');
			}
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
}