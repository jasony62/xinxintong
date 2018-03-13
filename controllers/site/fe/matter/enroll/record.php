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
	 * @param string $rid 指定在哪一个轮次上提交（仅限新建的情况）
	 * @param string $ek enrollKey 如果要更新之前已经提交的数据，需要指定
	 * @param string $submitkey 支持文件分段上传
	 */
	public function submit_action($site, $app, $rid = '', $ek = null, $submitkey = '', $subType = 'submit') {
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

		$modelEnl = $this->model('matter\enroll');
		$modelRec = $this->model('matter\enroll\record')->setOnlyWriteDbConn(true);

		if (false === ($oEnrollApp = $modelEnl->byId($app, ['cascaded' => 'N']))) {
			header('HTTP/1.0 500 parameter error:app dosen\'t exist.');
			die('登记活动不存在');
		}

		$bSubmitNewRecord = empty($ek); // 是否为提交新纪录

		if (!$bSubmitNewRecord) {
			$oBeforeRecord = $modelRec->byId($ek, ['state' => '1']);
			if (false === $oBeforeRecord) {
				return new \ObjectNotFoundError('指定的记录不存在');
			}
			$rid = $oBeforeRecord->rid;
		}

		// 提交轮次
		$aResult = $this->_getSubmitRecordRid($oEnrollApp, $rid);
		if (false === $aResult[0]) {
			return new \ResponseError($aResult[1]);
		}
		$rid = $aResult[1];

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
		// 提交数据的用户
		$oUser = $this->getUser($oEnrollApp, $oEnrolledData);

		/* 记录数据提交日志，跟踪提交特殊数据失败的问题 */
		$rawPosted = file_get_contents("php://input");
		$modelLog = $this->model('log');
		$modelLog->log('trace', 'enroll-submit-' . $oUser->uid, $modelLog->cleanEmoji($rawPosted, true));

		if ($subType === 'save') {
			if (empty($submitkey)) {
				$submitkey = empty($oUser) ? '' : $oUser->uid;
			}

			$schemasById = []; // 方便获取登记项定义
			foreach ($oEnrollApp->dataSchemas as $schema) {
				$schemasById[$schema->id] = $schema;
			}
			$dbData = $this->model('matter\enroll\data')->disposRecrdData($oEnrollApp, $schemasById, $oEnrolledData, $submitkey);
			if ($dbData[0] === false) {
				return new \ResponseError($dbData[1]);
			}
			$dbData = $dbData[1];

			$posted->data = $dbData;
			$data_tag = new \stdClass;
			if (isset($posted->tag) && count(get_object_vars($posted->tag))) {
				foreach ($posted->tag as $schId => $saveTags) {
					$data_tag->{$schId} = [];
					foreach ($saveTags as $saveTag) {
						$data_tag->{$schId}[] = $saveTag->id;
					}
				}
				unset($posted->tag);
			}
			$posted->data_tag = $data_tag;
			!empty($rid) && $posted->rid = $rid;
			/* 插入到用户对素材的行为日志中 */
			$operation = new \stdClass;
			$operation->name = 'saveData';
			$operation->data = $modelEnl->toJson($posted);
			$logid = $this->_logUserOp($oEnrollApp, $operation, $oUser);

			return new \ResponseData($logid);
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
						$requireCheckedData->{$oSchema->id} = $modelRec->getValueBySchema($oSchema, $oEnrolledData);
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
						$requireCheckedData->{$oSchema->id} = $modelRec->getValueBySchema($oSchema, $oEnrolledData);
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
		 * 提交登记数据
		 */
		$oUpdatedEnrollRec = [];
		if ($bSubmitNewRecord) {
			/* 插入登记数据 */
			$ek = $modelRec->enroll($oEnrollApp, $oUser, ['nickname' => $oUser->nickname, 'assignRid' => $rid]);
			/* 处理自定义信息 */
			$rst = $modelRec->setData($oUser, $oEnrollApp, $ek, $oEnrolledData, $submitkey, true);
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
		/* 处理用户汇总数据，积分数据 */
		$oRecord = $modelRec->byId($ek);
		$this->model('matter\enroll\event')->submitRecord($oEnrollApp, $oRecord, $oUser, $bSubmitNewRecord);

		/* 记录操作日志 */
		$oOperation = new \stdClass;
		$oOperation->name = $bSubmitNewRecord ? 'submit' : 'updateData';
		$oOperation->data = $modelRec->byId($ek, ['fields' => 'enroll_key,data,rid']);
		$this->_logUserOp($oEnrollApp, $oOperation, $oUser);

		/* 通知登记活动事件接收人 */
		if ($oEnrollApp->notify_submit === 'Y') {
			$this->_notifyReceivers($oEnrollApp, $ek);
		}

		return new \ResponseData($ek);
	}
	/**
	 * 返回当前轮次或者检查指定轮次是否有效
	 */
	private function _getSubmitRecordRid($oApp, $rid = '') {
		$modelRnd = $this->model('matter\enroll\round');
		$bRequireRound = false;
		if (empty($rid)) {
			if ($oApp->multi_rounds === 'Y') {
				$bRequireRound = true;
				$oRecordRnd = $modelRnd->getActive($oApp);
			}
		} else {
			$bRequireRound = true;
			$oRecordRnd = $modelRnd->byId($rid);
		}
		if ($bRequireRound) {
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

		return [true, ''];
	}
	/**
	 * 记录用户提交日志
	 *
	 * @param object $app
	 *
	 */
	private function _logUserOp($oApp, $operation, $user) {
		$modelLog = $this->model('matter\log');

		$logUser = new \stdClass;
		$logUser->userid = $user->uid;
		$logUser->nickname = $user->nickname;

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
							return [false, ['【' . $oSchema->title . '】中最多只能选择(' . $oSchema->range[1] . ')项，最少需要选择(' . $oSchema->range[0] . ')项']];
						}
					}
					break;
				}
			}
		}

		return [true];
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
	 * @param string $app
	 * @param string $submitKey
	 *
	 */
	public function uploadFile_action($app, $submitkey = '') {
		/* support CORS */
		//header('Access-Control-Allow-Origin:*');
		//header('Access-Control-Allow-Methods:POST');
		//if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
		//	exit;
		//}
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		if (empty($submitkey)) {
			$oUser = $this->getUser($oApp);
			$submitkey = $oUser->uid;
		}
		/** 分块上传文件 */
		if (defined('SAE_TMP_PATH')) {
			$dest = '/' . $app . '/' . $submitkey . '_' . $_POST['resumableFilename'];
			$resumable = \TMS_APP::M('fs/resumableAliOss', $oApp->siteid, $dest, 'xinxintong');
			$resumable->handleRequest($_POST);
		} else {
			$modelFs = \TMS_APP::M('fs/local', $oApp->siteid, '_resumable');
			$dest = $submitkey . '_' . $_POST['resumableIdentifier'];
			$resumable = \TMS_APP::M('fs/resumable', $oApp->siteid, $dest, $modelFs);
			$resumable->handleRequest($_POST);
		}

		return new \ResponseData('ok');
	}
	/**
	 * 用保存的数据填写指定的记录数据
	 */
	private function _fillWithSaved($oApp, $oUser, &$oRecord) {
		$oSaveLog = $this->model('matter\log')->lastByUser($oApp->id, 'enroll', $oUser->uid, ['byOp' => 'saveData']);
		if (count($oSaveLog) == 1) {
			$oSaveLog = $oSaveLog[0];
			$oSaveLog->opData = json_decode($oSaveLog->operate_data);
			$bMatched = true;
			if (!empty($oRecord->rid) && (isset($oSaveLog->opData->rid) && $oSaveLog->opData->rid !== $oRecord->rid)) {
				$bMatched = false;
			}
			if ($bMatched) {
				$oLogData = $oSaveLog->opData;
				if (isset($oLogData)) {
					$oRecord->data = $oLogData->data;
					$oRecord->supplement = $oLogData->supplement;
					$oRecord->data_tag = $oLogData->data_tag;

					return true;
				}
			}
		}

		return false;
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

		$fields = 'id,aid,state,rid,enroll_key,userid,group_id,nickname,verified,enroll_at,first_enroll_at,data,supplement,data_tag,score,like_num,like_log,remark_num';

		if (empty($ek)) {
			$oUser = $this->getUser($oApp);
			if ($loadLast === 'Y') {
				$oRecord = $modelRec->lastByUser($oApp, $oUser, ['assignRid' => $rid, 'verbose' => 'Y', 'fields' => $fields]);
				if (false === $oRecord || $oRecord->state !== '1') {
					$oRecord = new \stdClass;
				}
			} else {
				$oRecord = false;
			}
		} else {
			$oRecord = $modelRec->byId($ek, ['verbose' => 'Y', 'fields' => $fields]);
			$oUser = new \stdClass;
			if (false === $oRecord || $oRecord->state !== '1') {
				$oRecord = new \stdClass;
			} else {
				if (!empty($oRecord->userid)) {
					$oUser->uid = $oRecord->userid;
				}
			}
		}

		/* 返回当前用户在关联活动中填写的数据 */
		if (!empty($oApp->enroll_app_id) && !empty($oUser->uid)) {
			$oAssocApp = $this->model('matter\enroll')->byId($oApp->enroll_app_id, ['cascaded' => 'N']);
			if ($oAssocApp) {
				$oAssocRec = $modelRec->byUser($oAssocApp, $oUser);
				if (count($oAssocRec) === 1) {
					if (!empty($oAssocRec[0]->data)) {
						$oAssocRecord = $oAssocRec[0]->data;
						if (!isset($oRecord->data)) {
							$oRecord->data = new \stdClass;
						}
						foreach ($oAssocRecord as $key => $value) {
							$oRecord->data->{$key} = $value;
						}
					}
				}
			}
		}
		if (!empty($oApp->group_app_id) && !empty($oUser->uid)) {
			$oGrpApp = $this->model('matter\group')->byId($oApp->group_app_id, ['cascaded' => 'N']);
			$oGrpPlayer = $this->model('matter\group\player')->byUser($oGrpApp, $oUser->uid);
			if (count($oGrpPlayer) === 1) {
				if (!empty($oGrpPlayer[0]->data)) {
					if (!isset($oRecord->data)) {
						$oRecord->data = new \stdClass;
					}
					if (is_string($oGrpPlayer[0]->data)) {
						$oAssocRecord = json_decode($oGrpPlayer[0]->data);
					} else {
						$oAssocRecord = $oGrpPlayer[0]->data;
					}

					$oAssocRecord->_round_id = $oGrpPlayer[0]->round_id;
					foreach ($oAssocRecord as $k => $v) {
						$oRecord->data->{$k} = $v;
					}
				}
			}
		}

		/* 恢复用户保存的数据 */
		if ($withSaved === 'Y') {
			$this->_fillWithSaved($oApp, $oUser, $oRecord);
		}

		if (!empty($oRecord->rid)) {
			$oRecRound = $this->model('matter\enroll\round')->byId($oRecord->rid);
			$oRecord->round = $oRecRound;
		}

		return new \ResponseData($oRecord);
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
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->getUser($oApp);
		// 登记数据过滤条件
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

		$options = [];
		$options['page'] = $page;
		$options['size'] = $size;
		$options['orderby'] = $orderby;

		$modelRec = $this->model('matter\enroll\record');

		$oResult = $modelRec->byApp($oApp, $options, $oCriteria);

		return new \ResponseData($oResult);
	}
	/**
	 * 点赞登记记录中的某一个题
	 *
	 * @param string $ek
	 *
	 */
	public function like_action($ek) {
		$modelRec = $this->model('matter\enroll\record');
		$oRecord = $modelRec->byId($ek, ['fields' => 'id,enroll_key,state,aid,rid,userid,like_log,like_num']);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oApp = $this->model('matter\enroll')->byId($oRecord->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->getUser($oApp);

		$oLikeLog = $oRecord->like_log;
		if (isset($oLikeLog->{$oUser->uid})) {
			unset($oLikeLog->{$oUser->uid});
			$incLikeNum = -1;
		} else {
			$oLikeLog->{$oUser->uid} = time();
			$incLikeNum = 1;
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
			/* 被点赞 */
			$modelEnlEvt->belikedRecord($oApp, $oRecord, $oUser);
		} else {
			/* 撤销发起点赞 */
			$modelEnlEvt->undoLikeRecord($oApp, $oRecord, $oUser);
			/* 撤销被点赞 */
			$modelEnlEvt->undoBeLikedRecord($oApp, $oRecord, $oUser);
		}

		$oResult = new \stdClass;
		$oResult->like_log = $oLikeLog;
		$oResult->like_num = $likeNum;

		return new \ResponseData($oResult);
	}
	/**
	 * 推荐登记记录中的某一个题
	 * 只有组长才有权限做
	 *
	 * @param string $ek
	 * @param string $value
	 *
	 */
	public function recommend_action($ek, $value = '') {
		$modelRec = $this->model('matter\enroll\record');
		$oRecord = $modelRec->byId($ek, ['fields' => 'id,state,aid,rid,enroll_key,userid,agreed,agreed_log']);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oApp = $this->model('matter\enroll')->byId($oRecord->aid, ['cascaded' => 'N', 'fields' => 'id,siteid,mission_id,state,entry_rule']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		if (empty($oApp->entryRule->group->id)) {
			return new \ParameterError('只有进入条件为分组活动的登记活动才允许组长推荐');
		}
		$oUser = $this->getUser($oApp);

		$modelGrpUsr = $this->model('matter\group\player');
		/* 当前用户所属分组及角色 */
		$oGrpLeader = $modelGrpUsr->byUser($oApp->entryRule->group, $oUser->uid, ['fields' => 'is_leader,round_id', 'onlyOne' => true]);
		if (false === $oGrpLeader || !in_array($oGrpLeader->is_leader, ['Y', 'S'])) {
			return new \ParameterError('只允许组长进行推荐');
		}
		/* 填写记录用户所属分组 */
		if ($oGrpLeader->is_leader === 'Y') {
			$oGrpMemb = $modelGrpUsr->byUser($oApp->entryRule->group, $oRecord->userid, ['fields' => 'round_id', 'onlyOne' => true]);
			if (false === $oGrpMemb || $oGrpMemb->round_id !== $oGrpLeader->round_id) {
				return new \ParameterError('只允许组长推荐本组数据');
			}
		}

		if (!in_array($value, ['Y', 'N', 'A'])) {
			$value = '';
		}
		$beforeValue = $oRecord->agreed;
		if ($beforeValue === $value) {
			return new \ParameterError('不能重复设置推荐状态');
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
		$this->model('matter\enroll\event')->recommendRecord($oApp, $oRecord, $oUser, $value);

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
		$app = $this->escape($app);
		$ek = $this->escape($ek);
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$modelRec = $this->model('matter\enroll\record');
		$oRecord = $modelRec->byId($ek, ['fields' => 'userid,nickname,state,enroll_key,data,rid']);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ResponseError('记录已经被删除，不能再次删除');
		}
		$oUser = $this->getUser($oApp);

		// 判断删除人是否为提交人
		if ($oRecord->userid !== $oUser->uid) {
			return new \ResponseError('仅允许记录的提交者删除记录');
		}
		// 判断活动是否添加了轮次
		if ($oApp->multi_rounds == 'Y') {
			$modelRnd = $this->model('matter\enroll\round');
			$oActiveRnd = $modelRnd->getActive($oApp);
			$now = time();
			if (empty($oActiveRnd) || (!empty($oActiveRnd) && ($oActiveRnd->end_at != 0) && $oActiveRnd->end_at < $now) || ($oActiveRnd->rid !== $oRecord->rid)) {
				return new \ResponseError('记录所在活动轮次已结束，不能提交、修改、保存或删除！');
			}
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
		$oUser->nickname = $oRecord->nickname;
		$oOperation = new \stdClass;
		$oOperation->name = 'removeData';
		unset($oRecord->userid);
		unset($oRecord->nickname);
		unset($oRecord->state);
		$oOperation->data = $oRecord;

		$this->_logUserOp($oApp, $oOperation, $oUser);

		return new \ResponseData($rst);
	}
}