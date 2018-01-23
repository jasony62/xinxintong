<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记记录数据
 */
class data extends base {
	/**
	 * 获得登记记录中的数据
	 */
	public function get_action($ek, $schema = '', $data = '') {
		$fields = 'id,state,aid,rid,enroll_key,schema_id,multitext_seq,userid,group_id,submit_at,agreed,value,supplement,like_num,like_log,remark_num,tag,score';
		$modelRecDat = $this->model('matter\enroll\data');
		if (empty($data)) {
			$oRecData = $modelRecDat->byRecord($ek, ['schema' => $schema, 'fields' => $fields]);
		} else {
			$oRecData = $modelRecDat->byId($data, ['fields' => $fields]);
		}
		if ($oRecData) {
			$oRecord = $this->model('matter\enroll\record')->byId($oRecData->enroll_key, ['fields' => 'nickname']);
			if ($oRecord) {
				$oRecData->nickname = $oRecord->nickname;
			}
		}

		return new \ResponseData($oRecData);
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
		$oRecData = $modelData->byRecord($ek, ['schema' => $schema, 'fields' => 'id,aid,enroll_key,state,userid,agreed,agreed_log']);
		if (false === $oRecData || $oRecData->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oApp = $this->model('matter\enroll')->byId($oRecData->aid, ['cascaded' => 'N', 'fields' => 'id,siteid,mission_id,state,entry_rule']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		if (empty($oApp->entry_rule->group->id)) {
			return new \ParameterError('只有进入条件为分组活动的登记活动才允许组长推荐');
		}

		$modelGrpUsr = $this->model('matter\group\player');
		/* 当前操作用户所属分组及角色 */
		$oGrpLeader = $modelGrpUsr->byUser($oApp->entry_rule->group, $this->who->uid, ['fields' => 'is_leader,round_id', 'onlyOne' => true]);
		if (false === $oGrpLeader || $oGrpLeader->is_leader !== 'Y') {
			return new \ParameterError('只有允许组长进行推荐');
		}
		/* 记录提交人所属分组 */
		$oGrpMemb = $modelGrpUsr->byUser($oApp->entry_rule->group, $oRecData->userid, ['fields' => 'round_id', 'onlyOne' => true]);
		if (false === $oGrpMemb || $oGrpMemb->round_id !== $oGrpLeader->round_id) {
			return new \ParameterError('只允许组长推荐本组数据');
		}

		if (!in_array($value, ['Y', 'N', 'A'])) {
			$value = '';
		}

		$oAgreedLog = $oRecData->agreed_log;
		if (isset($oAgreedLog->{$this->who->uid})) {
			$oLog = $oAgreedLog->{$this->who->uid};
			$oLog->time = time();
			$oLog->value = $value;
		} else {
			$oAgreedLog->{$this->who->uid} = (object) ['time' => time(), 'value' => $value];
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

		return new \ResponseData($rst);
	}
	/**
	 * 点赞登记记录中的某一个题
	 *
	 * @param string $ek
	 * @param string $schema
	 * @param int $id xxt_enroll_record_data 的id
	 *
	 */
	public function like_action($ek, $schema, $id = '') {
		$modelData = $this->model('matter\enroll\data');
		if (empty($id)) {
			$oRecordData = $modelData->byRecord($ek, ['schema' => $schema, 'fields' => 'aid,id,like_log,userid,multitext_seq,like_num']);
		} else {
			$oRecordData = $modelData->byId($id, ['fields' => 'aid,id,like_log,userid,multitext_seq,like_num']);
		}
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
				if (empty($id)) {
					return new \ComplianceError('参数错误，此题型需要指定唯一标识');
				}
			}
		}

		$oUser = $this->who;

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

		$modelUsr = $this->model('matter\enroll\user');
		$modelUsr->setOnlyWriteDbConn(true);
		if ($incLikeNum > 0) {
			/* 更新进行点赞的活动用户的积分奖励 */
			$modelMat = $this->model('matter\enroll\coin');
			$modelMat->setOnlyWriteDbConn(true);
			$rulesOther = $modelMat->rulesByMatter('site.matter.enroll.data.other.like', $oApp);
			$modelCoin = $this->model('site\coin\log');
			$modelCoin->setOnlyWriteDbConn(true);
			$modelCoin->award($oApp, $oUser, 'site.matter.enroll.data.other.like', $rulesOther);
		}

		/* 获得所属轮次 */
		$modelRun = $this->model('matter\enroll\round');
		if ($activeRound = $modelRun->getActive($oApp)) {
			$rid = $activeRound->rid;
		} else {
			$rid = '';
		}

		/* 更新进行点赞的活动用户的轮次数据 */
		$oEnrollUsr = $modelUsr->byId($oApp, $oUser->uid, ['fields' => 'id,nickname,last_like_other_at,like_other_num,user_total_coin', 'rid' => $rid]);
		if (false === $oEnrollUsr) {
			$inData = ['last_like_other_at' => time(), 'like_other_num' => $incLikeNum];
			if (!empty($rulesOther)) {
				$inData['user_total_coin'] = 0;
				foreach ($rulesOther as $ruleOther) {
					$inData['user_total_coin'] = $inData['user_total_coin'] + (int) $ruleOther->actor_delta;
				}
			}

			$inData['rid'] = $rid;
			$modelUsr->add($oApp, $oUser, $inData);
		} else {
			$upData = ['last_like_other_at' => time(), 'like_other_num' => $oEnrollUsr->like_other_num + $incLikeNum];
			if (!empty($rulesOther)) {
				$upData['user_total_coin'] = (int) $oEnrollUsr->user_total_coin;
				foreach ($rulesOther as $ruleOther) {
					$upData['user_total_coin'] = $upData['user_total_coin'] + (int) $ruleOther->actor_delta;
				}
			}
			$modelUsr->update(
				'xxt_enroll_user',
				$upData,
				['id' => $oEnrollUsr->id]
			);
		}
		/* 更新进行点赞的活动用户的总数据 */
		$oEnrollUsrALL = $modelUsr->byId($oApp, $oUser->uid, ['fields' => 'id,nickname,last_like_other_at,like_other_num,user_total_coin', 'rid' => 'ALL']);
		if (false === $oEnrollUsrALL) {
			$inDataALL = ['last_like_other_at' => time(), 'like_other_num' => $incLikeNum];
			if (!empty($rulesOther)) {
				$inDataALL['user_total_coin'] = 0;
				foreach ($rulesOther as $ruleOther) {
					$inDataALL['user_total_coin'] = $inDataALL['user_total_coin'] + (int) $ruleOther->actor_delta;
				}
			}

			$inDataALL['rid'] = "ALL";
			$modelUsr->add($oApp, $oUser, $inDataALL);
		} else {
			$upDataALL = ['last_like_other_at' => time(), 'like_other_num' => $oEnrollUsrALL->like_other_num + $incLikeNum];
			if (!empty($rulesOther)) {
				$upDataALL['user_total_coin'] = (int) $oEnrollUsrALL->user_total_coin;
				foreach ($rulesOther as $ruleOther) {
					$upDataALL['user_total_coin'] = $upDataALL['user_total_coin'] + (int) $ruleOther->actor_delta;
				}
			}
			$modelUsr->update(
				'xxt_enroll_user',
				$upDataALL,
				['id' => $oEnrollUsrALL->id]
			);
		}

		/* 更新被点赞的活动用户的轮次数据 */
		$oEnrollUsr = $modelUsr->byId($oApp, $oRecordData->userid, ['fields' => 'id,userid,nickname,last_like_at,like_num,user_total_coin', 'rid' => $rid]);
		if ($oEnrollUsr) {
			if ($incLikeNum > 0) {
				$user = new \stdClass;
				$user->uid = $oEnrollUsr->userid;
				$user->nickname = $oEnrollUsr->nickname;
				/* 更新被点赞的活动用户的积分奖励 */
				$rulesOwner = $modelMat->rulesByMatter('site.matter.enroll.data.like', $oApp);
				$modelCoin->award($oApp, $user, 'site.matter.enroll.data.like', $rulesOwner);
			}
			$upData2 = ['last_like_at' => time(), 'like_num' => $oEnrollUsr->like_num + $incLikeNum];
			if (!empty($rulesOwner)) {
				$upData2['user_total_coin'] = (int) $oEnrollUsr->user_total_coin;
				foreach ($rulesOwner as $rule) {
					$upData2['user_total_coin'] = $upData2['user_total_coin'] + (int) $rule->actor_delta;
				}
			}
			$modelUsr->update(
				'xxt_enroll_user',
				$upData2,
				['id' => $oEnrollUsr->id]
			);
		}
		/* 更新被点赞的活动用户的总数据 */
		$oEnrollUsrALL = $modelUsr->byId($oApp, $oRecordData->userid, ['fields' => 'id,userid,nickname,last_like_at,like_num,user_total_coin', 'rid' => 'ALL']);
		if ($oEnrollUsrALL) {
			if ($incLikeNum > 0 && !isset($rulesOwner)) {
				/* 更新被点赞的活动用户的积分奖励 */
				$rulesOwner = $modelMat->rulesByMatter('site.matter.enroll.data.like', $oApp);
			}
			$upDataALL2 = ['last_like_at' => time(), 'like_num' => $oEnrollUsrALL->like_num + $incLikeNum];
			if (!empty($rulesOwner)) {
				$upDataALL2['user_total_coin'] = (int) $oEnrollUsrALL->user_total_coin;
				foreach ($rulesOwner as $rule) {
					$upDataALL2['user_total_coin'] = $upDataALL2['user_total_coin'] + (int) $rule->actor_delta;
				}
			}
			$modelUsr->update(
				'xxt_enroll_user',
				$upDataALL2,
				['id' => $oEnrollUsrALL->id]
			);
		}
		/**
		 * 更新项目用户数据
		 */
		if (!empty($oApp->mission_id)) {
			$modelMisUsr = $this->model('matter\mission\user');
			$modelMisUsr->setOnlyWriteDbConn(true);
			$oMission = new \stdClass;
			$oMission->siteid = $oApp->siteid;
			$oMission->id = $oApp->mission_id;
			/* 更新进行点赞的活动用户的总数据 */
			$oMisUser = $modelMisUsr->byId($oMission, $oUser->uid, ['fields' => 'id,nickname,last_like_other_at,like_other_num,user_total_coin']);
			if (false === $oMisUser) {
				$aNewMisUsr = ['last_like_other_at' => time(), 'like_other_num' => $incLikeNum];
				if (!empty($rulesOther)) {
					$aNewMisUsr['user_total_coin'] = 0;
					foreach ($rulesOther as $ruleOther) {
						$aNewMisUsr['user_total_coin'] = $aNewMisUsr['user_total_coin'] + (int) $ruleOther->actor_delta;
					}
				}
				$modelMisUsr->add($oMission, $oUser, $aNewMisUsr);
			} else {
				$aUpdMisUsr = ['last_like_other_at' => time(), 'like_other_num' => $oMisUser->like_other_num + $incLikeNum];
				if (!empty($rulesOther)) {
					$aUpdMisUsr['user_total_coin'] = (int) $oMisUser->user_total_coin;
					foreach ($rulesOther as $ruleOther) {
						$aUpdMisUsr['user_total_coin'] = $aUpdMisUsr['user_total_coin'] + (int) $ruleOther->actor_delta;
					}
				}
				$modelMisUsr->update(
					'xxt_mission_user',
					$aUpdMisUsr,
					['id' => $oMisUser->id]
				);
			}
			/* 更新被点赞的活动用户的总数据 */
			$oMisUser = $modelMisUsr->byId($oMission, $oRecordData->userid, ['fields' => 'id,userid,nickname,last_like_at,like_num,user_total_coin']);
			if ($oMisUser) {
				if ($incLikeNum > 0 && !isset($rulesOwner)) {
					$rulesOwner = $modelMat->rulesByMatter('site.matter.enroll.data.like', $oApp);
				}
				$aUpdMisUsr = ['last_like_at' => time(), 'like_num' => $oMisUser->like_num + $incLikeNum];
				if (!empty($rulesOwner)) {
					$aUpdMisUsr['user_total_coin'] = (int) $oMisUser->user_total_coin;
					foreach ($rulesOwner as $rule) {
						$aUpdMisUsr['user_total_coin'] = $aUpdMisUsr['user_total_coin'] + (int) $rule->actor_delta;
					}
				}
				$modelMisUsr->update(
					'xxt_mission_user',
					$aUpdMisUsr,
					['id' => $oMisUser->id]
				);
			}
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