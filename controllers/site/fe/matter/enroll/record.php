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

		// 应用的定义
		$modelEnl = $this->model('matter\enroll');
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

		// 当前访问用户的基本信息
		$userNickname = $modelEnl->getUserNickname($oEnrollApp, $oUser);
		$oUser->nickname = $userNickname;

		/* 记录数据提交日志，跟踪提交特殊数据失败的问题 */
		$rawPosted = file_get_contents("php://input");
		$this->model('log')->log('trace', 'enroll-submit-' . $oUser->uid, $rawPosted);

		// 提交的数据
		$posted = $this->getPostJson();
		if (isset($posted->data)) {
			$enrolledData = $posted->data;
		} else {
			$enrolledData = $posted;
		}

		// 检查是否允许登记
		$rst = $this->_canSubmit($oEnrollApp, $oUser, $enrolledData, $ek);
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
			foreach ($dataSchemas as $dataSchema) {
				if (isset($dataSchema->requireCheck) && $dataSchema->requireCheck === 'Y') {
					if (isset($dataSchema->fromApp) && $dataSchema->fromApp === $oEnrollApp->enroll_app_id) {
						$requireCheckedData->{$dataSchema->id} = isset($enrolledData->{$dataSchema->id}) ? $enrolledData->{$dataSchema->id} : '';
					}
				}
			}
			/* 在指定的登记活动中检查数据 */
			$modelMatchRec = $this->model('matter\enroll\record');
			$matchedRecords = $modelMatchRec->byData($oMatchApp, $requireCheckedData);
			if (empty($matchedRecords)) {
				return new \ParameterError('未在指定的登记活动［' . $oMatchApp->title . '］中找到与提交数据相匹配的记录');
			}
			$matchedRecord = $matchedRecords[0];
			if ($matchedRecord->verified !== 'Y') {
				return new \ParameterError('在指定的登记活动［' . $oMatchApp->title . '］中与提交数据匹配的记录未通过验证');
			}
			/* 将匹配的登记记录数据作为提交的登记数据的一部分 */
			$matchedData = $matchedRecords[0]->data;
			foreach ($oMatchApp->dataSchemas as $oSchema) {
				!isset($enrolledData->{$oSchema->id}) && $enrolledData->{$oSchema->id} = $matchedData->{$oSchema->id};
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
			foreach ($dataSchemas as $dataSchema) {
				if (isset($dataSchema->requireCheck) && $dataSchema->requireCheck === 'Y') {
					if (isset($dataSchema->fromApp) && $dataSchema->fromApp === $oEnrollApp->group_app_id) {
						$requireCheckedData->{$dataSchema->id} = isset($enrolledData->{$dataSchema->id}) ? $enrolledData->{$dataSchema->id} : '';
					}
				}
			}
			/* 在指定的登记活动中检查数据 */
			$modelMatchRec = $this->model('matter\group\player');
			$groupRecords = $modelMatchRec->byData($oGroupApp, $requireCheckedData);
			if (empty($groupRecords)) {
				return new \ParameterError('未在指定的分组活动［' . $oGroupApp->title . '］中找到与提交数据相匹配的记录');
			}
			$groupRecord = $groupRecords[0];
			/* 将匹配的登记记录数据作为提交的登记数据的一部分 */
			$matchedData = $groupRecord->data;
			foreach ($oGroupApp->dataSchemas as $oSchema) {
				if (!isset($enrolledData->{$oSchema->id}) && isset($matchedData->{$oSchema->id})) {
					$enrolledData->{$oSchema->id} = $matchedData->{$oSchema->id};
				}
			}
			/* 所属分组id */
			if (isset($groupRecord->round_id)) {
				$oUser->group_id = $enrolledData->_round_id = $groupRecord->round_id;
			}
		}
		/**
		 * 提交用户身份信息
		 */
		if (isset($enrolledData->member) && isset($enrolledData->member->schema_id)) {
			$member = clone $enrolledData->member;
			$rst = $this->_submitMember($site, $member, $oUser);
			if ($rst[0] === false) {
				return new \ParameterError($rst[1]);
			}
		}
		/**
		 * 提交登记数据
		 */
		$oUpdatedEnrollRec = [];
		$modelRec = $this->model('matter\enroll\record');
		$modelRec->setOnlyWriteDbConn(true);
		if ($bSubmitNewRecord) {
			/* 插入登记数据 */
			$ek = $modelRec->enroll($oEnrollApp, $oUser, ['nickname' => $oUser->nickname]);
			/* 处理自定义信息 */
			$rst = $modelRec->setData($oUser, $oEnrollApp, $ek, $enrolledData, $submitkey, true);
			/* 登记提交的积分奖励 */
			$modelMat = $this->model('matter\enroll\coin');
			$modelMat->setOnlyWriteDbConn(true);
			$rules = $modelMat->rulesByMatter('site.matter.enroll.submit', $oEnrollApp);
			$modelCoin = $this->model('site\coin\log');
			$modelCoin->setOnlyWriteDbConn(true);
			$modelCoin->award($oEnrollApp, $oUser, 'site.matter.enroll.submit', $rules);
		} else {
			/* 重新插入新提交的数据 */
			$rst = $modelRec->setData($oUser, $oEnrollApp, $ek, $enrolledData, $submitkey);
			if ($rst[0] === true) {
				/* 已经登记，更新原先提交的数据，只要进行更新操作就设置为未审核通过的状态 */
				$oUpdatedEnrollRec['enroll_at'] = time();
				$oUpdatedEnrollRec['userid'] = $oUser->uid;
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
		if ($activeRound = $modelRun->getActive($oEnrollApp)) {
			$rid = $activeRound->rid;
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
			$upData = ['last_enroll_at' => time()];
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
			$upDataALL = ['last_enroll_at' => time()];
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
			$users = $modelUsr->query_objs_ss([
				'id,score',
				'xxt_enroll_user',
				"siteid='$oEnrollApp->siteid' and aid='$oEnrollApp->id' and userid='$oUser->uid' and rid !='ALL'",
			]);
			$total = 0;
			foreach ($users as $v) {
				if (!empty($v->score)) {
					$total += (float) $v->score;
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
	 *
	 */
	private function _canSubmit(&$oApp, &$oUser, &$posted, $ek) {
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
		foreach ($oApp->dataSchemas as $schema) {
			if (isset($schema->unique) && $schema->unique === 'Y') {
				if (empty($posted->{$schema->id})) {
					return [false, ['唯一项【' . $schema->title . '】不允许为空']];
				}
				$checked = new \stdClass;
				$checked->{$schema->id} = $posted->{$schema->id};
				$existings = $modelRec->byData($oApp, $checked, ['fields' => 'enroll_key']);
				if (count($existings)) {
					foreach ($existings as $existing) {
						if ($existing->enroll_key !== $ek) {
							return [false, ['唯一项【' . $schema->title . '】不允许重复，请检查填写的数据']];
						}
					}
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
		$oMschema = $this->model('site\user\memberschema')->byId($schemaId, 'siteid,id,title,auto_verified,attr_mobile,attr_email,attr_name,extattr');
		$modelMem = $this->model('site\user\member');

		$existentMember = $modelMem->byUser($user->uid, ['schemas' => $schemaId]);
		if (count($existentMember)) {
			$memberId = $existentMember[0]->id;
			$member->id = $memberId;
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
	 *
	 *
	 * 列出填写人名单列表
	 *
	 *
	 */
	public function actorList_action($site, $app, $orderby='id', $page=1, $size=30) {
		$modelEnl=$this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		//参与者列表
		$modelRnd=$this->model('matter\enroll\round');
		$rnd=$modelRnd->getActive($oApp);
		$rid=!empty($rnd) ? $rnd->rid : '';

		$q1=[
			'*',
			'xxt_enroll_user',
			['siteid'=>$site, 'aid'=>$app, 'rid'=>$rid]
		];
		$q2['o']="$orderby desc";
		$q2['r']=['o' => ($page - 1) * $size, 'l' => $size];
		if($users=$modelEnl->query_objs_ss($q1,$q2)){
			foreach ($users as &$user) {
				//公众号的信息
				$sns=$modelEnl->query_obj_ss([
					'assoc_id,wx_openid,yx_openid,qy_openid,uname,headimgurl,ufrom,uid,unionid',
					'xxt_site_account',
					['siteid'=>$site,'uid'=>$user->userid]
				]);
				$user->sns=$sns;
				//通信录的信息
				$addressbook=$modelEnl->query_objs_ss([
					'*',
					'xxt_site_member',
					['siteid'=>$site,'userid'=>$user->userid]
				]);
				foreach ($addressbook as &$v) {
					if(isset($v->schema_id)){
						$v->schema_name=$modelEnl->query_val_ss(['title','xxt_site_member_schema',['id'=>$v->schema_id]]);
					}
					if($v->extattr){
						$v->extattr=json_decode($v->extattr);
					}
				}
				$user->addressbook=$addressbook;
			}
		}

		$result=new \stdClass;
		$result->records=$users;
		$q1[0]='count(*)';
		$result->total=$modelEnl->query_val_ss($q1);

		return new \ResponseData($result);	
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
				$rules = $modelMat->rulesByMatter('site.matter.enroll.data.like', $oApp);
				$modelCoin->award($oApp, $user, 'site.matter.enroll.data.like', $rules);
			}

			$upData2 = ['last_like_at' => time(), 'like_num' => $oEnrollUsr->like_num + $incLikeNum];
			if (!empty($rules)) {
				$upData2['user_total_coin'] = (int) $oEnrollUsr->user_total_coin;
				foreach ($rules as $rule) {
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
			if ($incLikeNum > 0) {
				/* 更新被点赞的活动用户的积分奖励 */
				$rules = $modelMat->rulesByMatter('site.matter.enroll.data.like', $oApp);
			}

			$upDataALL2 = ['last_like_at' => time(), 'like_num' => $oEnrollUsrALL->like_num + $incLikeNum];
			if (!empty($rules)) {
				$upDataALL2['user_total_coin'] = (int) $oEnrollUsrALL->user_total_coin;
				foreach ($rules as $rule) {
					$upDataALL2['user_total_coin'] = $upDataALL2['user_total_coin'] + (int) $rule->actor_delta;
				}
			}
			$modelUsr->update(
				'xxt_enroll_user',
				$upDataALL2,
				['id' => $oEnrollUsrALL->id]
			);
		}

		return new \ResponseData(['like_log' => $oLikeLog, 'like_num' => $likeNum]);
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