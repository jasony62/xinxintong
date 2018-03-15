<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记记录数据
 */
class data extends base {
	/**
	 * 获得登记记录中的数据
	 *
	 * @param string $ek
	 * @param string $schema
	 * @param string $data
	 */
	public function get_action($ek, $schema = '', $data = '', $cascaded = 'N') {
		$oRecord = $this->model('matter\enroll\record')->byId($ek, ['fields' => 'aid,rid,enroll_key,userid,group_id,nickname,enroll_at']);
		if (false === $oRecord) {
			return new \ObjectNotFoundError('（1）指定的对象不存在或不可用');
		}

		$oApp = $this->model('matter\enroll')->byId($oRecord->aid, ['cascaded' => 'N', 'fields' => 'id,siteid,state,data_schemas']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError('（2）指定的对象不存在或不可用');
		}

		$oSchemas = new \stdClass;
		foreach ($oApp->dataSchemas as $dataSchema) {
			$oSchemas->{$dataSchema->id} = $dataSchema;
		}

		$fields = 'id,state,userid,nickname,schema_id,multitext_seq,submit_at,agreed,value,supplement,like_num,like_log,remark_num,tag,score';
		$modelRecDat = $this->model('matter\enroll\data');
		if (empty($data)) {
			$oRecData = $modelRecDat->byRecord($ek, ['schema' => $schema, 'fields' => $fields]);
		} else {
			$oRecData = $modelRecDat->byId($data, ['fields' => $fields]);
		}

		if (false === $oRecData || $oRecData->state !== '1') {
			return new \ObjectNotFoundError('（3）指定的对象不存在或不可用');
		}

		if (isset($oSchemas->{$oRecData->schema_id}) && $oSchemas->{$oRecData->schema_id}->type === 'multitext') {
			if ($oRecData->multitext_seq == 0) {
				$oRecData->value = empty($oRecData->value) ? [] : json_decode($oRecData->value);
				if ($cascaded === 'Y') {
					$q = [
						$fields,
						'xxt_enroll_record_data',
						['state' => 1, 'enroll_key' => $ek, 'schema_id' => $oRecData->schema_id, 'multitext_seq' => (object) ['op' => '<>', 'pat' => '0']],
					];
					$oRecData->items = $modelRecDat->query_objs_ss($q);
					foreach ($oRecData->items as $oItem) {
						$oItem->like_log = empty($oItem->like_log) ? [] : json_decode($oItem->like_log);
					}
				}
			}
		}

		$oRecord->verbose = new \stdClass;
		$oRecord->verbose->{$oRecData->schema_id} = $oRecData;

		return new \ResponseData($oRecord);
	}
	/**
	 * 提交一道题目的数据
	 * 1、需要记录修改日志
	 * 2、需要支持积分
	 *
	 * @param int $data 填写记录数据id
	 */
	public function submit_action($data) {
		$modelData = $this->model('matter\enroll\data')->setOnlyWriteDbConn(true);
		$oRecData = $modelData->byId($data, ['fields' => 'id,aid,rid,enroll_key,schema_id,multitext_seq']);
		if (false === $oRecData) {
			return new \ObjectNotFoundError();
		}

		$oApp = $this->model('matter\enroll')->byId($oRecData->aid, ['cascaded' => 'N']);
		if (false === $oApp) {
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

		$oUser = $this->getUser($oApp);

		$oPosted = $this->getPostJson();
		$current = time();
		$oResult = null;

		switch ($oUpdatedSchema->type) {
		case 'multitext':
			if ($oRecData->multitext_seq === '0') {
				/* 之前提交过现在要保留的数据 */
				$oReservedItems = new \stdClass;
				foreach ($oPosted as $oItem) {
					if (!empty($oItem->id)) {
						$oReservedItems->{$oItem->id} = $oItem;
					}
				}
				/* 已有的记录 */
				$oBeforeItems = new \stdClass;
				$q = [
					'id,userid',
					'xxt_enroll_record_data',
					['state' => 1, 'enroll_key' => $oRecData->enroll_key, 'schema_id' => $oRecData->schema_id, 'multitext_seq' => (object) ['op' => '<>', 'pat' => '0']],
				];
				$aBeforeItems = $modelData->query_objs_ss($q);
				foreach ($aBeforeItems as $oBeforeItem) {
					if (!isset($oReservedItems->{$oBeforeItem->id})) {
						/* 数据项将要被删除 */
						if ($oBeforeItem->userid !== $oUser->uid) {
							return new \ResponseError('不允许删除其他用户提交的数据');
						}
					}
					$oBeforeItems->{$oBeforeItem->id} = $oBeforeItem;
				}
				/* 更新数据 */
				foreach ($oPosted as $seq => $oItem) {
					if (empty($oItem->id)) {
						$aSchemaValue = [
							'aid' => $oApp->id,
							'rid' => $oRecData->rid,
							'enroll_key' => $oRecData->enroll_key,
							'submit_at' => $current,
							'userid' => isset($oUser->uid) ? $oUser->uid : '',
							'nickname' => isset($oUser->nickname) ? $oUser->nickname : '',
							'group_id' => isset($oUser->group_id) ? $oUser->group_id : '',
							'schema_id' => $oUpdatedSchema->id,
							'multitext_seq' => (int) $seq + 1,
							'value' => $this->escape($oItem->value),
						];
						$oItem->id = $modelData->insert('xxt_enroll_record_data', $aSchemaValue, true);
					} else {
						$oBeforeItem = $oBeforeItems->{$oItem->id};
						if ($oBeforeItem->userid === $oUser->uid) {
							$modelData->update(
								'xxt_enroll_record_data',
								[
									'value' => $this->escape($oItem->value),
									'multitext_seq' => (int) $seq + 1,
								],
								['id' => $oItem->id]
							);
						}
						unset($oBeforeItems->{$oItem->id});
					}
				}
				/* 删除的项目 */
				foreach ($oBeforeItems as $oItem) {
					$modelData->update(
						'xxt_enroll_record_data',
						['state' => 0],
						['id' => $oItem->id]
					);
				}
				/* 更新父记录数据 */
				$oResult = $oPosted;
				$modelData->update(
					'xxt_enroll_record_data',
					['value' => $this->escape($modelData->toJson($oResult))],
					['id' => $oRecData->id]
				);
			}
			break;
		}

		return new \ResponseData($oResult);
	}
	/**
	 * 推荐登记记录中的某一个题
	 * 只有组长才有权限做
	 *
	 * @param string $ek
	 * @param string $schema
	 * @param string $value
	 *
	 */
	public function recommend_action($ek, $schema, $value = '') {
		$modelData = $this->model('matter\enroll\data');
		$oRecData = $modelData->byRecord($ek, ['schema' => $schema, 'fields' => 'id,aid,rid,enroll_key,state,userid,agreed,agreed_log']);
		if (false === $oRecData || $oRecData->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oApp = $this->model('matter\enroll')->byId($oRecData->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		if (empty($oApp->entryRule->group->id)) {
			return new \ParameterError('只有进入条件为分组活动的登记活动才允许组长推荐');
		}
		$oUser = $this->getUser($oApp);

		$modelGrpUsr = $this->model('matter\group\player');
		/* 当前操作用户所属分组及角色 */
		$oGrpLeader = $modelGrpUsr->byUser($oApp->entryRule->group, $oUser->uid, ['fields' => 'is_leader,round_id', 'onlyOne' => true]);
		if (false === $oGrpLeader || !in_array($oGrpLeader->is_leader, ['Y', 'S'])) {
			return new \ParameterError('只允许组长进行推荐');
		}
		/* 检查是否在同一分组内 */
		if ($oGrpLeader->is_leader === 'Y') {
			$oGrpMemb = $modelGrpUsr->byUser($oApp->entryRule->group, $oRecData->userid, ['fields' => 'round_id', 'onlyOne' => true]);
			if (false === $oGrpMemb || $oGrpMemb->round_id !== $oGrpLeader->round_id) {
				return new \ParameterError('只允许组长推荐本组数据');
			}
		}

		if (!in_array($value, ['Y', 'N', 'A'])) {
			$value = '';
		}
		$beforeValue = $oRecData->agreed;
		if ($beforeValue === $value) {
			return new \ParameterError('不能重复设置推荐状态');
		}

		$oAgreedLog = $oRecData->agreed_log;
		if (isset($oAgreedLog->{$oUser->uid})) {
			$oLog = $oAgreedLog->{$oUser->uid};
			$oLog->time = time();
			$oLog->value = $value;
		} else {
			$oAgreedLog->{$oUser->uid} = (object) ['time' => time(), 'value' => $value];
		}

		$rst = $modelData->update(
			'xxt_enroll_record_data',
			['agreed' => $value, 'agreed_log' => json_encode($oAgreedLog)],
			['id' => $oRecData->id]
		);

		/* 如果活动属于项目，更新项目内的推荐内容 */
		if (!empty($oApp->mission_id)) {
			$modelMisMat = $this->model('matter\mission\matter');
			$modelMisMat->agreed($oApp, 'D', $oRecData, $value);
		}

		/* 处理了用户汇总数据，积分数据 */
		$this->model('matter\enroll\event')->recommendRecordData($oApp, $oRecData, $oUser, $value);

		return new \ResponseData($rst);
	}
	/**
	 * 点赞登记记录中的某一个题
	 *
	 * @param string $ek
	 * @param string $schema
	 * @param int $data xxt_enroll_record_data 的id
	 *
	 */
	public function like_action($data) {
		if (empty($data)) {
			return new \ResponseError('参数错误：未指定被评论内容ID');
		}
		$modelData = $this->model('matter\enroll\data');
		$oRecData = $modelData->byId($data, ['fields' => 'id,aid,rid,enroll_key,schema_id,like_log,userid,multitext_seq,like_num']);
		if (false === $oRecData) {
			return new \ObjectNotFoundError();
		}

		$oApp = $this->model('matter\enroll')->byId($oRecData->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		/* 检查是否是多项填写题题的点赞，如果是，需要$id */
		$oDataSchema = null;
		foreach ($oApp->dataSchemas as $dataSchema) {
			if ($dataSchema->id === $oRecData->schema_id && $dataSchema->type === 'multitext') {
				$oDataSchema = $dataSchema;
				break;
			}
		}

		$oUser = $this->getUser($oApp);

		$oLikeLog = $oRecData->like_log;
		if (isset($oLikeLog->{$oUser->uid})) {
			unset($oLikeLog->{$oUser->uid});
			$incLikeNum = -1;
		} else {
			$oLikeLog->{$oUser->uid} = time();
			$incLikeNum = 1;
		}
		$likeNum = $oRecData->like_num + $incLikeNum;
		$modelData->update(
			'xxt_enroll_record_data',
			['like_log' => json_encode($oLikeLog), 'like_num' => $likeNum],
			['id' => $oRecData->id]
		);
		// if (isset($oDataSchema) && $oDataSchema->type === 'multitext' && $oRecData->multitext_seq != 0) {
		// 	// 总数据点赞数 +1
		// 	if ($incLikeNum > 0) {
		// 		$modelData->update("update xxt_enroll_record_data set like_num=like_num +1 where enroll_key='{$oRecData->enroll_key}' and schema_id='{$oRecData->schema_id}' and multitext_seq = 0");
		// 	} else {
		// 		$modelData->update("update xxt_enroll_record_data set like_num=like_num -1 where enroll_key='{$oRecData->enroll_key}' and schema_id='{$oRecData->schema_id}' and multitext_seq = 0");
		// 	}
		// }

		$modelEnlEvt = $this->model('matter\enroll\event');
		if ($incLikeNum > 0) {
			/* 发起点赞 */
			$modelEnlEvt->likeRecData($oApp, $oRecData, $oUser);
			/* 被点赞 */
			$modelEnlEvt->belikedRecData($oApp, $oRecData, $oUser);
		} else {
			/* 撤销发起点赞 */
			$modelEnlEvt->undoLikeRecData($oApp, $oRecData, $oUser);
			/* 撤销被点赞 */
			$modelEnlEvt->undoBeLikedRecData($oApp, $oRecData, $oUser);
		}

		$result = [];
		//if (isset($oDataSchema) && $oDataSchema->type === 'multitext' && $oRecData->multitext_seq != 0) {
		//	$leader = $modelData->byRecord($oRecData->enroll_key, ['schema' => $oRecData->schema_id, 'fields' => 'like_log,like_num']);
		//	$result['itemLike_log'] = $oLikeLog;
		//	$result['itemLike_num'] = $likeNum;
		//$result['like_log'] = $leader->like_log;
		//$result['like_num'] = $leader->like_num;
		//} else {
		$result['like_log'] = $oLikeLog;
		$result['like_num'] = $likeNum;
		//}

		return new \ResponseData($result);
	}
}