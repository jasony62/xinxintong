<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记记录数据的项
 */
class item extends base {
	/**
	 * 添加题目数据的项
	 * 1、需要记录修改日志
	 * 2、需要支持积分
	 *
	 * @param int $data 填写记录数据id
	 */
	public function add_action($data) {
		$modelData = $this->model('matter\enroll\data')->setOnlyWriteDbConn(true);
		/* 要更新的题目 */
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

		$oNewItem = $modelData->byId($oNewItem->id);

		return new \ResponseData($oNewItem);
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
			return new \ResponseError('不允许删除其他用户提交的数据');
		}

		/**
		 * 更新数据
		 */
		array_splice($oRecData->value, $oItem->multitext_seq - 1, 1);

		$modelData->update(
			'xxt_enroll_record_data',
			['state' => 0],
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

		return new \ResponseData($oRecData->value);
	}
}