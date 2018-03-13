<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记记录数据
 */
class data extends base {
	/**
	 * 获得登记记录中的数据
	 * $data xxt_enroll_record_data id
	 */
	public function get_action($ek, $schema = '', $data = '') {
		$ek = $this->escape($ek);
		$oRecord = $this->model('matter\enroll\record')->byId($ek, ['fields' => 'aid,rid,enroll_key,userid,group_id,nickname,enroll_at']);
		if (false === $oRecord) {
			return new \ObjectNotFoundError();
		}

		$oApp = $this->model('matter\enroll')->byId($oRecord->aid, ['cascaded' => 'N', 'fields' => 'id,siteid,state,data_schemas']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oSchemas = new \stdClass;
		foreach ($oApp->dataSchemas as $dataSchema) {
			$oSchemas->{$dataSchema->id} = $dataSchema;
		}

		$fields = 'id,state,schema_id,multitext_seq,submit_at,agreed,value,supplement,like_num,like_log,remark_num,tag,score';
		$modelRecDat = $this->model('matter\enroll\data');
		if (empty($data)) {
			$oRecData = $modelRecDat->byRecord($ek, ['schema' => $schema, 'fields' => $fields]);
		} else {
			$oRecData = $modelRecDat->byId($data, ['fields' => $fields]);
		}
		if (isset($oSchemas->{$oRecData->schema_id}) && $oSchemas->{$oRecData->schema_id}->type === 'multitext') {
			if ($oRecData->multitext_seq == 0) {
				$oRecData->value = empty($oRecData->value) ? [] : json_decode($oRecData->value);
			}
		}

		$oRecord->verbose = new \stdClass;
		$oRecord->verbose->{$oRecData->schema_id} = $oRecData;

		return new \ResponseData($oRecord);
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
	public function like_action($ek, $schema, $data) {
		if (empty($data)) {
			return new \ResponseError('参数错误：未指定被评论内容ID');
		}
		$modelData = $this->model('matter\enroll\data');
		$oRecordData = $modelData->byId($data, ['fields' => 'id,aid,rid,like_log,userid,multitext_seq,like_num']);
		if (false === $oRecordData) {
			return new \ObjectNotFoundError();
		}

		$oApp = $this->model('matter\enroll')->byId($oRecordData->aid, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		/* 检查是否是多项填写题题的点赞，如果是，需要$id */
		foreach ($oApp->dataSchemas as $dataSchema) {
			if ($dataSchema->id === $schema && $dataSchema->type === 'multitext') {
				$schmeaType = 'multitext';
				if (empty($data)) {
					return new \ComplianceError('参数错误，此题型需要指定唯一标识');
				}
			}
		}

		$oUser = $this->getUser($oApp);

		$oLikeLog = $oRecordData->like_log;
		if (isset($oLikeLog->{$oUser->uid})) {
			unset($oLikeLog->{$oUser->uid});
			$incLikeNum = -1;
		} else {
			$oLikeLog->{$oUser->uid} = time();
			$incLikeNum = 1;
		}
		$likeNum = $oRecordData->like_num + $incLikeNum;
		$modelData->update(
			'xxt_enroll_record_data',
			['like_log' => json_encode($oLikeLog), 'like_num' => $likeNum],
			['id' => $oRecordData->id]
		);
		if (isset($schmeaType) && $schmeaType === 'multitext' && $oRecordData->multitext_seq != 0) {
			// 总数据点赞数 +1
			if ($incLikeNum > 0) {
				$modelData->update("update xxt_enroll_record_data set like_num=like_num +1 where enroll_key='$ek' and schema_id='$schema' and multitext_seq = 0");
			} else {
				$modelData->update("update xxt_enroll_record_data set like_num=like_num -1 where enroll_key='$ek' and schema_id='$schema' and multitext_seq = 0");
			}
		}

		$modelEnlEvt = $this->model('matter\enroll\event');
		if ($incLikeNum > 0) {
			/* 发起点赞 */
			$modelEnlEvt->likeRecData($oApp, $oRecordData, $oUser);
			/* 被点赞 */
			$modelEnlEvt->belikedRecData($oApp, $oRecordData, $oUser);
		} else {
			/* 撤销发起点赞 */
			$modelEnlEvt->undoLikeRecData($oApp, $oRecordData, $oUser);
			/* 撤销被点赞 */
			$modelEnlEvt->undoBeLikedRecData($oApp, $oRecordData, $oUser);
		}

		$result = [];
		if (isset($schmeaType) && $schmeaType === 'multitext' && $oRecordData->multitext_seq != 0) {
			$leader = $modelData->byRecord($ek, ['schema' => $schema, 'fields' => 'like_log,like_num']);
			$result['itemLike_log'] = $oLikeLog;
			$result['itemLike_num'] = $likeNum;
			$result['like_log'] = $leader->like_log;
			$result['like_num'] = $leader->like_num;
		} else {
			$result['like_log'] = $oLikeLog;
			$result['like_num'] = $likeNum;
		}

		return new \ResponseData($result);
	}
}