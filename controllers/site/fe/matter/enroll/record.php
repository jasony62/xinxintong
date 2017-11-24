<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动记录
 */
class record extends base {
	/**
	 * 解决跨域异步提交问题
	 */
	public function submitkeyGet_action() {
		/* support CORS */
		//header('Access-Control-Allow-Origin:*');

		$key = md5(uniqid() . mt_rand());

		return new \ResponseData($key);
	}
	/**
	 * 记录登记信息
	 *
	 * @param string $site
	 * @param string $app
	 * @param string $ek enrollKey 如果要更新之前已经提交的数据，需要指定
	 * @param string $submitkey 支持文件分段上传
	 */
	public function submit_action($site, $app, $ek = null, $submitkey = '') {
		/* support CORS */
		//header('Access-Control-Allow-Origin:*');
		//header('Access-Control-Allow-Methods:POST');
		//header('Access-Control-Allow-Headers:Content-Type');
		//$_SERVER['REQUEST_METHOD'] === 'OPTIONS' && exit;

		if (empty($site)) {
			header('HTTP/1.0 500 parameter error:site is empty.');
			die('参数错误！');
		}
		if (empty($app)) {
			header('HTTP/1.0 500 parameter error:app is empty.');
			die('参数错误！');
		}

		$bSubmitNewRecord = empty($ek); // 是否为提交新纪录

		$modelEnl = $this->model('matter\enroll');
		$modelEnlRec = $this->model('matter\enroll\record');

		if (false === ($oEnrollApp = $modelEnl->byId($app, ['cascaded' => 'N']))) {
			header('HTTP/1.0 500 parameter error:app dosen\'t exist.');
			die('登记活动不存在');
		}
		// 判断活动是否添加了轮次
		if ($oEnrollApp->multi_rounds == 'Y') {
			$modelRnd = $this->model('matter\enroll\round');
			$oActiveRnd = $modelRnd->getActive($oEnrollApp);
			$now = time();
			if (empty($oActiveRnd) || (!empty($oActiveRnd) && ($oActiveRnd->end_at != 0) && $oActiveRnd->end_at < $now)) {
				return new \ResponseError('当前活动轮次已结束，不能提交、修改、保存或删除！');
			}
		}

		$oUser = clone $this->who;

		/* 记录数据提交日志，跟踪提交特殊数据失败的问题 */
		$rawPosted = file_get_contents("php://input");
		$modelLog = $this->model('log');
		$modelLog->log('trace', 'enroll-submit-' . $oUser->uid, $modelLog->cleanEmoji($rawPosted, true));

		// 提交的数据
		$posted = $this->getPostJson();
		if (empty($posted) || count(get_object_vars($posted)) === 0) {
			return new \ResponseError('没有提交有效数据');
		}

		if (isset($posted->data)) {
			$oEnrolledData = $posted->data;
		} else {
			$oEnrolledData = $posted;
		}
		if ((isset($oEnrollApp->assignedNickname->valid) && $oEnrollApp->assignedNickname->valid === 'Y') && isset($oEnrollApp->assignedNickname->schema->id)) {
			$oUser->nickname = $modelEnlRec->getValueBySchema($oEnrollApp->assignedNickname->schema, $oEnrolledData);
		} else {
			/* 当前访问用户的基本信息 */
			$userNickname = $modelEnl->getUserNickname($oEnrollApp, $oUser);
			$oUser->nickname = $userNickname;
		}

		// 检查是否允许登记
		$rst = $this->_canSubmit($oEnrollApp, $oUser, $oEnrolledData, $ek);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}
		/**
		 * 检查是否存在匹配的登记记录
		 */
		if (!empty($oEnrollApp->enroll_app_id)) {
			$oMatchApp = $modelEnl->byId($oEnrollApp->enroll_app_id, ['cascaded' => 'N']);
			if (empty($oMatchApp)) {
				return new \ParameterError('指定的登记匹配登记活动不存在');
			}
			/* 获得要检查的登记项 */
			$requireCheckedData = new \stdClass;
			$dataSchemas = $oEnrollApp->dataSchemas;
			foreach ($dataSchemas as $oSchema) {
				if (isset($oSchema->requireCheck) && $oSchema->requireCheck === 'Y') {
					if (isset($oSchema->fromApp) && $oSchema->fromApp === $oEnrollApp->enroll_app_id) {
						$requireCheckedData->{$oSchema->id} = $modelEnlRec->getValueBySchema($oSchema, $oEnrolledData);
					}
				}
			}
			/* 在指定的登记活动中检查数据 */
			$modelMatchRec = $this->model('matter\enroll\record');
			$matchedRecords = $modelMatchRec->byData($oMatchApp, $requireCheckedData);
			if (empty($matchedRecords)) {
				return new \ParameterError('未在指定的登记活动［' . $oMatchApp->title . '］中找到与提交数据相匹配的记录');
			}
			/* 如果匹配的分组数据不唯一，怎么办？ */
			if (count($matchedRecords) > 1) {
				return new \ParameterError('在指定的登记活动［' . $oMatchApp->title . '］中找到多条与提交数据相匹配的记录，匹配关系不唯一');
			}
			$oEnrollRecord = $matchedRecords[0];
			if ($oEnrollRecord->verified !== 'Y') {
				return new \ParameterError('在指定的登记活动［' . $oMatchApp->title . '］中与提交数据匹配的记录未通过验证');
			}
			/* 如果登记数据中未包含用户信息，更新用户信息 */
			if (empty($oEnrollRecord->userid)) {
				$oUserAcnt = $this->model('site\user\account')->byId($oUser->uid, ['fields' => 'wx_openid,yx_openid,qy_openid,headimgurl']);
				if (false === $oUserAcnt) {
					$oUserAcnt = new \stdClass;
				}
				$oUserAcnt->userid = $oUser->uid;
				$oUserAcnt->nickname = $modelMatchRec->escape($oUser->nickname);
				$modelMatchRec->update('xxt_enroll_record', $oUserAcnt, ['id' => $oEnrollRecord->id]);
			}
			/* 将匹配的登记记录数据作为提交的登记数据的一部分 */
			$oMatchedData = $oEnrollRecord->data;
			foreach ($oMatchApp->dataSchemas as $oSchema) {
				if (!isset($oEnrolledData->{$oSchema->id}) && isset($oMatchedData->{$oSchema->id})) {
					$oEnrolledData->{$oSchema->id} = $oMatchedData->{$oSchema->id};
				}
			}
		}
		/**
		 * 检查是否存在匹配的分组记录
		 */
		if (!empty($oEnrollApp->group_app_id)) {
			$oGroupApp = $this->model('matter\group')->byId($oEnrollApp->group_app_id);
			if (empty($oGroupApp)) {
				return new \ParameterError('指定的登记匹配分组活动不存在');
			}
			/* 获得要检查的登记项 */
			$requireCheckedData = new \stdClass;
			$dataSchemas = $oEnrollApp->dataSchemas;
			foreach ($dataSchemas as $oSchema) {
				if (isset($oSchema->requireCheck) && $oSchema->requireCheck === 'Y') {
					if (isset($oSchema->fromApp) && $oSchema->fromApp === $oEnrollApp->group_app_id) {
						$requireCheckedData->{$oSchema->id} = $modelEnlRec->getValueBySchema($oSchema, $oEnrolledData);
					}
				}
			}
			/* 在指定的分组活动中检查数据 */
			$modelMatchRec = $this->model('matter\group\player');
			$groupRecords = $modelMatchRec->byData($oGroupApp, $requireCheckedData);
			if (empty($groupRecords)) {
				return new \ParameterError('未在指定的分组活动［' . $oGroupApp->title . '］中找到与提交数据相匹配的记录');
			}
			/* 如果匹配的分组数据不唯一，怎么办？ */
			if (count($groupRecords) > 1) {
				return new \ParameterError('在指定的分组活动［' . $oGroupApp->title . '］中找到多条与提交数据相匹配的记录，匹配关系不唯一');
			}
			$oGroupRecord = $groupRecords[0];
			/* 如果分组数据中未包含用户信息，更新用户信息 */
			if (empty($oGroupRecord->userid)) {
				$oUserAcnt = $this->model('site\user\account')->byId($oUser->uid, ['fields' => 'wx_openid,yx_openid,qy_openid,headimgurl']);
				if (false === $oUserAcnt) {
					$oUserAcnt = new \stdClass;
				}
				$oUserAcnt->userid = $oUser->uid;
				$oUserAcnt->nickname = $modelMatchRec->escape($oUser->nickname);
				$modelMatchRec->update('xxt_group_player', $oUserAcnt, ['id' => $oGroupRecord->id]);
			}
			/* 将匹配的分组记录数据作为提交的登记数据的一部分 */
			$oMatchedData = $oGroupRecord->data;
			foreach ($oGroupApp->dataSchemas as $oSchema) {
				if (!isset($oEnrolledData->{$oSchema->id}) && isset($oMatchedData->{$oSchema->id})) {
					$oEnrolledData->{$oSchema->id} = $oMatchedData->{$oSchema->id};
				}
			}
			/* 所属分组id */
			if (isset($oGroupRecord->round_id)) {
				$oUser->group_id = $oEnrolledData->_round_id = $oGroupRecord->round_id;
			}
		}
		/**
		 * 提交用户身份信息
		 */
		// if (isset($oEnrolledData->member) && isset($oEnrolledData->member->schema_id)) {
		// 	$member = clone $oEnrolledData->member;
		// 	$rst = $this->_submitMember($site, $member, $oUser);
		// 	if ($rst[0] === false) {
		// 		return new \ParameterError($rst[1]);
		// 	}
		// }
		/**
		 * 提交登记数据
		 */
		$oUpdatedEnrollRec = [];
		$modelRec = $this->model('matter\enroll\record')->setOnlyWriteDbConn(true);
		if ($bSubmitNewRecord) {
			/* 插入登记数据 */
			$ek = $modelRec->enroll($oEnrollApp, $oUser, ['nickname' => $oUser->nickname]);
			/* 处理自定义信息 */
			$rst = $modelRec->setData($oUser, $oEnrollApp, $ek, $oEnrolledData, $submitkey, true);
			/* 登记提交的积分奖励 */
			$modelMat = $this->model('matter\enroll\coin')->setOnlyWriteDbConn(true);
			$rules = $modelMat->rulesByMatter('site.matter.enroll.submit', $oEnrollApp);
			$modelCoin = $this->model('site\coin\log')->setOnlyWriteDbConn(true);
			$modelCoin->award($oEnrollApp, $oUser, 'site.matter.enroll.submit', $rules);
		} else {
			/* 重新插入新提交的数据 */
			$rst = $modelRec->setData($oUser, $oEnrollApp, $ek, $oEnrolledData, $submitkey);
			if ($rst[0] === true) {
				/* 已经登记，更新原先提交的数据，只要进行更新操作就设置为未审核通过的状态 */
				$oUpdatedEnrollRec['enroll_at'] = time();
				$oUpdatedEnrollRec['userid'] = $oUser->uid;
				$oUpdatedEnrollRec['nickname'] = $oUser->nickname;
				$oUpdatedEnrollRec['verified'] = 'N';
			}
		}
		if (false === $rst[0]) {
			return new \ResponseError($rst[1]);
		}
		$oSubmitedEnrollRec = $rst[1]; // 包含data和score
		/**
		 * 提交填写项数据标签
		 */
		if (isset($posted->tag) && count(get_object_vars($posted->tag))) {
			$rst = $modelRec->setTag($oUser, $oEnrollApp, $ek, $posted->tag);
		}
		/**
		 * 提交补充说明
		 */
		if (isset($posted->supplement) && count(get_object_vars($posted->supplement))) {
			$rst = $modelRec->setSupplement($oUser, $oEnrollApp, $ek, $posted->supplement);
		}
		if (isset($matchedRecord)) {
			$oUpdatedEnrollRec['matched_enroll_key'] = $matchedRecord->enroll_key;
		}
		if (isset($groupRecord)) {
			$oUpdatedEnrollRec['group_enroll_key'] = $groupRecord->enroll_key;
		}
		if (count($oUpdatedEnrollRec)) {
			$modelRec->update(
				'xxt_enroll_record',
				$oUpdatedEnrollRec,
				"enroll_key='$ek'"
			);
		}
		/* 记录操作日志 */
		$this->_logSubmit($oEnrollApp, $ek);
		/* 登记用户行为及积分 */
		$modelUsr = $this->model('matter\enroll\user');
		$modelUsr->setOnlyWriteDbConn(true);

		/* 获得所属轮次 */
		$modelRun = $this->model('matter\enroll\round');
		if ($oActiveRnd = $modelRun->getActive($oEnrollApp)) {
			$rid = $oActiveRnd->rid;
		} else {
			$rid = '';
		}

		/* 更新活动用户轮次数据 */
		$oEnrollUsr = $modelUsr->byId($oEnrollApp, $oUser->uid, ['fields' => 'id,nickname,group_id,last_enroll_at,enroll_num,user_total_coin', 'rid' => $rid]);
		if (false === $oEnrollUsr) {
			$inData = ['last_enroll_at' => time(), 'enroll_num' => 1];
			if (!empty($rules)) {
				$inData['user_total_coin'] = 0;
				foreach ($rules as $rule) {
					$inData['user_total_coin'] = $inData['user_total_coin'] + (int) $rule->actor_delta;
				}
			}
			$inData['rid'] = $rid;
			if (isset($oSubmitedEnrollRec->score->sum)) {
				$inData['score'] = $oSubmitedEnrollRec->score->sum;
			}
			$modelUsr->add($oEnrollApp, $oUser, $inData);
		} else {
			$upData = [];
			if ($oEnrollUsr->nickname !== $oUser->nickname) {
				$upData['nickname'] = $oUser->nickname;
			}
			$upData['last_enroll_at'] = time();
			if (isset($oUser->group_id)) {
				if ($oEnrollUsr->group_id !== $oUser->group_id) {
					$upData['group_id'] = $oUser->group_id;
				}
			}
			if ($bSubmitNewRecord) {
				$upData['enroll_num'] = (int) $oEnrollUsr->enroll_num + 1;
				if (!empty($rules)) {
					$upData['user_total_coin'] = (int) $oEnrollUsr->user_total_coin;
					foreach ($rules as $rule) {
						$upData['user_total_coin'] = $upData['user_total_coin'] + (int) $rule->actor_delta;
					}
				}
			}
			if (isset($oSubmitedEnrollRec->score->sum)) {
				$upData['score'] = $oSubmitedEnrollRec->score->sum;
			}
			$modelUsr->update(
				'xxt_enroll_user',
				$upData,
				['id' => $oEnrollUsr->id]
			);
		}
		/* 更新活动用户总数据 */
		$oEnrollUsrALL = $modelUsr->byId($oEnrollApp, $oUser->uid, ['fields' => 'id,nickname,group_id,last_enroll_at,enroll_num,user_total_coin', 'rid' => 'ALL']);
		if (false === $oEnrollUsrALL) {
			$inDataALL = ['last_enroll_at' => time(), 'enroll_num' => 1];
			if (!empty($rules)) {
				$inDataALL['user_total_coin'] = 0;
				foreach ($rules as $rule) {
					$inDataALL['user_total_coin'] = $inDataALL['user_total_coin'] + (int) $rule->actor_delta;
				}
			}
			$inDataALL['rid'] = 'ALL';
			$modelUsr->add($oEnrollApp, $oUser, $inDataALL);
		} else {
			$upDataALL = [];
			if ($oEnrollUsrALL->nickname !== $oUser->nickname) {
				$upDataALL['nickname'] = $oUser->nickname;
			}
			$upDataALL['last_enroll_at'] = time();
			if (isset($oUser->group_id)) {
				if ($oEnrollUsrALL->group_id !== $oUser->group_id) {
					$upDataALL['group_id'] = $oUser->group_id;
				}
			}
			if ($bSubmitNewRecord) {
				$upDataALL['enroll_num'] = (int) $oEnrollUsrALL->enroll_num + 1;
				if (!empty($rules)) {
					$upDataALL['user_total_coin'] = (int) $oEnrollUsrALL->user_total_coin;
					foreach ($rules as $rule) {
						$upDataALL['user_total_coin'] = $upDataALL['user_total_coin'] + (int) $rule->actor_delta;
					}
				}
			}

			/* 更新用户获得的分数 */
			$enrollees = $modelUsr->query_objs_ss([
				'id,score',
				'xxt_enroll_user',
				"siteid='$oEnrollApp->siteid' and aid='$oEnrollApp->id' and userid='$oUser->uid' and rid !='ALL'",
			]);
			$total = 0;
			foreach ($enrollees as $oEnrollee) {
				if (!empty($oEnrollee->score)) {
					$total += (float) $oEnrollee->score;
				}
			}
			$upDataALL['score'] = $total;
			$modelUsr->update(
				'xxt_enroll_user',
				$upDataALL,
				['id' => $oEnrollUsrALL->id]
			);
		}
		/**
		 * 更新项目用户数据
		 */
		if (!empty($oEnrollApp->mission_id)) {
			$modelMisUsr = $this->model('matter\mission\user');
			$modelMisUsr->setOnlyWriteDbConn(true);
			$oMission = $this->model('matter\mission')->byId($oEnrollApp->mission_id, ['fields' => 'siteid,id,user_app_type,user_app_id']);
			if ($oMission->user_app_type === 'group') {
				$oMisUsrGrpApp = (object) ['id' => $oMission->user_app_id];
				$oMisGrpUser = $this->model('matter\group\player')->byUser($oMisUsrGrpApp, $oUser->uid, ['onlyOne' => true, 'round_id']);
			}
			$oMisUsr = $modelMisUsr->byId($oMission, $oUser->uid, ['fields' => 'id,nickname,group_id,last_enroll_at,enroll_num,user_total_coin']);
			if (false === $oMisUsr) {
				$aNewMisUser = ['last_enroll_at' => time(), 'enroll_num' => 1];
				if (!empty($oMisGrpUser->round_id)) {
					$aNewMisUser['group_id'] = $oMisGrpUser->round_id;
				}
				if (!empty($rules)) {
					$aNewMisUser['user_total_coin'] = 0;
					foreach ($rules as $rule) {
						$aNewMisUser['user_total_coin'] = $aNewMisUser['user_total_coin'] + (int) $rule->actor_delta;
					}
				}
				$modelMisUsr->add($oMission, $oUser, $aNewMisUser);
			} else {
				$aUpdMisUser = ['last_enroll_at' => time()];
				if ($oMisUsr->nickname !== $oUser->nickname) {
					$aUpdMisUser['nickname'] = $oUser->nickname;
				}
				if (isset($oMisGrpUser->round_id)) {
					if ($oMisUsr->group_id !== $oMisGrpUser->round_id) {
						$aUpdMisUser['group_id'] = $oMisGrpUser->round_id;
					}
				}
				if ($bSubmitNewRecord) {
					$aUpdMisUser['enroll_num'] = (int) $oMisUsr->enroll_num + 1;
					if (!empty($rules)) {
						$aUpdMisUser['user_total_coin'] = (int) $oMisUsr->user_total_coin;
						foreach ($rules as $rule) {
							$aUpdMisUser['user_total_coin'] = $aUpdMisUser['user_total_coin'] + (int) $rule->actor_delta;
						}
					}
				}
				$modelMisUsr->update(
					'xxt_mission_user',
					$aUpdMisUser,
					['id' => $oMisUsr->id]
				);
			}
		}
		/**
		 * 通知登记活动事件接收人
		 */
		if ($oEnrollApp->notify_submit === 'Y') {
			$this->_notifyReceivers($oEnrollApp, $ek);
		}

		return new \ResponseData($ek);
	}
	/**
	 * 记录用户提交日志
	 *
	 * @param object $app
	 *
	 */
	private function _logSubmit($oApp, $ek) {
		$modelLog = $this->model('matter\log');

		$logUser = new \stdClass;
		$logUser->userid = $this->who->uid;
		$logUser->nickname = $this->who->nickname;

		$operation = new \stdClass;
		$operation->name = 'submit';
		$operation->data = $this->model('matter\enroll\record')->byId($ek, ['fields' => 'enroll_key,data']);

		$client = new \stdClass;
		$client->agent = $_SERVER['HTTP_USER_AGENT'];
		$client->ip = $this->client_ip();

		$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

		$logid = $modelLog->addUserMatterOp($oApp->siteid, $logUser, $oApp, $operation, $client, $referer);

		return $logid;
	}
	/**
	 * 检查是否允许用户进行登记
	 *
	 * 检查内容：
	 * 1、应用允许登记的条数（count_limit）
	 * 2、登记项是否和已有登记记录重复（schema.unique）
	 * 3、多选题选项的数量（schema.limitChoice, schema.range）
	 *
	 */
	private function _canSubmit($oApp, $oUser, $oRecData, $ek) {
		/**
		 * 检查活动是否在进行过程中
		 */
		$current = time();
		if (!empty($oApp->start_at) && $oApp->start_at > $current) {
			return [false, ['活动没有开始，不允许修改数据']];
		}
		if (!empty($oApp->end_at) && $oApp->end_at < $current) {
			return [false, ['活动已经结束，不允许修改数据']];
		}
		if (!empty($oApp->end_submit_at) && $oApp->end_submit_at < $current) {
			return [false, ['活动提交时间已经结束，不允许修改数据']];
		}

		$modelRec = $this->model('matter\enroll\record');
		if (empty($ek)) {
			/**
			 * 检查登记数量
			 */
			if ($oApp->count_limit > 0) {
				$records = $modelRec->byUser($oApp, $oUser);
				if (count($records) >= $oApp->count_limit) {
					return [false, ['已经进行过' . count($records) . '次登记，不允再次登记']];
				}
			}
		} else {
			/**
			 * 检查提交人
			 */
			if (empty($oApp->can_cowork) || $oApp->can_cowork === 'N') {
				if ($oRecord = $modelRec->byId($ek, ['fields' => 'userid'])) {
					if ($oRecord->userid !== $oUser->uid) {
						return [false, ['不允许修改其他用户提交的数据']];
					}
				}
			}
		}
		/**
		 * 检查提交数据的合法性
		 */
		foreach ($oApp->dataSchemas as $oSchema) {
			if (isset($oSchema->unique) && $oSchema->unique === 'Y') {
				if (empty($oRecData->{$oSchema->id})) {
					return [false, ['唯一项【' . $oSchema->title . '】不允许为空']];
				}
				$checked = new \stdClass;
				$checked->{$oSchema->id} = $oRecData->{$oSchema->id};
				$existings = $modelRec->byData($oApp, $checked, ['fields' => 'enroll_key']);
				if (count($existings)) {
					foreach ($existings as $existing) {
						if ($existing->enroll_key !== $ek) {
							return [false, ['唯一项【' . $oSchema->title . '】不允许重复，请检查填写的数据']];
						}
					}
				}
			}
			if (isset($oSchema->type)) {
				switch ($oSchema->type) {
				case 'multiple':
					if (isset($oSchema->limitChoice) && $oSchema->limitChoice === 'Y' && isset($oSchema->range) && is_array($oSchema->range)) {
						if (isset($oRecData->{$oSchema->id})) {
							$submitVal = $oRecData->{$oSchema->id};
							if (is_object($submitVal)) {
								// 多选题，将选项合并为逗号分隔的字符串
								$opCount = count(array_filter((array) $submitVal, function ($i) {return $i;}));
							} else {
								$opCount = 0;
							}
						} else {
							$opCount = 0;
						}
						if ($opCount < $oSchema->range[0] || $opCount > $oSchema->range[1]) {
							return [false, ['选择题【' . $oSchema->title . '】选中的选项数量，不在指定范围【' . implode('-', $oSchema->range) . '】内']];
						}
					}
					break;
				}
			}
		}

		return [true];
	}
	/**
	 * 提交信息中包含的自定义用户信息
	 */
	private function _submitMember($siteId, &$member, &$user) {
		$schemaId = $member->schema_id;
		$oMschema = $this->model('site\user\memberschema')->byId($schemaId, ['fields' => 'siteid,id,title,auto_verified,attr_mobile,attr_email,attr_name,extattr']);
		$modelMem = $this->model('site\user\member');

		$existentMember = $modelMem->byUser($user->uid, ['schemas' => $schemaId]);
		if (count($existentMember)) {
			$memberId = $existentMember[0]->id;
			$member->id = $memberId;
			$member->verified = $existentMember[0]->verified;
			$member->identity = $existentMember[0]->identity;
			$rst = $modelMem->modify($oMschema, $memberId, $member);
		} else {
			$rst = $modelMem->createByApp($oMschema, $user->uid, $member);
			/**
			 * 将用户自定义信息和当前用户进行绑定
			 */
			if ($rst[0] === true) {
				$member = $rst[1];
				$this->model('site\fe\way')->bindMember($siteId, $member);
			}
		}
		$member->schema_id = $schemaId;

		return $rst;
	}
	/**
	 * 通知登记活动事件接收人
	 *
	 * @param object $app
	 * @param string $ek
	 *
	 */
	private function _notifyReceivers(&$oApp, $ek) {
		$receivers = $this->model('matter\enroll\receiver')->byApp($oApp->siteid, $oApp->id);
		if (count($receivers) === 0) {
			return false;
		}
		/* 获得活动的管理员链接 */
		$appURL = $this->model('matter\enroll')->getOpUrl($oApp->siteid, $oApp->id);
		$modelQurl = $this->model('q\url');
		$noticeURL = $modelQurl->urlByUrl($oApp->siteid, $appURL);
		/* 模板消息参数 */
		$params = new \stdClass;
		$notice = $this->model('site\notice')->byName($oApp->siteid, 'site.enroll.submit');
		if ($notice === false) {
			return false;
		}
		$tmplConfig = $this->model('matter\tmplmsg\config')->byId($notice->tmplmsg_config_id, ['cascaded' => 'Y']);
		if (!isset($tmplConfig->tmplmsg)) {
			return false;
		}
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
			$params->{$param->pname} = isset($value) ? $value : '';
		}
		$noticeURL && $params->url = $noticeURL;

		/* 发送消息 */
		foreach ($receivers as &$receiver) {
			if (!empty($receiver->sns_user)) {
				$snsUser = json_decode($receiver->sns_user);
				if (isset($snsUser->src) && isset($snsUser->openid)) {
					$receiver->{$snsUser->src . '_openid'} = $snsUser->openid;
				}
			}
		}

		$modelTmplBat = $this->model('matter\tmplmsg\plbatch');
		$modelTmplBat->send($oApp->siteid, $tmplConfig->msgid, $receivers, $params, ['event_name' => 'site.enroll.submit', 'send_from' => 'enroll:' . $oApp->id . ':' . $ek]);

		return true;
	}
	/**
	 * 分段上传文件
	 *
	 * @param string $site
	 * @param string $app
	 * @param string $submitKey
	 *
	 */
	public function uploadFile_action($site, $app, $submitkey = '') {
		/* support CORS */
		//header('Access-Control-Allow-Origin:*');
		//header('Access-Control-Allow-Methods:POST');
		//if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
		//	exit;
		//}
		if (empty($submitkey)) {
			$user = $this->who;
			$submitkey = $user->uid;
		}
		/** 分块上传文件 */
		if (defined('SAE_TMP_PATH')) {
			$dest = '/' . $app . '/' . $submitkey . '_' . $_POST['resumableFilename'];
			$resumable = \TMS_APP::M('fs/resumableAliOss', $site, $dest, 'xinxintong');
			$resumable->handleRequest($_POST);
		} else {
			$modelFs = \TMS_APP::M('fs/local', $site, '_resumable');
			$dest = $submitkey . '_' . $_POST['resumableIdentifier'];
			$resumable = \TMS_APP::M('fs/resumable', $site, $dest, $modelFs);
			$resumable->handleRequest($_POST);
		}

		return new \ResponseData('ok');
	}
	/**
	 * 给当前用户产生一条空的登记记录，记录传递的数据，并返回这条记录
	 * 适用于抽奖后记录兑奖信息
	 *
	 * @param string $site
	 * @param string $app
	 * @param string $once 如果已经有登记记录，不生成新的登记记录
	 */
	public function emptyGet_action($site, $app, $once = 'N') {
		$posted = $this->getPostJson();

		$model = $this->model('matter\enroll');
		if (false === ($oApp = $model->byId($app))) {
			return new \ParameterError("指定的活动（$app）不存在");
		}
		/**
		 * 当前访问用户的基本信息
		 */
		$user = $this->who;
		/* 如果已经有登记记录则不登记 */
		$modelRec = $this->model('matter\enroll\record');
		if ($once === 'Y') {
			$ek = $modelRec->lastKeyByUser($oApp, $user);
		}
		/* 创建登记记录*/
		if (empty($ek)) {
			$options = [
				'enrollAt' => time(),
				'referrer' => (empty($posted->referrer) ? '' : $posted->referrer),
			];
			$ek = $modelRec->enroll($oApp, $user, $options);
			/**
			 * 处理提交数据
			 */
			$data = $_GET;
			unset($data['site']);
			unset($data['app']);
			if (!empty($data)) {
				$data = (object) $data;
				$rst = $modelRec->setData($user, $oApp, $ek, $data);
				if (false === $rst[0]) {
					return new ResponseError($rst[1]);
				}
			}
		}
		/*登记记录的URL*/
		$url = '/rest/site/fe/matter/enroll';
		$url .= '?site=' . $site;
		$url .= '&app=' . $oApp->id;
		$url .= '&ek=' . $ek;

		$rsp = new \stdClass;
		$rsp->url = $url;
		$rsp->ek = $ek;

		return new \ResponseData($rsp);
	}
	/**
	 * 返回指定记录或最后一条记录
	 * @param string $site
	 * @param string $app
	 * @param string $ek
	 */
	public function get_action($site, $app, $ek = '') {
		$modelApp = $this->model('matter\enroll');
		$modelRec = $this->model('matter\enroll\record');
		$openedek = $ek;
		$record = null;

		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		/*当前访问用户的基本信息*/
		$oUser = $this->who;
		/**登记数据*/
		if (empty($openedek)) {
			// 获得最后一条登记数据。登记记录有可能未进行过登记
			$record = $modelRec->lastByUser($oApp, $oUser, ['fields' => '*', 'verbose' => 'Y']);
			if ($record) {
				$openedek = $record->enroll_key;
			}
		} else {
			// 打开指定的登记记录
			$record = $modelRec->byId($openedek, ['verbose' => 'Y']);
		}

		return new \ResponseData($record);
	}
	/**
	 * 列出所有的登记记录
	 *
	 * $site
	 * $app
	 * $orderby time|remark|score|follower
	 * $page
	 * $size
	 *
	 * return
	 * [0] 数据列表
	 * [1] 数据总条数
	 * [2] 数据项的定义
	 *
	 */
	public function list_action($site, $app, $owner = 'U', $orderby = 'time', $page = 1, $size = 30) {
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->who;
		// 登记数据过滤条件
		$oCriteria = $this->getPostJson();

		switch ($owner) {
		case 'I':
			$options = array(
				'inviter' => $oUser->uid,
			);
			break;
		case 'A':
			$options = array();
			break;
		case 'G':
			$modelUsr = $this->model('matter\enroll\user');
			$options = ['fields' => 'group_id'];
			$oEnrollee = $modelUsr->byId($oApp, $oUser->uid, $options);
			$options = array(
				'userGroup' => isset($oEnrollee->group_id) ? $oEnrollee->group_id : '',
			);
			break;
		default:
			$options = array(
				'creater' => $oUser->uid,
			);
			break;
		}
		$options['page'] = $page;
		$options['size'] = $size;
		$options['orderby'] = $orderby;

		$modelRec = $this->model('matter\enroll\record');

		$rst = $modelRec->byApp($oApp, $options, $oCriteria);

		return new \ResponseData($rst);
	}
	/**
	 * 点赞登记记录中的某一个题
	 *
	 * @param string $ek
	 * @param string $schema
	 *
	 */
	public function like_action($ek, $schema) {
		$modelData = $this->model('matter\enroll\data');
		$oRecordData = $modelData->byRecord($ek, ['schema' => $schema, 'fields' => 'aid,id,like_log,userid']);
		if (false === $oRecordData) {
			return new \ObjectNotFoundError();
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
		$likeNum = count(get_object_vars($oLikeLog));

		$modelData->update(
			'xxt_enroll_record_data',
			['like_log' => json_encode($oLikeLog), 'like_num' => $likeNum],
			['id' => $oRecordData->id]
		);

		$oApp = $this->model('matter\enroll')->byId($oRecordData->aid, ['cascaded' => 'N']);
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

		return new \ResponseData(['like_log' => $oLikeLog, 'like_num' => $likeNum]);
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
		$oRecData = $modelData->byRecord($ek, ['schema' => $schema, 'fields' => 'aid,userid,agreed,agreed_log']);
		if (false === $oRecData) {
			return new \ObjectNotFoundError();
		}

		$oApp = $this->model('matter\enroll')->byId($oRecData->aid, ['cascaded' => 'N', 'fields' => 'entry_rule']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		if (empty($oApp->entry_rule->group->id) || empty($oApp->entry_rule->group->round->id)) {
			return new \ParameterError('只有进入条件为分组活动的登记活动才允许组长推荐');
		}
		$modelGrpUsr = $this->model('matter\group\player');
		$oGrpLeader = $modelGrpUsr->byUser($oApp->entry_rule->group, $this->who->uid, ['fields' => 'is_leader,round_id', 'onlyOne' => true]);
		if (false === $oGrpLeader || $oGrpLeader->is_leader !== 'Y') {
			return new \ParameterError('只有允许组长进行推荐');
		}
		if ($oGrpLeader->round_id !== $oApp->entry_rule->group->round->id) {
			return new \ParameterError('只允许推荐本组数据');
		}
		$oGrpMemb = $modelGrpUsr->byUser($oApp->entry_rule->group, $this->who->uid, ['fields' => 'round_id', 'onlyOne' => true]);
		if (false === $oGrpMemb || $oGrpMemb->round_id !== $oApp->entry_rule->group->round->id) {
			return new \ParameterError('被推荐的数据必须在指定分组内');
		}

		if (!in_array($value, ['Y', 'N', 'A'])) {
			$value = '';
		}
		// 确定模板名称
		// if ($value == 'Y') {
		// 	$name = 'site.enroll.submit.recommend';
		// } else if ($value == 'N') {
		// 	$name = 'site.enroll.submit.mask';
		// }

		// if (!empty($name)) {
		// 	$modelRec = $this->model('matter\enroll\record');
		// 	$oRecord = $modelRec->byId($ek);
		// 	$modelEnl = $this->model('matter\enroll');
		// 	$oApp = $modelEnl->byId($oRecord->aid, ['cascaded' => 'N']);
		// 	$this->_notifyAgree($oApp, $oRecord, $name, $schema);
		// }

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
			['enroll_key' => $ek, 'schema_id' => $schema, 'state' => 1]
		);

		return new \ResponseData($rst);
	}
	/**
	 * 删除当前记录
	 *
	 * @param string $site
	 * @param string $app
	 */
	public function remove_action($site, $app, $ek) {
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if ($oApp === false) {
			return new \ObjectNotFoundError();
		}

		// 判断活动是否添加了轮次
		if ($oApp->multi_rounds == 'Y') {
			$modelRnd = $this->model('matter\enroll\round');
			$oActiveRnd = $modelRnd->getActive($oApp);
			$now = time();
			if (empty($oActiveRnd) || (!empty($oActiveRnd) && ($oActiveRnd->end_at != 0) && $oActiveRnd->end_at < $now)) {
				return new \ResponseError('当前活动轮次已结束，不能提交、修改、保存或删除！');
			}
		}

		$modelRec = $this->model('matter\enroll\record');
		$rst = $modelRec->removeByUser($site, $app, $ek);

		return new \ResponseData($rst);
	}
}