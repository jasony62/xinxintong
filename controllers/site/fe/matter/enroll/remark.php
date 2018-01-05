<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动登记记录评论
 */
class remark extends base {
	/**
	 * 返回一条登记记录的所有评论
	 */
	public function list_action($ek, $schema, $page = 1, $size = 99, $id = '') {
		$oUser = $this->who;

		$options = [];
		if (empty($id)) {
			$oRecordData = $this->model('matter\enroll\data')->byRecord($ek, ['schema' => $schema, 'fields' => 'id,agreed,value,like_num,like_log,remark_num,supplement,tag']);
		} else {
			$oRecordData = $this->model('matter\enroll\data')->byId($id, ['fields' => 'id,agreed,value,like_num,like_log,remark_num,supplement,tag']);
			if ($oRecordData) {
				$data_ids = [];
				$data_ids[] = $oRecordData->id;
				$options['data_id'] = $data_ids;
			}
		}

		$result = $this->model('matter\enroll\remark')->listByRecord($oUser, $ek, $schema, $page, $size, $options);

		$result->data = $oRecordData;

		return new \ResponseData($result);
	}
	/*
	* 返回多项填写题的所有评论
	* $id xxt_enroll_record_data id
	*/
	public function listMultitext_action($ek, $schema, $page = 1, $size = 99) {
		if (empty($schema)) {
			return new \ResponseError('没有指定题目id');
		}

		$oUser = $this->who;
		$oRecordDatas = $this->model('matter\enroll\data')->getMultitext($ek, $schema, ['fields' => 'id,multitext_seq,agreed,value,like_num,like_log,remark_num,supplement,tag']);

		$options = [];
		if (count($oRecordDatas)) {
			$data_ids = [];
			foreach ($oRecordDatas as $oRecordData) {
				$data_ids[] = $oRecordData->id;
			}
			$options['data_id'] = $data_ids;
		}

		$result = $this->model('matter\enroll\remark')->listByRecord($oUser, $ek, $schema, $page, $size, $options);

		$result->data = $oRecordDatas;

		return new \ResponseData($result);
	}
	/**
	 *
	 */
	public function summary_action($ek) {
		$q = [
			'schema_id,remark_num,last_remark_at',
			'xxt_enroll_record_data',
			['enroll_key' => $ek],
		];
		$values = $this->model()->query_objs_ss($q);

		return new \ResponseData($values);
	}
	/**
	 * 给指定的登记记录的添加评论
	 * 进行评论操作的用户需满足进入活动规则的条件
	 * $id  xxt_enroll_record_data 的id
	 */
	public function add_action($ek, $schema = '', $remark = 0, $id = 0) {
		$modelRec = $this->model('matter\enroll\record');
		$oRecord = $modelRec->byId($ek);
		if (false === $oRecord) {
			return new \ObjectNotFoundError();
		}
		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($oRecord->aid, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		/* 操作规则 */
		$oActionRule = $this->checkActionRule($oApp);
		if (isset($oActionRule->passed) && $oActionRule->passed === 'N') {
			return new \ComplianceError('用户身份不符合进入规则，无法发表评论');
		}

		$data = $this->getPostJson();
		if (empty($data->content)) {
			return new \ResponseError('评论内容不允许为空');
		}

		$oUser = $this->who;
		if (!empty($oApp->group_app_id)) {
			$modelUsr = $this->model('matter\enroll\user');
			$options = ['fields' => 'group_id'];
			if (!empty($oRecord->rid)) {
				$options['rid'] = $oRecord->rid;
			}
			$oEnrollee = $modelUsr->byId($oApp, $oUser->uid, $options);
			if ($oEnrollee) {
				$oUser->group_id = $oEnrollee->group_id;
			}
		}
		$userNickname = $modelEnl->getUserNickname($oApp, $oUser);
		$oUser->nickname = $userNickname;
		/**
		 * 发表评论的用户
		 */
		$data_id = 0; 
		//如果是多项填写题需要指定id，否则，则不需要
		if (!empty($schema)) {
			foreach ($oApp->dataSchemas as $dataSchema) {
				if ($dataSchema->id === $schema && $dataSchema->type === 'multitext') {
					if (empty($id)) {
						return new \ComplianceError('参数错误，此题型需要指定唯一标识');
					}
					$schemaType = 'multitext';
					$data_id = $id;
					$oRecordData = $this->model('matter\enroll\data')->byId($data_id, ['fields' => 'aid,id,like_log,userid,multitext_seq']);
					if (false === $oRecordData) {
						return new \ObjectNotFoundError();
					}
				}
			}
		}
		$current = time();
		$oRemark = new \stdClass;
		$oRemark->siteid = $oRecord->siteid;
		$oRemark->aid = $oRecord->aid;
		$oRemark->rid = $oRecord->rid;
		$oRemark->userid = $oUser->uid;
		$oRemark->group_id = isset($oUser->group_id) ? $oUser->group_id : '';
		$oRemark->user_src = 'S';
		$oRemark->nickname = $modelRec->escape($oUser->nickname);
		$oRemark->enroll_key = $ek;
		$oRemark->enroll_group_id = $oRecord->group_id;
		$oRemark->enroll_userid = $oRecord->userid;
		$oRemark->schema_id = $modelRec->escape($schema);
		$oRemark->data_id = $modelRec->escape($data_id);
		$oRemark->remark_id = $modelRec->escape($remark);
		$oRemark->create_at = $current;
		$oRemark->content = $modelRec->escape($data->content);

		$oRemark->id = $modelRec->insert('xxt_enroll_record_remark', $oRemark, true);

		$modelRec->update("update xxt_enroll_record set remark_num=remark_num+1 where enroll_key='$ek'");
		if (isset($schema)) {
			if (isset($schemaType) && $schemaType === 'multitext' && !empty($data_id)) {
				$modelRec->update("update xxt_enroll_record_data set remark_num=remark_num+1,last_remark_at=$current where id = $modelRec->escape($data_id)");
				// 如果每一条的数据呗评论了那么这道题的总数据+1
				if ($oRecordData->multitext_seq != 0) {
					$modelRec->update("update xxt_enroll_record_data set remark_num=remark_num+1,last_remark_at=$current where enroll_key='$ek' and schema_id='$schema' and multitext_seq = 0");
				}
			} else {
				$modelRec->update("update xxt_enroll_record_data set remark_num=remark_num+1,last_remark_at=$current where enroll_key='$ek' and schema_id='$schema' and multitext_seq = 0");
			}
		}

		$modelUsr = $this->model('matter\enroll\user');
		$modelUsr->setOnlyWriteDbConn(true);

		/* 更新进行点评的活动用户的积分奖励 */
		$modelMat = $this->model('matter\enroll\coin');
		$modelMat->setOnlyWriteDbConn(true);
		$rulesOther = $modelMat->rulesByMatter('site.matter.enroll.data.other.comment', $oApp);
		$rulesOwner = $modelMat->rulesByMatter('site.matter.enroll.data.comment', $oApp);

		$modelCoin = $this->model('site\coin\log');
		$modelCoin->setOnlyWriteDbConn(true);
		$modelCoin->award($oApp, $oUser, 'site.matter.enroll.data.other.comment', $rulesOther);

		/* 获得所属轮次 */
		$modelRun = $this->model('matter\enroll\round');
		if ($activeRound = $modelRun->getActive($oApp)) {
			$rid = $activeRound->rid;
		} else {
			$rid = '';
		}

		/* 更新发起评论的活动用户轮次数据 */
		$oEnrollUsr = $modelUsr->byId($oApp, $oUser->uid, ['fields' => 'id,nickname,last_remark_other_at,remark_other_num,user_total_coin', 'rid' => $rid]);
		if (false === $oEnrollUsr) {
			$inData = ['last_remark_other_at' => time(), 'remark_other_num' => 1];
			$inData['user_total_coin'] = 0;
			foreach ($rulesOther as $ruleOther) {
				$inData['user_total_coin'] = $inData['user_total_coin'] + (int) $ruleOther->actor_delta;
			}

			$inData['rid'] = $rid;
			$modelUsr->add($oApp, $oUser, $inData);
		} else {
			$upData = ['last_remark_other_at' => time(), 'remark_other_num' => $oEnrollUsr->remark_other_num + 1];
			$upData['user_total_coin'] = $oEnrollUsr->user_total_coin;
			foreach ($rulesOther as $ruleOther) {
				$upData['user_total_coin'] = $upData['user_total_coin'] + (int) $ruleOther->actor_delta;
			}
			$modelUsr->update(
				'xxt_enroll_user',
				$upData,
				['id' => $oEnrollUsr->id]
			);
		}
		/* 更新发起评论的活动用户总数据 */
		$oEnrollUsrALL = $modelUsr->byId($oApp, $oUser->uid, ['fields' => 'id,nickname,last_remark_other_at,remark_other_num,user_total_coin', 'rid' => 'ALL']);
		if (false === $oEnrollUsrALL) {
			$inDataALL = ['last_remark_other_at' => time(), 'remark_other_num' => 1];
			$inDataALL['user_total_coin'] = 0;
			foreach ($rulesOther as $ruleOther) {
				$inDataALL['user_total_coin'] = $inDataALL['user_total_coin'] + (int) $ruleOther->actor_delta;
			}

			$inDataALL['rid'] = 'ALL';
			$modelUsr->add($oApp, $oUser, $inDataALL);
		} else {
			$upDataALL = ['last_remark_other_at' => time(), 'remark_other_num' => $oEnrollUsrALL->remark_other_num + 1];
			$upDataALL['user_total_coin'] = $oEnrollUsrALL->user_total_coin;
			foreach ($rulesOther as $ruleOther) {
				$upDataALL['user_total_coin'] = $upDataALL['user_total_coin'] + (int) $ruleOther->actor_delta;
			}
			$modelUsr->update(
				'xxt_enroll_user',
				$upDataALL,
				['id' => $oEnrollUsrALL->id]
			);
		}

		/* 更新被评论的活动用户轮次数据 */
		$oEnrollUsr = $modelUsr->byId($oApp, $oRecord->userid, ['fields' => 'id,userid,nickname,last_remark_at,remark_num,user_total_coin', 'rid' => $rid]);
		if ($oEnrollUsr) {
			/* 更新被点评的活动用户的积分奖励 */
			$user = new \stdClass;
			$user->uid = $oEnrollUsr->userid;
			$user->nickname = $oEnrollUsr->nickname;
			$modelCoin->award($oApp, $user, 'site.matter.enroll.data.comment', $rulesOwner);

			$upData2 = ['last_remark_at' => time(), 'remark_num' => $oEnrollUsr->remark_num + 1];
			$upData2['user_total_coin'] = (int) $oEnrollUsr->user_total_coin;
			foreach ($rulesOwner as $rule) {
				$upData2['user_total_coin'] = $upData2['user_total_coin'] + (int) $rule->actor_delta;
			}
			$modelUsr->update(
				'xxt_enroll_user',
				$upData2,
				['id' => $oEnrollUsr->id]
			);
		}
		/* 更新被评论的活动用户总数据 */
		$oEnrollUsrALL = $modelUsr->byId($oApp, $oRecord->userid, ['fields' => 'id,userid,nickname,last_remark_at,remark_num,user_total_coin', 'rid' => 'ALL']);
		if ($oEnrollUsrALL) {
			/* 更新被点评的活动用户的积分奖励 */
			$upData2 = ['last_remark_at' => time(), 'remark_num' => $oEnrollUsrALL->remark_num + 1];
			$upData2['user_total_coin'] = (int) $oEnrollUsrALL->user_total_coin;
			foreach ($rulesOwner as $rule) {
				$upData2['user_total_coin'] = $upData2['user_total_coin'] + (int) $rule->actor_delta;
			}
			$modelUsr->update(
				'xxt_enroll_user',
				$upData2,
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
			/* 更新发起评论的活动用户总数据 */
			$oMisUsr = $modelMisUsr->byId($oMission, $oUser->uid, ['fields' => 'id,nickname,last_remark_other_at,remark_other_num,user_total_coin']);
			if (false === $oMisUsr) {
				$aNewMisUser = ['last_remark_other_at' => time(), 'remark_other_num' => 1];
				$aNewMisUser['user_total_coin'] = 0;
				foreach ($rulesOther as $ruleOther) {
					$aNewMisUser['user_total_coin'] = $aNewMisUser['user_total_coin'] + (int) $ruleOther->actor_delta;
				}
				$modelMisUsr->add($oMission, $oUser, $aNewMisUser);
			} else {
				$aUpdMisUsr = ['last_remark_other_at' => time(), 'remark_other_num' => $oMisUsr->remark_other_num + 1];
				$aUpdMisUsr['user_total_coin'] = $oMisUsr->user_total_coin;
				foreach ($rulesOther as $ruleOther) {
					$aUpdMisUsr['user_total_coin'] = $aUpdMisUsr['user_total_coin'] + (int) $ruleOther->actor_delta;
				}
				$modelMisUsr->update(
					'xxt_mission_user',
					$aUpdMisUsr,
					['id' => $oMisUsr->id]
				);
			}
			/* 更新被评论的活动用户总数据 */
			$oMisUsr = $modelMisUsr->byId($oMission, $oRecord->userid, ['fields' => 'id,userid,nickname,last_remark_at,remark_num,user_total_coin', 'rid' => 'ALL']);
			if ($oMisUsr) {
				$oUpdMisUser = ['last_remark_at' => time(), 'remark_num' => $oMisUsr->remark_num + 1];
				$oUpdMisUser['user_total_coin'] = (int) $oMisUsr->user_total_coin;
				foreach ($rulesOwner as $rule) {
					$oUpdMisUser['user_total_coin'] = $oUpdMisUser['user_total_coin'] + (int) $rule->actor_delta;
				}
				$modelMisUsr->update(
					'xxt_mission_user',
					$oUpdMisUser,
					['id' => $oMisUsr->id]
				);
			}
		}

		$this->_notifyHasRemark($oApp, $oRecord, $oRemark);

		return new \ResponseData($oRemark);
	}
	/**
	 * 通知评论登记记录事件
	 */
	private function _notifyHasRemark($oApp, $oRecord, $oRemark) {
		/* 模板消息参数 */
		$notice = $this->model('site\notice')->byName($oApp->siteid, 'site.enroll.remark');
		if ($notice === false) {
			return false;
		}
		$tmplConfig = $this->model('matter\tmplmsg\config')->byId($notice->tmplmsg_config_id, ['cascaded' => 'Y']);
		if (!isset($tmplConfig->tmplmsg)) {
			return false;
		}

		$params = new \stdClass;
		foreach ($tmplConfig->tmplmsg->params as $param) {
			if (!isset($tmplConfig->mapping->{$param->pname})) {
				continue;
			}
			$mapping = $tmplConfig->mapping->{$param->pname};
			if (isset($mapping->src)) {
				if ($mapping->src === 'matter') {
					if (isset($oApp->{$mapping->id})) {
						$value = $oApp->{$mapping->id};
					} else if ($mapping->id === 'event_at') {
						$value = date('Y-m-d H:i:s');
					}
				} else if ($mapping->src === 'text') {
					$value = $mapping->name;
				}
			}
			!isset($value) && $value = '';
			$params->{$param->pname} = $value;
		}
		/**
		 * 给记录的提交人发送通知
		 */
		if ($oRecord->userid !== $oRemark->userid) {
			/* 发送给登记记录的提交人 */
			$noticeURL = $this->model('matter\enroll')->getEntryUrl($oApp->siteid, $oApp->id);
			$noticeURL .= '&page=remark&ek=' . $oRecord->enroll_key;
			$noticeURL .= '&schema=' . $oRemark->schema_id;
			$params->url = $noticeURL;

			/* 消息的接收人 */
			$oEnroller = new \stdClass;
			$oEnroller->assoc_with = $oRecord->enroll_key;
			$oEnroller->userid = $oRecord->userid;

			/* 消息的创建人 */
			$oCreator = new \stdClass;
			$oCreator->uid = $oRemark->userid;
			$oCreator->name = $oRemark->nickname;
			$oCreator->src = 'site';

			/* 给用户发通知消息 */
			$modelTmplBat = $this->model('matter\tmplmsg\batch');
			$modelTmplBat->send($oRecord->siteid, $tmplConfig->msgid, $oCreator, [$oEnroller], $params, ['send_from' => 'enroll:' . $oRecord->aid . ':' . $oRecord->enroll_key]);
		}
		/**
		 * 给活动管理员发送通知
		 */
		$receivers = $this->model('matter\enroll\receiver')->byApp($oApp->siteid, $oApp->id);
		if (count($receivers)) {
			/* 获得活动的管理员链接 */
			$appURL = $this->model('matter\enroll')->getOpUrl($oApp->siteid, $oApp->id);
			$modelQurl = $this->model('q\url');
			$noticeURL = $modelQurl->urlByUrl($oApp->siteid, $appURL);
			$params->url = $noticeURL;

			/* 发送消息 */
			foreach ($receivers as &$receiver) {
				if (!empty($receiver->sns_user)) {
					$snsUser = json_decode($receiver->sns_user);
					if (isset($snsUser->src) && isset($snsUser->openid)) {
						$receiver->{$snsUser->src . '_openid'} = $snsUser->openid;
					}
				}
			}
			/* 发送给活动管理员 */
			$modelTmplBat = $this->model('matter\tmplmsg\plbatch');
			$modelTmplBat->send($oApp->siteid, $tmplConfig->msgid, $receivers, $params, ['event_name' => 'site.enroll.remark', 'send_from' => 'enroll:' . $oApp->id . ':' . $oRemark->enroll_key]);
		}

		return true;
	}
	/**
	 * 点赞登记记录中的某一个评论
	 *
	 * @param string $remark remark'id
	 *
	 */
	public function like_action($remark) {
		$modelRem = $this->model('matter\enroll\remark');
		$oRemark = $modelRem->byId($remark, ['fields' => 'aid,id,like_log']);
		if (false === $oRemark) {
			return new \ObjectNotFoundError();
		}

		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($oRemark->aid, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		$oLikeLog = $oRemark->like_log;

		$oUser = $this->who;

		if (isset($oLikeLog->{$oUser->uid})) {
			unset($oLikeLog->{$oUser->uid});
			$incLikeNum = -1;
		} else {
			$oLikeLog->{$oUser->uid} = time();
			$incLikeNum = 1;
		}
		$likeNum = count(get_object_vars($oLikeLog));

		$modelRem->update(
			'xxt_enroll_record_remark',
			['like_log' => json_encode($oLikeLog), 'like_num' => $likeNum],
			['id' => $oRemark->id]
		);

		$modelUsr = $this->model('matter\enroll\user');
		$modelUsr->setOnlyWriteDbConn(true);
		/* 更新进行点赞的活动用户的数据 */
		$oEnrollUsr = $modelUsr->byId($oApp, $this->who->uid, ['fields' => 'id,nickname,last_like_other_remark_at,like_other_remark_num']);
		if (false === $oEnrollUsr) {
			$modelUsr->add($oApp, $this->who, ['last_like_other_remark_at' => time(), 'like_other_remark_num' => 1]);
		} else {
			$modelUsr->update(
				'xxt_enroll_user',
				['last_like_other_remark_at' => time(), 'like_other_remark_num' => $oEnrollUsr->like_other_remark_num + $incLikeNum],
				['id' => $oEnrollUsr->id]
			);
		}
		/* 更新被点赞的活动用户的数据 */
		$oEnrollUsr = $modelUsr->byId($oApp, $this->who->uid, ['fields' => 'id,nickname,last_like_remark_at,like_remark_num']);
		if ($oEnrollUsr) {
			$modelUsr->update(
				'xxt_enroll_user',
				['last_like_remark_at' => time(), 'like_remark_num' => $oEnrollUsr->like_remark_num + $incLikeNum],
				['id' => $oEnrollUsr->id]
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
			$oMisUsr = $modelMisUsr->byId($oMission, $this->who->uid, ['fields' => 'id,nickname,last_like_other_remark_at,like_other_remark_num']);
			if (false === $oMisUsr) {
				$modelMisUsr->add($oMission, $this->who, ['last_like_other_remark_at' => time(), 'like_other_remark_num' => 1]);
			} else {
				$modelMisUsr->update(
					'xxt_mission_user',
					['last_like_other_remark_at' => time(), 'like_other_remark_num' => $oMisUsr->like_other_remark_num + $incLikeNum],
					['id' => $oMisUsr->id]
				);
			}
			/* 更新被点赞的活动用户的数据 */
			$oMisUsr = $modelMisUsr->byId($oMission, $this->who->uid, ['fields' => 'id,nickname,last_like_remark_at,like_remark_num']);
			if ($oMisUsr) {
				$modelMisUsr->update(
					'xxt_mission_user',
					['last_like_remark_at' => time(), 'like_remark_num' => $oMisUsr->like_remark_num + $incLikeNum],
					['id' => $oMisUsr->id]
				);
			}
		}

		return new \ResponseData(['like_log' => $oLikeLog, 'like_num' => $likeNum]);
	}
}