<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 记录活动记录
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
	 * 记录记录信息
	 *
	 * @param string $app
	 * @param string $rid 指定在哪一个轮次上提交（仅限新建的情况）
	 * @param string $ek enrollKey 如果要更新之前已经提交的数据，需要指定
	 * @param string $submitkey 支持文件分段上传
	 */
	public function submit_action($app, $rid = '', $ek = null, $submitkey = '', $subType = 'submit') {
		$modelEnl = $this->model('matter\enroll');
		$oEnlApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oEnlApp || $oEnlApp->state !== '1') {
			return new \ObjectNotFoundError('（1）指定的活动不存在');
		}

		$modelRec = $this->model('matter\enroll\record')->setOnlyWriteDbConn(true);

		$bSubmitNewRecord = empty($ek); // 是否为新记录

		if (!$bSubmitNewRecord) {
			$oBeforeRecord = $modelRec->byId($ek, ['state' => ['1', '99']]);
			if (false === $oBeforeRecord) {
				return new \ObjectNotFoundError('（2）指定的填写记录不存在');
			}
			if ($oBeforeRecord->state === '99') {
				/* 将之前保存的记录作为提交记录 */
				$modelRec->update('xxt_enroll_record', ['state' => '1'], ['enroll_key' => $ek]);
				$oBeforeRecord->state = '1';
			}
			$rid = $oBeforeRecord->rid;
		}

		// 检查或获得提交轮次
		$aResultSubmitRid = $this->_getSubmitRecordRid($oEnlApp, $rid);
		if (false === $aResultSubmitRid[0]) {
			return new \ResponseError($aResultSubmitRid[1]);
		}
		$rid = $aResultSubmitRid[1];

		// 提交的数据
		$oPosted = $this->getPostJson();
		if (empty($oPosted->data) || count(get_object_vars($oPosted->data)) === 0) {
			return new \ResponseError('（3）没有提交有效数据');
		}
		$oEnlData = $oPosted->data;

		// 提交数据的用户
		$oUser = $this->getUser($oEnlApp, $oEnlData);

		// 检查是否允许提交记录
		$aResultCanSubmit = $this->_canSubmit($oEnlApp, $oUser, $oEnlData, $ek, $rid);
		if ($aResultCanSubmit[0] === false) {
			return new \ResponseError($aResultCanSubmit[1]);
		}
		/**
		 * 检查是否存在匹配的记录记录
		 */
		if (!empty($oEnlApp->entryRule->enroll->id)) {
			$oMatchApp = $modelEnl->byId($oEnlApp->entryRule->enroll->id, ['cascaded' => 'N']);
			if (empty($oMatchApp)) {
				return new \ParameterError('指定的记录匹配记录活动不存在');
			}
			/* 获得要检查的记录项 */
			$requireCheckedData = new \stdClass;
			$dataSchemas = $oEnlApp->dataSchemas;
			foreach ($dataSchemas as $oSchema) {
				if (isset($oSchema->requireCheck) && $oSchema->requireCheck === 'Y') {
					if (isset($oSchema->fromApp) && $oSchema->fromApp === $oEnlApp->entryRule->enroll->id) {
						$requireCheckedData->{$oSchema->id} = $modelRec->getValueBySchema($oSchema, $oEnlData);
					}
				}
			}
			/* 在指定的记录活动中检查数据 */
			$modelMatchRec = $this->model('matter\enroll\record');
			$matchedRecords = $modelMatchRec->byData($oMatchApp, $requireCheckedData);
			if (empty($matchedRecords)) {
				return new \ParameterError('未在指定的记录活动［' . $oMatchApp->title . '］中找到与提交数据相匹配的记录');
			}
			/* 如果匹配的分组数据不唯一，怎么办？ */
			if (count($matchedRecords) > 1) {
				return new \ParameterError('在指定的记录活动［' . $oMatchApp->title . '］中找到多条与提交数据相匹配的记录，匹配关系不唯一');
			}
			$oMatchedEnlRec = $matchedRecords[0];
			if ($oMatchedEnlRec->verified !== 'Y') {
				return new \ParameterError('在指定的记录活动［' . $oMatchApp->title . '］中与提交数据匹配的记录未通过验证');
			}
			/* 如果记录数据中未包含用户信息，更新用户信息 */
			if (empty($oMatchedEnlRec->userid)) {
				$oUserAcnt = $this->model('site\user\account')->byId($oUser->uid, ['fields' => 'wx_openid,yx_openid,qy_openid,headimgurl']);
				if (false === $oUserAcnt) {
					$oUserAcnt = new \stdClass;
				}
				$oUserAcnt->userid = $oUser->uid;
				$oUserAcnt->nickname = $modelMatchRec->escape($oUser->nickname);
				$modelMatchRec->update('xxt_enroll_record', $oUserAcnt, ['id' => $oMatchedEnlRec->id]);
			}
			/* 将匹配的记录记录数据作为提交的记录数据的一部分 */
			$oMatchedData = $oMatchedEnlRec->data;
			foreach ($oMatchApp->dataSchemas as $oSchema) {
				if (!isset($oEnlData->{$oSchema->id}) && isset($oMatchedData->{$oSchema->id})) {
					$oEnlData->{$oSchema->id} = $oMatchedData->{$oSchema->id};
				}
			}
		}
		/**
		 * 检查是否存在匹配的分组记录
		 */
		if (isset($oEnlApp->entryRule->group->id)) {
			/* 获得要检查的记录项 */
			$countRequireCheckedData = 0;
			$requireCheckedData = new \stdClass;
			$dataSchemas = $oEnlApp->dynaDataSchemas;
			foreach ($dataSchemas as $oSchema) {
				if (isset($oSchema->requireCheck) && $oSchema->requireCheck === 'Y') {
					if (isset($oSchema->fromApp) && $oSchema->fromApp === $oEnlApp->entryRule->group->id) {
						$countRequireCheckedData++;
						$requireCheckedData->{$oSchema->id} = $modelRec->getValueBySchema($oSchema, $oEnlData);
					}
				}
			}
			if ($countRequireCheckedData > 0) {
				$oGroupApp = $this->model('matter\group')->byId($oEnlApp->entryRule->group->id);
				if (empty($oGroupApp)) {
					return new \ParameterError('指定的记录匹配分组活动不存在');
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
				$oMatchedGrpRec = $groupRecords[0];
				/* 如果分组数据中未包含用户信息，更新用户信息 */
				if (empty($oMatchedGrpRec->userid)) {
					$oUserAcnt = $this->model('site\user\account')->byId($oUser->uid, ['fields' => 'wx_openid,yx_openid,qy_openid,headimgurl']);
					if (false === $oUserAcnt) {
						$oUserAcnt = new \stdClass;
					}
					$oUserAcnt->userid = $oUser->uid;
					$oUserAcnt->nickname = $modelMatchRec->escape($oUser->nickname);
					$modelMatchRec->update('xxt_group_player', $oUserAcnt, ['id' => $oMatchedGrpRec->id]);
				}
				/* 将匹配的分组记录数据作为提交的记录数据的一部分 */
				$oMatchedData = $oMatchedGrpRec->data;
				foreach ($oGroupApp->dataSchemas as $oSchema) {
					if (!isset($oEnlData->{$oSchema->id}) && isset($oMatchedData->{$oSchema->id})) {
						$oEnlData->{$oSchema->id} = $oMatchedData->{$oSchema->id};
					}
				}
				/* 所属分组id */
				if (isset($oMatchedGrpRec->round_id)) {
					$oUser->group_id = $oEnlData->_round_id = $oMatchedGrpRec->round_id;
				}
			}
		}
		/**
		 * 提交记录数据
		 */
		$aUpdatedEnlRec = [];
		$bReviseRecordBeyondRound = false;
		if ($bSubmitNewRecord) {
			/* 插入记录数据 */
			$oNewRec = $modelRec->enroll($oEnlApp, $oUser, ['nickname' => $oUser->nickname, 'assignedRid' => $rid, 'state' => '1']);
			$ek = $oNewRec->enroll_key;
			/* 处理自定义信息 */
			$aResultSetData = $modelRec->setData($oUser, $oEnlApp, $ek, $oEnlData, $submitkey, true);
		} else {
			/* 重新插入新提交的数据 */
			$aResultSetData = $modelRec->setData($oUser, $oEnlApp, $ek, $oEnlData, $submitkey);
			/* 修改后的记录在当前轮次可见 */
			if ($this->getDeepValue($oEnlApp, 'scenarioConfig.visibleRevisedRecordAtRound') === 'Y') {
				$aResult = $this->model('matter\enroll\round')->reviseRecord($oEnlApp->appRound, $oBeforeRecord);
				if (true === $aResult[0]) {
					$bReviseRecordBeyondRound = true;
				}
			}
			if ($aResultSetData[0] === true) {
				/* 已经记录，更新原先提交的数据，只要进行更新操作就设置为未审核通过的状态 */
				$aUpdatedEnlRec['enroll_at'] = time();
				if ($oBeforeRecord->userid === $oUser->uid) {
					$aUpdatedEnlRec['group_id'] = empty($oUser->group_id) ? '' : $oUser->group_id;
					$aUpdatedEnlRec['nickname'] = $modelRec->escape($oUser->nickname);
				}
				$aUpdatedEnlRec['verified'] = 'N';
			}
		}
		if (false === $aResultSetData[0]) {
			return new \ResponseError($aResultSetData[1]);
		}
		/**
		 * 提交补充说明
		 */
		if (isset($oPosted->supplement) && count(get_object_vars($oPosted->supplement))) {
			$modelRec->setSupplement($oUser, $oEnlApp, $ek, $oPosted->supplement);
		}
		/**
		 * 关联记录
		 */
		if (isset($oMatchedEnlRec)) {
			$aUpdatedEnlRec['matched_enroll_key'] = $oMatchedEnlRec->enroll_key;
		}
		if (isset($oMatchedGrpRec)) {
			$aUpdatedEnlRec['group_enroll_key'] = $oMatchedGrpRec->enroll_key;
		}
		if (count($aUpdatedEnlRec)) {
			$modelRec->update(
				'xxt_enroll_record',
				$aUpdatedEnlRec,
				['enroll_key' => $ek]
			);
		}
		$oRecord = $modelRec->byId($ek);
		/**
		 * 处理用户按轮次汇总数据，积分数据
		 */
		$modelRec->setSummaryRec($oUser, $oEnlApp, $oRecord->rid);
		/**
		 * 更新得分题目排名
		 */
		$modelRec->setScoreRank($oEnlApp, $oRecord->rid);
		/**
		 * 处理用户汇总数据，积分数据
		 */
		if ($bReviseRecordBeyondRound) {
			$oRoundRecord = clone $oRecord;
			$oRoundRecord->rid = $oEnlApp->appRound->rid;
			$this->model('matter\enroll\event')->submitRecord($oEnlApp, $oRoundRecord, $oUser, $bSubmitNewRecord, true);
		} else {
			$this->model('matter\enroll\event')->submitRecord($oEnlApp, $oRecord, $oUser, $bSubmitNewRecord);
		}
		/**
		 * 更新用户得分排名
		 */
		$modelEnlUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
		$modelEnlUsr->setScoreRank($oEnlApp, $oRecord->rid);
		/**
		 * 如果存在提问任务，将记录放到任务专题中
		 */
		if ($bSubmitNewRecord) {
			$tasks = $this->model('matter\enroll\task', $oEnlApp)->currentByUser($oUser, ['type' => 'question']);
			if (!empty($tasks)) {
				$modelTop = $this->model('matter\enroll\topic', $oEnlApp);
				$oTask = $tasks[0];
				$oTopic = $modelTop->byTask($oTask);
				if ($oTopic) {
					$modelTop->assign($oTopic, $oRecord);
				}
			}
		}

		/* 生成提醒 */
		if ($bSubmitNewRecord) {
			$this->model('matter\enroll\notice')->addRecord($oEnlApp, $oRecord, $oUser);
		}
		/* 通知记录活动事件接收人 */
		if ($this->getDeepValue($oEnlApp, 'notifyConfig.submit.valid') === true) {
			$this->_notifyReceivers($oEnlApp, $oRecord);
		}

		return new \ResponseData($oRecord);
	}
	/**
	 * 记录记录信息
	 *
	 * @param string $app
	 * @param string $rid 指定在哪一个轮次上提交（仅限新建的情况）
	 * @param string $ek enrollKey 如果要更新之前已经提交的数据，需要指定
	 * @param string $submitkey 支持文件分段上传
	 */
	public function save_action($app, $rid = '', $ek = null, $submitkey = '') {
		$modelEnl = $this->model('matter\enroll');
		$oEnlApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oEnlApp || $oEnlApp->state !== '1') {
			return new \ObjectNotFoundError('指定的活动不存在');
		}

		$modelRec = $this->model('matter\enroll\record')->setOnlyWriteDbConn(true);

		$bSaveNewRecord = empty($ek); // 是否为提交新记录

		if (!$bSaveNewRecord) {
			$oBeforeRecord = $modelRec->byId($ek, ['state' => ['1', '99']]);
			if (false === $oBeforeRecord) {
				return new \ObjectNotFoundError('指定的填写记录不存在');
			}
			if ($oBeforeRecord->state === '1') {
				return new \ResponseError('记录已经提交，不能再进行保存操作');
			}
			$rid = $oBeforeRecord->rid;
		}

		// 保存轮次
		$aResultSaveRid = $this->_getSubmitRecordRid($oEnlApp, $rid);
		if (false === $aResultSaveRid[0]) {
			return new \ResponseError($aResultSaveRid[1]);
		}
		$rid = $aResultSaveRid[1];

		// 保存的数据
		$oPosted = $this->getPostJson();
		if (empty($oPosted->data) || count(get_object_vars($oPosted->data)) === 0) {
			return new \ResponseError('没有保存有效数据');
		}
		$oEnlData = $oPosted->data;

		// 保存数据的用户
		$oUser = $this->getUser($oEnlApp, $oEnlData);

		// 检查是否允许记录
		$aResultCanSubmit = $this->_canSubmit($oEnlApp, $oUser, $oEnlData, $ek, $rid, false);
		if ($aResultCanSubmit[0] === false) {
			return new \ResponseError($aResultCanSubmit[1]);
		}
		/**
		 * 保存记录数据
		 */
		$aUpdatedEnlRec = [];
		if ($bSaveNewRecord) {
			/* 插入记录数据 */
			$oNewRec = $modelRec->enroll($oEnlApp, $oUser, ['nickname' => $oUser->nickname, 'assignedRid' => $rid, 'state' => '99']);
			$ek = $oNewRec->enroll_key;
		} else {
			$modelRec->update('xxt_enroll_record', ['enroll_at' => time()], ['enroll_key' => $ek]);
		}
		/* 保存数据 */
		$aResultSetData = $modelRec->setData($oUser, $oEnlApp, $ek, $oEnlData, $submitkey, $bSaveNewRecord);
		if (false === $aResultSetData[0]) {
			return new \ResponseError($aResultSetData[1]);
		}
		/**
		 * 保存补充说明
		 */
		if (isset($oPosted->supplement) && count(get_object_vars($oPosted->supplement))) {
			$modelRec->setSupplement($oUser, $oEnlApp, $ek, $oPosted->supplement);
		}

		$oRecord = $modelRec->byId($ek);

		$this->model('matter\enroll\event')->saveRecord($oEnlApp, $oRecord, $oUser);

		return new \ResponseData($oRecord);
	}
	/**
	 * 返回当前轮次或者检查指定轮次是否有效
	 */
	private function _getSubmitRecordRid($oApp, $rid = '') {
		$modelRnd = $this->model('matter\enroll\round');
		if (empty($rid)) {
			$oRecordRnd = $modelRnd->getActive($oApp);
		} else {
			$oRecordRnd = $modelRnd->byId($rid);
		}
		if (empty($oRecordRnd)) {
			return [false, '没有获得有效的活动轮次，请检查是否已经设置轮次，或者轮次是否已经启用'];
		} else {
			$now = time();
			if ($oRecordRnd->end_at != 0 && $oRecordRnd->end_at < $now) {
				return [false, '活动轮次【' . $oRecordRnd->title . '】已结束，不能提交、修改、保存或删除填写记录！'];
			}
		}

		return [true, $oRecordRnd->rid];
	}
	/**
	 * 记录用户提交日志
	 *
	 * @param object $oApp
	 *
	 */
	private function _logUserOp($oApp, $oOperation, $oUser) {
		$modelLog = $this->model('matter\log');

		$oLogUser = new \stdClass;
		$oLogUser->userid = $oUser->uid;
		$oLogUser->nickname = $oUser->nickname;

		$oClient = new \stdClass;
		$oClient->agent = $_SERVER['HTTP_USER_AGENT'];
		$oClient->ip = $this->client_ip();

		$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

		$logid = $modelLog->addUserMatterOp($oApp->siteid, $oLogUser, $oApp, $oOperation, $oClient, $referer);

		return $logid;
	}
	/**
	 * 检查是否允许用户进行记录
	 *
	 * 检查内容：
	 * 1、应用允许记录的条数（count_limit）
	 * 2、记录项是否和已有记录记录重复（schema.unique）
	 * 3、多选题选项的数量（schema.limitChoice, schema.range）
	 *
	 */
	private function _canSubmit($oApp, $oUser, $oRecData, $ek, $rid = '', $bCheckSchema = true) {
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

		if (!empty($oApp->actionRule->record->submit->pre->editor)) {
			if (empty($oUser->is_editor) || $oUser->is_editor !== 'Y') {
				return [false, '仅限活动编辑组用户提交填写记录'];
			}
		}
		if (empty($oApp->entryRule->exclude_action->submit_record) || $oApp->entryRule->exclude_action->submit_record != "Y") {
			$checkEntryRule = $this->checkEntryRule($oApp, false, $oUser);
			if ($checkEntryRule[0] === false) {
				return $checkEntryRule;
			}
		}

		$modelRec = $this->model('matter\enroll\record');
		if (empty($ek)) {
			/**
			 * 检查记录数量
			 */
			if (isset($oApp->count_limit) && $oApp->count_limit > 0) {
				$records = $modelRec->byUser($oApp, $oUser, ['rid' => $rid]);
				if (count($records) >= $oApp->count_limit) {
					return [false, ['已经进行过' . count($records) . '次记录，不允再次记录']];
				}
			}
		} else {
			/**
			 * 检查提交人
			 */
			if ($this->getDeepValue($oApp->scenarioConfig, 'can_cowork') !== 'Y') {
				if ($oRecord = $modelRec->byId($ek, ['fields' => 'userid'])) {
					if ($oRecord->userid !== $oUser->uid && $this->getDeepValue($oUser, 'is_editor') !== 'Y') {
						return [false, ['不允许修改其他用户提交的数据']];
					}
				}
			}
		}
		/**
		 * 检查提交数据的合法性
		 */
		if ($bCheckSchema === true) {
			foreach ($oApp->dynaDataSchemas as $oSchema) {
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
								return [false, ['【' . $oSchema->title . '】中最多只能选择(' . $oSchema->range[1] . ')项，最少需要选择(' . $oSchema->range[0] . ')项']];
							}
						}
						break;
					case 'voice':
						if (!defined('WX_VOICE_AMR_2_MP3') || WX_VOICE_AMR_2_MP3 !== 'Y') {
							return [false, '运行环境不支持处理微信录音文件，题目【' . $oSchema->title . '】无效'];
						}
						break;
					}
				}
			}
		}

		return [true];
	}
	/**
	 * 通知记录活动事件接收人
	 *
	 * @param object $app
	 * @param string $ek
	 *
	 */
	private function _notifyReceivers($oApp, $oRecord) {
		/* 通知接收人 */
		$receivers = $this->model('matter\enroll\user')->getSubmitReceivers($oApp, $oRecord, $oApp->notifyConfig->submit);
		if (empty($receivers)) {
			return false;
		}

		// 指定的提醒页名称，默认为讨论页
		$page = empty($oApp->notifyConfig->submit->page) ? 'cowork' : $oApp->notifyConfig->submit->page;
		switch ($page) {
		case 'repos':
			$noticeURL = $oApp->entryUrl . '&page=repos';
			break;
		default:
			$noticeURL = $oApp->entryUrl . '&ek=' . $oRecord->enroll_key . '&page=cowork';
		}

		$noticeName = 'site.enroll.submit';

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
	 * 分段上传文件
	 *
	 * @param string $app
	 * @param string $submitKey
	 *
	 */
	public function uploadFile_action($app, $submitkey = '') {
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N', 'fields' => 'id,siteid,state']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		if (empty($submitkey)) {
			$submitkey = $this->who->uid;
		}
		/* 检查此文件片段是否已经成功上传 */
		if ($_SERVER['REQUEST_METHOD'] === 'GET') {
			if (!defined('SAE_TMP_PATH')) {
				$rootDir = TMS_UPLOAD_DIR . "$oApp->siteid" . '/' . \TMS_MODEL::toLocalEncoding('_resumable');
				$chunkNumber = $_GET['resumableChunkNumber'];
				$filename = str_replace(' ', '_', $_GET['resumableFilename']);
				$chunkDir = $_GET['resumableIdentifier'] . '_part';
				$chunkFile = \TMS_MODEL::toLocalEncoding($filename) . '.part' . $chunkNumber;
				$absPath = $rootDir . '/' . $chunkDir . '/' . $chunkFile;
				if (file_exists($absPath)) {
					header("HTTP/1.0 200 Ok");
					return new \ResponseData('已上传');
				} else {
					header("HTTP/1.0 404 Not Found");
					return new \ResponseData('未上传');
				}
			} else {
				header("HTTP/1.0 404 Not Found");
				return new \ResponseData('未上传');
			}
		}
		/**
		 * 分块上传文件
		 */
		$dest = '/enroll/' . $oApp->id . '/' . $submitkey . '_' . $_POST['resumableFilename'];
		$oResumable = $this->model('fs/resumable', $oApp->siteid, $dest, '_user');
		$aResult = $oResumable->handleRequest($_POST);
		if (true === $aResult[0]) {
			return new \ResponseData('ok');
		} else {
			return new \ResponseError($aResult[1]);
		}
	}
	/**
	 * 返回指定记录或最后一条记录
	 *
	 * @param string $app
	 * @param string $ek
	 * @param string $loadLast 如果没有指定ek，是否获取最近一条数据
	 * @param string $withSaved 是否获取保存数据
	 *
	 */
	public function get_action($app, $ek = '', $rid = '', $loadLast = 'Y', $loadAssoc = 'Y', $withSaved = 'N') {
		$modelApp = $this->model('matter\enroll');
		$modelRec = $this->model('matter\enroll\record');

		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$fields = 'id,aid,state,rid,enroll_key,userid,group_id,nickname,verified,enroll_at,first_enroll_at,data,supplement,score,like_num,like_log,remark_num';
		$ValidRecStates = ['1', '99'];

		if (empty($ek)) {
			$oRecUser = $this->getUser($oApp);
			if ($loadLast === 'Y') {
				$oRecord = $modelRec->lastByUser($oApp, $oRecUser, ['state' => $ValidRecStates, 'rid' => $rid, 'verbose' => 'Y', 'fields' => $fields]);
				if (false === $oRecord) {
					$oRecord = new \stdClass;
					$oRecord->rid = empty($rid) ? $oApp->appRound->rid : $rid;
				}
			} else {
				$oRecord = new \stdClass;
				$oRecord->rid = empty($rid) ? $oApp->appRound->rid : $rid;
			}
		} else {
			$oRecord = $modelRec->byId($ek, ['verbose' => 'Y', 'fields' => $fields]);
			$oRecUser = new \stdClass;
			if (false === $oRecord || !in_array($oRecord->state, $ValidRecStates)) {
				$oRecord = new \stdClass;
			} else {
				if (!empty($oRecord->userid)) {
					$oRecUser->uid = $oRecord->userid;
				}
			}
		}

		/* 当前用户在关联活动中填写的数据 */
		if (!empty($oRecUser->uid)) {
			if (!empty($oApp->entryRule->enroll->id)) {
				$oAssocApp = $this->model('matter\enroll')->byId($oApp->entryRule->enroll->id, ['cascaded' => 'N']);
				if ($oAssocApp) {
					$oAssocRec = $modelRec->byUser($oAssocApp, $oRecUser);
					if (count($oAssocRec) === 1) {
						if (!empty($oAssocRec[0]->data)) {
							$oAssocRecData = $oAssocRec[0]->data;
							if (!isset($oRecord->data)) {
								$oRecord->data = new \stdClass;
							}
							foreach ($oAssocRecData as $key => $value) {
								if (!isset($oRecord->data->{$key})) {
									$oRecord->data->{$key} = $value;
								}
							}
						}
					}
				}
			}
			if (!empty($oApp->entryRule->group->id)) {
				$oGrpApp = $this->model('matter\group')->byId($oApp->entryRule->group->id, ['cascaded' => 'N']);
				if ($oGrpApp) {
					$oGrpUsr = $this->model('matter\group\user')->byUser($oGrpApp, $oRecUser->uid, ['onlyOne' => true, 'fields' => 'round_id,data']);
					if ($oGrpUsr) {
						if (!isset($oRecord->data)) {
							$oRecord->data = new \stdClass;
						}
						$oAssocRecData = $oGrpUsr->data;
						$oAssocRecData->_round_id = $oGrpUsr->round_id;
						foreach ($oAssocRecData as $k => $v) {
							if (!isset($oRecord->data->{$k})) {
								$oRecord->data->{$k} = $v;
							}
						}
					}
				}
			}
		}

		if (!empty($oRecord->rid)) {
			$oRecRound = $this->model('matter\enroll\round')->byId($oRecord->rid, ['fields' => 'rid,title,purpose,state,start_at,end_at']);
			$oRecord->round = $oRecRound;
		}

		if (count((array) $oRecord) === 0) {
			return new \ObjectNotFoundError('不存在符合指定条件的填写记录');
		}

		return new \ResponseData($oRecord);
	}
	/**
	 * 获得目标值的记录
	 * 目标轮次中的记录
	 *
	 * @param string $app app'id
	 * @param string $rid 指定的轮次
	 */
	public function baseline_action($app, $rid = '') {
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$modelRnd = $this->model('matter\enroll\round');
		$aRndOptions = ['fields' => 'rid,title'];
		if (!empty($rid)) {
			$aRndOptions['assignedRid'] = $rid;
		}
		$oBaselineRnd = $modelRnd->getBaseline($oApp, $aRndOptions);
		if (false === $oBaselineRnd) {
			return new \ResponseData(false);
		}

		$modelRec = $this->model('matter\enroll\record');
		$oBaselineRec = $modelRec->baselineByRound($this->who->uid, $oBaselineRnd);
		if (false === $oBaselineRec) {
			return new \ResponseData(false);
		}
		/* 只有数值题可以有目标值 */
		$oNumberRecData = new \stdClass;
		foreach ($oApp->dynaDataSchemas as $oSchema) {
			if ($oSchema->type === 'shorttext' && $modelRec->getDeepValue($oSchema, 'format') === 'number') {
				$oNumberRecData->{$oSchema->id} = empty($oBaselineRec->data->{$oSchema->id}) ? 0 : $oBaselineRec->data->{$oSchema->id};
			}
		}
		$oBaselineRec->data = $oNumberRecData;

		return new \ResponseData($oBaselineRec);
	}
	/**
	 * 记录的概要信息
	 */
	public function sketch_action($record) {
		$modelRec = $this->model('matter\enroll\record');

		$oSketch = new \stdClass;
		$oRecord = $modelRec->byPlainId($record, ['fields' => 'id,aid,state,enroll_key,agreed,remark_num,like_num,favor_num']);
		if ($oRecord) {
			$modelApp = $this->model('matter\enroll');
			$oApp = $modelApp->byId($oRecord->aid, ['fields' => 'title', 'cascaded' => 'N']);
			$oSketch->raw = $oRecord;
			$oSketch->title = '记录' . $oRecord->id . '|' . $oApp->title;
		}

		return new \ResponseData($oSketch);
	}
	/**
	 * 列出所有的记录记录
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
	public function list_action($site, $app, $owner = 'U', $orderby = 'time', $page = 1, $size = 30, $sketch = 'N') {
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->getUser($oApp);

		// 填写记录过滤条件
		$oCriteria = $this->getPostJson();

		switch ($owner) {
		case 'A':
			break;
		case 'G':
			$modelUsr = $this->model('matter\enroll\user');
			$options = ['fields' => 'group_id'];
			$oEnrollee = $modelUsr->byId($oApp, $oUser->uid, $options);
			if ($oEnrollee) {
				!isset($oCriteria->record) && $oCriteria->record = new \stdClass;
				$oCriteria->record->group_id = isset($oEnrollee->group_id) ? $oEnrollee->group_id : '';
			}
			break;
		default:
			!isset($oCriteria->record) && $oCriteria->record = new \stdClass;
			$oCriteria->record->userid = $oUser->uid;
			break;
		}

		$aOptions = [];
		$aOptions['page'] = $page;
		$aOptions['size'] = $size;
		$aOptions['orderby'] = $orderby;
		if ($sketch === 'Y') {
			$aOptions['fields'] = 'id,enroll_key,enroll_at';
		}

		$modelRec = $this->model('matter\enroll\record');

		$oResult = $modelRec->byApp($oApp, $aOptions, $oCriteria);

		return new \ResponseData($oResult);
	}
	/**
	 * 点赞记录记录
	 *
	 * @param string $ek
	 *
	 */
	public function like_action($ek) {
		$modelRec = $this->model('matter\enroll\record');
		$oRecord = $modelRec->byId($ek, ['fields' => 'id,enroll_key,state,aid,rid,userid,group_id,like_log,like_num']);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oApp = $this->model('matter\enroll')->byId($oRecord->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->getUser($oApp);

		/* 检查是否满足了点赞的前置条件 */
		if (empty($oApp->entryRule->exclude_action->like) || $oApp->entryRule->exclude_action->like != "Y") {
			$checkEntryRule = $this->checkEntryRule($oApp, false, $oUser);
			if ($checkEntryRule[0] === false) {
				return new \ResponseError($checkEntryRule[1]);
			}
		}

		if (!empty($oApp->actionRule->record->like->pre)) {
			/* 当前轮次，当前组已经提交的记录数 */
			$oRule = $oApp->actionRule->record->like->pre;
			if (!empty($oRule->record->num)) {
				$oCriteria = new \stdClass;
				$oCriteria->record = new \stdClass;
				$oCriteria->record->group_id = $oRecord->group_id;
				$oResult = $modelRec->byApp($oApp, ['fields' => 'id'], $oCriteria);
				if ((int) $oResult->total < (int) $oRule->record->num) {
					$desc = empty($oRule->desc) ? ('提交【' . $oRule->record->num . '条】记录后开启点赞（投票）') : $oRule->desc;
					if (!in_array(mb_substr($desc, -1), ['。', '，', '；', '.', ',', ';'])) {
						$desc .= '，';
					}
					$desc .= '还需提交【' . ((int) $oRule->record->num - (int) $oResult->total) . '条】记录。';
					return new \ResponseError($desc);
				}
			}
			if (!empty($oRule->record->submit->end)) {
				if (!empty($oApp->actionRule->record->submit->end->time)) {
					$oTimeRule = $oApp->actionRule->record->submit->end->time;
					if (!empty($oTimeRule->mode) && !empty($oTimeRule->unit) && !empty($oTimeRule->value)) {
						if ($oTimeRule->mode === 'after_round_start_at') {
							if ($oTimeRule->unit === 'hour') {
								$oActiveRnd = $this->model('matter\enroll\round')->getActive($oApp);
								if ($oActiveRnd && !empty($oActiveRnd->start_at)) {
									if (((int) $oActiveRnd->start_at + ($oTimeRule->value * 3600)) > time()) {
										$desc = empty($oRule->desc) ? ('提交记录结束后开启点赞（投票）') : $oRule->desc;
										if (!in_array(mb_substr($desc, -1), ['。', '，', '；', '.', ',', ';'])) {
											$desc .= '，';
										}
										$endDate = date('y-m-j H:i', (int) $oActiveRnd->start_at + ($oTimeRule->value * 3600));
										$desc .= '结束时间【' . $endDate . '】。';
										return new \ResponseError($desc);
									}
								}
							}
						}
					}
				}
			}
		}

		$oLikeLog = $oRecord->like_log;
		if (isset($oLikeLog->{$oUser->uid})) {
			unset($oLikeLog->{$oUser->uid});
			$incLikeNum = -1;
		} else {
			$oLikeLog->{$oUser->uid} = time();
			$incLikeNum = 1;
		}
		/* 检查数量限制 */
		if ($incLikeNum > 0) {
			if (isset($oApp->actionRule->record->like->end)) {
				$oRule = $oApp->actionRule->record->like->end;
				/* 限制了最多点赞次数 */
				if (!empty($oRule->max)) {
					$oAppUser = $this->model('matter\enroll\user')->byId($oApp, $oUser->uid, ['fields' => 'id,do_like_num', 'rid' => $oRecord->rid]);
					if ($oAppUser && (int) $oAppUser->do_like_num >= (int) $oRule->max) {
						$desc = empty($oRule->desc) ? ('点赞次数最多【' . $oRule->max . '】') : $oRule->desc;
						return new \ResponseError($desc);
					}
				}
			}
		}

		$likeNum = $oRecord->like_num + $incLikeNum;
		$modelRec->update(
			'xxt_enroll_record',
			['like_log' => json_encode($oLikeLog), 'like_num' => $likeNum],
			['enroll_key' => $oRecord->enroll_key]
		);

		$modelEnlEvt = $this->model('matter\enroll\event');
		if ($incLikeNum > 0) {
			/* 发起点赞 */
			$modelEnlEvt->likeRecord($oApp, $oRecord, $oUser);
		} else {
			/* 撤销发起点赞 */
			$modelEnlEvt->undoLikeRecord($oApp, $oRecord, $oUser);
		}

		$oResult = new \stdClass;
		$oResult->like_log = $oLikeLog;
		$oResult->like_num = $likeNum;

		return new \ResponseData($oResult);
	}
	/**
	 * 点踩记录记录
	 *
	 *
	 */
	public function dislike_action($ek) {
		$modelRec = $this->model('matter\enroll\record');
		$oRecord = $modelRec->byId($ek, ['fields' => 'id,enroll_key,state,aid,rid,userid,group_id,dislike_log,dislike_num']);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oApp = $this->model('matter\enroll')->byId($oRecord->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->getUser($oApp);

		/* 检查是否满足了点赞/点踩的前置条件 */
		if (empty($oApp->entryRule->exclude_action->like) || $oApp->entryRule->exclude_action->like != "Y") {
			$checkEntryRule = $this->checkEntryRule($oApp, false, $oUser);
			if ($checkEntryRule[0] === false) {
				return new \ResponseError($checkEntryRule[1]);
			}
		}

		// if (!empty($oApp->actionRule->record->like->pre)) {
		// 	/* 当前轮次，当前组已经提交的记录数 */
		// 	$oRule = $oApp->actionRule->record->like->pre;
		// 	if (!empty($oRule->record->num)) {
		// 		$oCriteria = new \stdClass;
		// 		$oCriteria->record = new \stdClass;
		// 		$oCriteria->record->group_id = $oRecord->group_id;
		// 		$oResult = $modelRec->byApp($oApp, ['fields' => 'id'], $oCriteria);
		// 		if ((int) $oResult->total < (int) $oRule->record->num) {
		// 			$desc = empty($oRule->desc) ? ('提交【' . $oRule->record->num . '条】记录后开启点赞（投票）') : $oRule->desc;
		// 			if (!in_array(mb_substr($desc, -1), ['。', '，', '；', '.', ',', ';'])) {
		// 				$desc .= '，';
		// 			}
		// 			$desc .= '还需提交【' . ((int) $oRule->record->num - (int) $oResult->total) . '条】记录。';
		// 			return new \ResponseError($desc);
		// 		}
		// 	}
		// 	if (!empty($oRule->record->submit->end)) {
		// 		if (!empty($oApp->actionRule->record->submit->end->time)) {
		// 			$oTimeRule = $oApp->actionRule->record->submit->end->time;
		// 			if (!empty($oTimeRule->mode) && !empty($oTimeRule->unit) && !empty($oTimeRule->value)) {
		// 				if ($oTimeRule->mode === 'after_round_start_at') {
		// 					if ($oTimeRule->unit === 'hour') {
		// 						$oActiveRnd = $this->model('matter\enroll\round')->getActive($oApp);
		// 						if ($oActiveRnd && !empty($oActiveRnd->start_at)) {
		// 							if (((int) $oActiveRnd->start_at + ($oTimeRule->value * 3600)) > time()) {
		// 								$desc = empty($oRule->desc) ? ('提交记录结束后开启点赞（投票）') : $oRule->desc;
		// 								if (!in_array(mb_substr($desc, -1), ['。', '，', '；', '.', ',', ';'])) {
		// 									$desc .= '，';
		// 								}
		// 								$endDate = date('y-m-j H:i', (int) $oActiveRnd->start_at + ($oTimeRule->value * 3600));
		// 								$desc .= '结束时间【' . $endDate . '】。';
		// 								return new \ResponseError($desc);
		// 							}
		// 						}
		// 					}
		// 				}
		// 			}
		// 		}
		// 	}
		// }

		$oDislikeLog = $oRecord->dislike_log;
		if (isset($oDislikeLog->{$oUser->uid})) {
			unset($oDislikeLog->{$oUser->uid});
			$incDislikeNum = -1;
		} else {
			$oDislikeLog->{$oUser->uid} = time();
			$incDislikeNum = 1;
		}
		/* 检查数量限制 */
		// if ($incDislikeNum > 0) {
		// 	if (isset($oApp->actionRule->record->like->end)) {
		// 		$oRule = $oApp->actionRule->record->like->end;
		// 		/* 限制了最多点赞次数 */
		// 		if (!empty($oRule->max)) {
		// 			$oAppUser = $this->model('matter\enroll\user')->byId($oApp, $oUser->uid, ['fields' => 'id,do_dislike_num', 'rid' => $oRecord->rid]);
		// 			if ($oAppUser && (int) $oAppUser->do_dislike_num >= (int) $oRule->max) {
		// 				$desc = empty($oRule->desc) ? ('点赞次数最多【' . $oRule->max . '】') : $oRule->desc;
		// 				return new \ResponseError($desc);
		// 			}
		// 		}
		// 	}
		// }

		$dislikeNum = $oRecord->dislike_num + $incDislikeNum;
		$modelRec->update(
			'xxt_enroll_record',
			['dislike_log' => json_encode($oDislikeLog), 'dislike_num' => $dislikeNum],
			['enroll_key' => $oRecord->enroll_key]
		);

		$modelEnlEvt = $this->model('matter\enroll\event');
		if ($incDislikeNum > 0) {
			/* 发起反对 */
			$modelEnlEvt->dislikeRecord($oApp, $oRecord, $oUser);
		} else {
			/* 撤销发起反对 */
			$modelEnlEvt->undoDislikeRecord($oApp, $oRecord, $oUser);
		}

		$oResult = new \stdClass;
		$oResult->dislike_log = $oDislikeLog;
		$oResult->dislike_num = $dislikeNum;

		return new \ResponseData($oResult);
	}
	/**
	 * 推荐记录记录中
	 * 只有组长和超级用户才有权限
	 *
	 * @param string $ek
	 * @param string $value
	 *
	 */
	public function agree_action($ek, $value = '') {
		$modelRec = $this->model('matter\enroll\record');
		$oRecord = $modelRec->byId($ek, ['fields' => 'id,state,aid,rid,enroll_key,userid,group_id,agreed,agreed_log']);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oApp = $this->model('matter\enroll')->byId($oRecord->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		if (empty($oApp->entryRule->group->id)) {
			return new \ParameterError('只有进入条件为分组活动的记录活动才允许组长表态');
		}
		$oUser = $this->getUser($oApp);

		$modelGrpUsr = $this->model('matter\group\player');
		/* 当前用户所属分组及角色 */
		$oGrpLeader = $modelGrpUsr->byUser($oApp->entryRule->group, $oUser->uid, ['fields' => 'is_leader,round_id', 'onlyOne' => true]);
		if (false === $oGrpLeader || !in_array($oGrpLeader->is_leader, ['Y', 'S'])) {
			return new \ParameterError('只允许组长进行表态');
		}
		/* 组长只能表态本组用户的数据，或者不属于任何分组的数据 */
		if ($oGrpLeader->is_leader === 'Y') {
			$oGrpMemb = $modelGrpUsr->byUser($oApp->entryRule->group, $oRecord->userid, ['fields' => 'round_id', 'onlyOne' => true]);
			if ($oGrpMemb && !empty($oGrpMemb->round_id)) {
				/* 填写记录的用户属于一个分组 */
				if ($oGrpMemb->round_id !== $oGrpLeader->round_id) {
					return new \ParameterError('只允许组长对本组成员的数据表态');
				}
			} else {
				if (empty($oUser->is_editor) || $oUser->is_editor !== 'Y') {
					return new \ParameterError('只允许编辑组的组长对不属于任何分组的成员的数据表态');
				}
			}
		}

		if (!in_array($value, ['Y', 'N', 'A', 'D'])) {
			$value = '';
		}
		$beforeValue = $oRecord->agreed;
		if ($beforeValue === $value) {
			return new \ParameterError('不能重复设置表态');
		}

		/* 检查推荐数量限制 */
		if ($value === 'Y') {
			if (!empty($oApp->actionRule->leader->record->agree->end)) {
				/* 当前轮次，当前组已经提交的记录数 */
				$oRule = $oApp->actionRule->leader->record->agree->end;
				if (!empty($oRule->max)) {
					$oCriteria = new \stdClass;
					$oCriteria->record = new \stdClass;
					$oCriteria->record->group_id = $oRecord->group_id;
					$oCriteria->record->agreed = 'Y';
					$oResult = $modelRec->byApp($oApp, ['fields' => 'id'], $oCriteria);
					if ((int) $oResult->total >= (int) $oRule->max) {
						$desc = empty($oRule->desc) ? ('每轮次每组最多允许推荐【' . $oRule->max . '条】记录（问题）') : $oRule->desc;
						if (!in_array(mb_substr($desc, -1), ['。', '，', '；', '.', ',', ';'])) {
							$desc .= '，';
						}
						$desc .= '已经推荐【' . $oResult->total . '条】。';
						return new \ResponseError($desc);
					}
				}
			}
		}
		/**
		 * 更新记录数据
		 */
		$oAgreedLog = $oRecord->agreed_log;
		if (isset($oAgreedLog->{$oUser->uid})) {
			$oLog = $oAgreedLog->{$oUser->uid};
			$oLog->time = time();
			$oLog->value = $value;
		} else {
			$oAgreedLog->{$oUser->uid} = (object) ['time' => time(), 'value' => $value];
		}
		$modelRec->update(
			'xxt_enroll_record',
			['agreed' => $value, 'agreed_log' => json_encode($oAgreedLog)],
			['enroll_key' => $ek]
		);
		/* 如果活动属于项目，更新项目内的推荐内容 */
		if (!empty($oApp->mission_id)) {
			$modelMisMat = $this->model('matter\mission\matter');
			$modelMisMat->agreed($oApp, 'R', $oRecord, $value);
		}

		/* 处理用户汇总数据，积分数据 */
		$this->model('matter\enroll\event')->agreeRecord($oApp, $oRecord, $oUser, $value);

		return new \ResponseData('ok');
	}
	/**
	 * 删除当前记录
	 *
	 * @param string $app
	 * @param string $ek
	 *
	 */
	public function remove_action($app, $ek) {
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$modelRec = $this->model('matter\enroll\record');
		$oRecord = $modelRec->byId($ek, ['fields' => 'id,userid,nickname,state,enroll_key,data,rid']);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ResponseError('记录已经被删除，不能再次删除');
		}
		$oUser = $this->getUser($oApp);

		// 判断删除人是否为提交人
		if ($oRecord->userid !== $oUser->uid) {
			return new \ResponseError('仅允许记录的提交者删除记录');
		}
		// 判断活动是否添加了轮次
		$modelRnd = $this->model('matter\enroll\round');
		$oActiveRnd = $modelRnd->getActive($oApp);
		$now = time();
		if (empty($oActiveRnd) || (!empty($oActiveRnd) && ($oActiveRnd->end_at != 0) && $oActiveRnd->end_at < $now) || ($oActiveRnd->rid !== $oRecord->rid)) {
			return new \ResponseError('记录所在活动轮次已结束，不能提交、修改、保存或删除！');
		}
		// 如果已经获得积分不允许删除
		$modelEnlUsr = $this->model('matter\enroll\user');
		$oEnlUsrRnd = $modelEnlUsr->byId($oApp, $oUser->uid, ['fields' => 'id,enroll_num,user_total_coin', 'rid' => $oRecord->rid]);
		if ($oEnlUsrRnd && $oEnlUsrRnd->user_total_coin > 0) {
			return new \ResponseError('提交的记录已经获得活动积分，不能删除');
		}

		// 删除数据
		$rst = $modelRec->removeByUser($oApp, $oRecord);

		/* 记录操作日志 */
		$oTarget = new \stdClass;
		$oTarget->id = $oRecord->id;
		$oTarget->type = 'record';
		$oEvent = new \stdClass;
		$oEvent->name = 'site.matter.enroll.remove';
		$oEvent->op = 'Del';
		$oEvent->at = time();
		$oEvent->user = $oUser;
		$log = $this->model('matter\enroll\event')->_logEvent($oApp, $oRecord->rid, $ek, $oTarget, $oEvent);

		return new \ResponseData($rst);
	}
	/**
	 * 返回指定记录项的活动记录
	 */
	public function list4Schema_action($site, $app, $rid = null, $schema, $page = 1, $size = 10) {
		// 记录数据过滤条件
		$oCriteria = $this->getPostJson();

		// 记录记录过滤条件
		$aOptions = [
			'page' => $page,
			'size' => $size,
		];
		if (!empty($rid)) {
			$aOptions['rid'] = $rid;
		}

		// 记录活动
		$modelApp = $this->model('matter\enroll');
		$enrollApp = $modelApp->byId($app);

		// 查询结果
		$mdoelRec = $this->model('matter\enroll\record');
		$result = $mdoelRec->list4Schema($enrollApp, $schema, $aOptions);

		return new \ResponseData($result);
	}
	/**
	 * 将填写记录转发到其他活动
	 */
	public function transmit_action($ek, $transmit) {
		$modelApp = $this->model('matter\enroll');
		$modelRec = $this->model('matter\enroll\record');

		$fields = 'id,aid,state';
		$oRecord = $modelRec->byId($ek, ['fields' => $fields]);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oApp = $modelApp->byId($oRecord->aid, ['cascaded' => 'N', 'fields' => 'siteid,state,mission_id,sync_mission_round,data_schemas,transmit_config']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oConfig = tms_array_search($oApp->transmitConfig, function ($oConfig) use ($transmit) {return $oConfig->id === $transmit;});
		if (empty($oConfig->app->id) || !isset($oConfig->mappings)) {
			return new \ResponseError('没有设置记录转发规则');
		}

		$oTargetApp = $modelApp->byId($oConfig->app->id, ['cascaded' => 'N', 'fields' => '*']);
		if (false === $oTargetApp || $oTargetApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$aResult = $this->model('matter\enroll\record\copy')->toApp($oApp, $oTargetApp, [$ek], $oConfig->mappings);
		if (false === $aResult[0]) {
			return new \ResponseError($aResult[1]);
		}
		if (count($aResult[1]) !== 1) {
			return new \ResponseError('记录转发错误');
		}

		$oNewRec = $aResult[1][0];

		return new \ResponseData($oNewRec);
	}
}