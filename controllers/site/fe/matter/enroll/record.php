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

		// 应用的定义
		$modelEnl = $this->model('matter\enroll');
		if (false === ($enrollApp = $modelEnl->byId($app, ['cascaded' => 'N']))) {
			header('HTTP/1.0 500 parameter error:app dosen\'t exist.');
			die('登记活动不存在');
		}

		// 当前访问用户的基本信息
		$user = $this->who;
		// 提交的数据
		$enrolledData = $this->getPostJson();
		// 检查是否允许登记
		$rst = $this->_canEnroll($site, $enrollApp, $user, $enrolledData, $ek);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}
		/**
		 * 检查是否存在匹配的登记记录
		 */
		if (!empty($enrollApp->enroll_app_id)) {
			$matchApp = $modelEnl->byId($enrollApp->enroll_app_id);
			if (empty($matchApp)) {
				return new \ParameterError('指定的登记匹配登记活动不存在');
			}
			/* 获得要检查的登记项 */
			$requireCheckedData = new \stdClass;
			$dataSchemas = json_decode($enrollApp->data_schemas);
			foreach ($dataSchemas as $dataSchema) {
				if (isset($dataSchema->requireCheck) && $dataSchema->requireCheck === 'Y') {
					if (isset($dataSchema->fromApp) && $dataSchema->fromApp === $enrollApp->enroll_app_id) {
						$requireCheckedData->{$dataSchema->id} = isset($enrolledData->{$dataSchema->id}) ? $enrolledData->{$dataSchema->id} : '';
					}
				}
			}
			/* 在指定的登记活动中检查数据 */
			$modelMatchRec = $this->model('matter\enroll\record');
			$matchedRecords = $modelMatchRec->byData($site, $matchApp, $requireCheckedData);
			if (empty($matchedRecords)) {
				return new \ParameterError('未在指定的登记活动［' . $matchApp->title . '］中找到与提交数据相匹配的记录');
			}
			$matchedRecord = $matchedRecords[0];
			if ($matchedRecord->verified !== 'Y') {
				return new \ParameterError('在指定的登记活动［' . $matchApp->title . '］中与提交数据匹配的记录未通过验证');
			}
			/* 将匹配的登记记录数据作为提交的登记数据的一部分 */
			$matchedData = $matchedRecords[0]->data;
			foreach ($matchedData as $n => $v) {
				!isset($enrolledData->{$n}) && $enrolledData->{$n} = $v;
			}
		}
		/**
		 * 检查是否存在匹配的分组记录
		 */
		if (!empty($enrollApp->group_app_id)) {
			$groupApp = $this->model('matter\group')->byId($enrollApp->group_app_id);
			if (empty($groupApp)) {
				return new \ParameterError('指定的登记匹配分组活动不存在');
			}
			/* 获得要检查的登记项 */
			$requireCheckedData = new \stdClass;
			$dataSchemas = json_decode($enrollApp->data_schemas);
			foreach ($dataSchemas as $dataSchema) {
				if (isset($dataSchema->requireCheck) && $dataSchema->requireCheck === 'Y') {
					if (isset($dataSchema->fromApp) && $dataSchema->fromApp === $enrollApp->group_app_id) {
						$requireCheckedData->{$dataSchema->id} = isset($enrolledData->{$dataSchema->id}) ? $enrolledData->{$dataSchema->id} : '';
					}
				}
			}
			/* 在指定的登记活动中检查数据 */
			$modelMatchRec = $this->model('matter\group\player');
			$groupRecords = $modelMatchRec->byData($site, $groupApp, $requireCheckedData);
			if (empty($groupRecords)) {
				return new \ParameterError('未在指定的分组活动［' . $groupApp->title . '］中找到与提交数据相匹配的记录');
			}
			$groupRecord = $groupRecords[0];
			/* 将匹配的登记记录数据作为提交的登记数据的一部分 */
			$matchedData = $groupRecord->data;
			foreach ($matchedData as $n => $v) {
				!isset($enrolledData->{$n}) && $enrolledData->{$n} = $v;
			}
			if (isset($groupRecord->round_id)) {
				$enrolledData->_round_id = $groupRecord->round_id;
			}
		}
		/**
		 * 提交用户身份信息
		 */
		if (isset($enrolledData->member) && isset($enrolledData->member->schema_id)) {
			$member = clone $enrolledData->member;
			$rst = $this->_submitMember($site, $member, $user);
			if ($rst[0] === false) {
				return new \ParameterError($rst[1]);
			}
		}
		/**
		 * 提交登记数据
		 */
		$updatedEnrollRec = [];
		$modelRec = $this->model('matter\enroll\record');
		if (empty($ek)) {
			/* 插入登记数据 */
			$ek = $modelRec->enroll($site, $enrollApp, $user);
			/* 处理自定义信息 */
			$rst = $modelRec->setData($user, $enrollApp, $ek, $enrolledData, $submitkey);
			/* 登记提交的积分奖励 */
			$modelCoin = $this->model('site\coin\log');
			$modelCoin->award($enrollApp, $user, 'site.matter.enroll.submit');
		} else {
			/* 重新插入新提交的数据 */
			$rst = $modelRec->setData($user, $enrollApp, $ek, $enrolledData, $submitkey);
			if ($rst[0] === true) {
				/* 已经登记，更新原先提交的数据，只要进行更新操作就设置为未审核通过的状态 */
				$updatedEnrollRec['enroll_at'] = time();
				$updatedEnrollRec['verified'] = 'N';
			}
		}
		if (false === $rst[0]) {
			return new \ResponseError($rst[1]);
		}
		if (isset($matchedRecord)) {
			$updatedEnrollRec['matched_enroll_key'] = $matchedRecord->enroll_key;
		}
		if (isset($groupRecord)) {
			$updatedEnrollRec['group_enroll_key'] = $groupRecord->enroll_key;
		}
		if (count($updatedEnrollRec)) {
			$modelRec->update(
				'xxt_enroll_record',
				$updatedEnrollRec,
				"enroll_key='$ek'"
			);
		}
		/* 记录操作日志 */
		$this->_logSubmit($site, $enrollApp, $ek);
		/**
		 * 通知登记活动事件接收人
		 */
		if ($enrollApp->notify_submit === 'Y') {
			$this->_notifyReceivers($site, $enrollApp, $ek);
		}

		return new \ResponseData($ek);
	}
	/**
	 * 记录用户提交日志
	 *
	 * @param string $siteId
	 * @param object $app
	 *
	 */
	private function _logSubmit($siteId, $app, $ek) {
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

		$logid = $modelLog->addUserMatterOp($siteId, $logUser, $app, $operation, $client, $referer);

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
	private function _canEnroll($siteId, &$app, &$user, &$posted, $ek) {
		$modelRec = $this->model('matter\enroll\record');
		/**
		 * 检查登记数量
		 */
		if (empty($ek) && $app->count_limit > 0) {
			$records = $modelRec->byUser($app->id, $user);
			if (count($records) >= $app->count_limit) {
				return [false, ['已经进行过' . count($records) . '次登记，不允再次登记']];
			}
		}
		/**
		 * 检查提交数据的合法性
		 */
		$schemas = json_decode($app->data_schemas);
		foreach ($schemas as $schema) {
			if (isset($schema->unique) && $schema->unique === 'Y') {
				if (empty($posted->{$schema->id})) {
					return [false, ['唯一项【' . $schema->title . '】不允许为空']];
				}
				$checked = new \stdClass;
				$checked->{$schema->id} = $posted->{$schema->id};
				$existings = $modelRec->byData($siteId, $app, $checked, ['fields' => 'userid']);
				if (count($existings)) {
					foreach ($existings as $existing) {
						if ($existing->userid !== $user->uid) {
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
		$schema = $this->model('site\user\memberschema')->byId($schemaId, 'attr_mobile,attr_email,attr_name,extattr');
		$modelMem = $this->model('site\user\member');

		$existentMember = $modelMem->byUser($user->uid, ['schemas' => $schemaId]);
		if (count($existentMember)) {
			$memberId = $existentMember[0]->id;
			$member->id = $memberId;
			$rst = $modelMem->modify($siteId, $schema, $memberId, $member);
		} else {
			$rst = $modelMem->createByApp($siteId, $schema, $user->uid, $member);
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
	 * @param string $siteId
	 * @param object $app
	 * @param string $ek
	 *
	 */
	private function _notifyReceivers($siteId, &$app, $ek) {
		$receivers = $this->model('matter\enroll\receiver')->byApp($siteId, $app->id);
		if (count($receivers) === 0) {
			return [false];
		}
		/* 获得活动的管理员链接 */
		$appURL = 'http://' . $_SERVER['HTTP_HOST'];
		$appURL .= "/rest/site/op/matter/enroll?site={$siteId}&app={$app->id}";
		$modelQurl = $this->model('q\url');
		$user = new \stdClass;
		$task = $modelQurl->byUrl($user, $siteId, $appURL);
		if (false === $task) {
			return [false];
		}
		$noticeURL = 'http://' . $_SERVER['HTTP_HOST'];
		$noticeURL .= "/q/{$task->code}";
		$yxProxy = $wxProxy = null;

		foreach ($receivers as $receiver) {
			if (empty($receiver->sns_user)) {
				continue;
			}
			$snsUser = json_decode($receiver->sns_user);

			if ($snsUser->src === 'yx' && isset($snsUser->openid)) {
				if ($yxProxy === null) {
					$yxConfig = $this->model('sns\yx')->bySite($siteId);
					if ($yxConfig->joined === 'Y' && $yxConfig->can_p2p === 'Y') {
						$yxProxy = $this->model('sns\yx\proxy', $yxConfig);
					} else {
						$yxProxy = false;
					}
					$msg = '【' . $app->title . "】有新信息，请处理";
					$message = [
						'msgtype' => 'news',
						'news' => [
							'articles' => [
								[
									'title' => $app->title,
									'description' => $msg,
									'url' => $noticeURL,
									'picurl' => $app->pic,
								],
							],
						],
					];
				}
				if ($yxProxy !== false && isset($message)) {
					$rst = $yxProxy->messageSend($message, array($snsUser->openid));
				}
			}
			/* 微信号要通过模板消息发 */
			if ($snsUser->src === 'wx' && isset($snsUser->openid)) {
				if ($wxProxy === null) {
					$wxSiteId = $receiver->siteid;
					$modelWx = $this->model('sns\wx');
					$wxConfig = $modelWx->bySite($wxSiteId);
					if ($wxConfig->joined === 'Y') {
						$wxProxy = $this->model('sns\wx\proxy', $wxConfig);
					} else {
						$wxProxy = false;
					}
					/* 模版消息定义 */
					$notice = $this->model('site\notice')->byName($wxSiteId, 'site.enroll.submit');
					if ($notice) {
						$tmplConfig = $this->model('matter\tmplmsg\config')->byId($notice->tmplmsg_config_id, ['cascaded' => 'Y']);
						/* 拼装模版消息 */
						$data = [];
						if (isset($tmplConfig->tmplmsg)) {
							foreach ($tmplConfig->tmplmsg->params as $param) {
								$mapping = $tmplConfig->mapping->{$param->pname};
								if ($mapping->src === 'matter') {
									if (isset($app->{$mapping->id})) {
										$value = $app->{$mapping->id};
									}
								} else if ($mapping->src === 'text') {
									$value = $mapping->name;
								}
								!isset($value) && $value = '';
								$data[$param->pname] = [
									'value' => $value,
									'color' => '#173177',
								];
							}
							$message = [
								'template_id' => $tmplConfig->tmplmsg->templateid,
								'data' => &$data,
								'url' => $noticeURL,
							];
						}
					}
				}
				/* 发送模版消息 */
				if ($wxProxy !== false && isset($message)) {
					$message['touser'] = $snsUser->openid;
					$rst = $wxProxy->messageTemplateSend($message);
					if ($rst[0] === false) {
						return $rst;
					}
					$msgid = $rst[1]->msgid;
					$model = $this->model();
					/*记录日志*/
					$log = [
						'siteid' => $wxSiteId,
						'mpid' => $wxSiteId,
						'openid' => $snsUser->openid,
						'tmplmsg_id' => $tmplConfig->tmplmsg->id,
						'template_id' => $message['template_id'],
						'data' => $model->escape(json_encode($message)),
						'create_at' => time(),
						'msgid' => $msgid,
					];
					$model->insert('xxt_log_tmplmsg', $log, false);
				}
			}
			/* 企业号直接发文本 */
			if (isset($snsUser->qy_openid)) {
				$qyConfig = $this->model('sns\qy')->bySite($siteId);
				if ($qyConfig->joined === 'Y') {
					$qyProxy = $this->model('sns\qy\proxy', $qyConfig);
					$msg = '【' . $app->title . "】有新登记，<a href='" . $noticeURL . "' >请处理</a> ";
					$message = [
						'touser' => $snsUser->qy_openid,
						'msgtype' => 'text',
						"text" => [
							"content" => $msg,
						],
					];
					if ($qyProxy !== false && isset($message)) {
						$rst = $qyProxy->messageSend($message, $snsUser->qy_openid);
					}
				}
			}
		}

		return array(true);
	}
	/**
	 * 分段上传文件
	 *
	 * @param string $site
	 * @param string $app
	 * @param string $submitKey
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
		if (false === ($app = $model->byId($app))) {
			return new \ParameterError("指定的活动（$app）不存在");
		}
		/**
		 * 当前访问用户的基本信息
		 */
		$user = $this->who;
		/* 如果已经有登记记录则不登记 */
		$modelRec = $this->model('matter\enroll\record');
		if ($once === 'Y') {
			$ek = $modelRec->getLastKey($site, $app, $user);
		}
		/* 创建登记记录*/
		if (empty($ek)) {
			$options = [
				'enrollAt' => time(),
				'referrer' => (empty($posted->referrer) ? '' : $posted->referrer),
			];
			$ek = $modelRec->enroll($site, $app, $user, $options);
			/**
			 * 处理提交数据
			 */
			$data = $_GET;
			unset($data['site']);
			unset($data['app']);
			if (!empty($data)) {
				$data = (object) $data;
				$rst = $modelRec->setData($user, $app, $ek, $data);
				if (false === $rst[0]) {
					return new ResponseError($rst[1]);
				}
			}
		}
		/*登记记录的URL*/
		$url = '/rest/site/fe/matter/enroll';
		$url .= '?site=' . $site;
		$url .= '&app=' . $app->id;
		$url .= '&ek=' . $ek;

		$rsp = new \stdClass;
		$rsp->url = $url;
		$rsp->ek = $ek;

		return new \ResponseData($rsp);
	}
	/**
	 * 发出邀请
	 *
	 * @param string $site
	 * @param string $app
	 * @param string $invitee
	 * @param string $page
	 */
	public function inviteSend_action($site, $app, $ek, $invitee, $page = '') {
		/*获得被邀请人的信息*/
		$options = array('fields' => 'openid');
		$members = $this->model('user/member')->search($site, $invitee, $options);
		if (empty($members)) {
			return new \ResponseError("指定的用户不存在");
		}
		$openid = $members[0]->openid;

		/*给邀请人发消息*/
		$message = \TMS_APP::M('matter\enroll')->forCustomPush($site, $app);
		$url = $message['news']['articles'][0]['url'];
		$url .= "&ek=$ek";
		!empty($page) && $url .= "&page=$page";
		$message['news']['articles'][0]['url'] = $url;
		$rst = $this->sendByOpenid($site, $openid, $message);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}

		return new \ResponseData($members);
	}
	/**
	 * 记录参加登记活动的用户之间的邀请关系
	 * 邀请必须依赖于某条已经存在的登记记录
	 *
	 * $param inviter enroll_key
	 */
	public function acceptInvite_action($site, $app, $inviter, $state = '1') {
		$model = $this->model('app\enroll');
		if (false === ($app = $model->byId($app))) {
			return new \ParameterError("指定的活动（$app）不存在");
		}
		/* 当前访问用户的基本信息 */
		$user = $this->getUser($site,
			array(
				'authapis' => $app->authapis,
				'matter' => $app,
				'verbose' => array('member' => 'Y', 'fan' => 'Y'),
			)
		);
		/* 如果已经有登记记录则不登记 */
		$modelRec = $this->model('app\enroll\record');
		if ($state === '1') {
			$ek = $modelRec->getLastKey($site, $app, $user);
			if (!empty($ek)) {
				$rsp = new \stdClass;
				$rsp->ek = $ek;
				return new \ResponseData($rsp);
			}
		} else {
			$ek = $modelRec->hasAcceptedInvite($app, $user->openid, $inviter);
		}
		if (false === $ek) {
			/* 创建登记记录*/
			$ek = $modelRec->add($site, $app, $user, 'ek:' . $inviter);
			if ($state !== '1') {
				/*不作为独立的记录，只是接收邀请的日志*/
				$modelRec->modify($ek, array('state' => 2));
			}
			/** 处理提交数据 */
			$data = $_GET;
			unset($data['site']);
			unset($data['app']);
			if (!empty($data)) {
				$data = (object) $data;
				$rst = $modelRec->setData($user, $app, $ek, $data);
				if (false === $rst[0]) {
					return new ResponseError($rst[1]);
				}
			}
			/*记录邀请数*/
			$modelRec->update("update xxt_enroll_record set follower_num=follower_num+1 where enroll_key='$inviter'");
			/*邀请成功的积分奖励*/
			$inviteRecord = $modelRec->byId($inviter, array('cascaded' => 'N', 'fields' => 'openid'));
			$modelCoin = $this->model('coin\log');
			$action = 'app.enroll,' . $app . '.invite.success';
			$modelCoin->income($site, $action, $app, 'sys', $inviteRecord->openid);
		}
		$rsp = new \stdClass;
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

		$app = $modelApp->byId($app, ['cascaded' => 'N']);
		/*当前访问用户的基本信息*/
		$user = $this->who;
		/**登记数据*/
		if (empty($openedek)) {
			// 获得最后一条登记数据。登记记录有可能未进行过登记
			$record = $modelRec->getLast($app, $user, ['fields' => '*']);
			if ($record) {
				$openedek = $record->enroll_key;
			}
		} else {
			// 打开指定的登记记录
			$record = $modelRec->byId($openedek);
		}

		/** 互动数据？？？ */
		if (!empty($openedek)) {
			/*登记人信息*/
			$record->enroller = $user;
			/*获得关联抽奖活动记录*/
			$ql = array(
				'award_title',
				'xxt_lottery_log',
				"enroll_key='$openedek'",
			);
			$lotteryResult = $this->model()->query_objs_ss($ql);
			if (!empty($lotteryResult)) {
				$lrs = array();
				foreach ($lotteryResult as $lr) {
					$lrs[] = $lr->award_title;
				}
				$record->data['lotteryResult'] = implode(',', $lrs);
			}
		}

		return new \ResponseData($record);
	}
	/**
	 * 列出所有的登记记录
	 *
	 * $site
	 * $app
	 * $orderby time|remark|score|follower
	 * $openid
	 * $page
	 * $size
	 *
	 * return
	 * [0] 数据列表
	 * [1] 数据总条数
	 * [2] 数据项的定义
	 *
	 */
	public function list_action($site, $app, $owner = 'U', $rid = '', $orderby = 'time', $openid = null, $page = 1, $size = 30) {
		$user = $this->who;
		switch ($owner) {
		case 'I':
			$options = array(
				'inviter' => $user->uid,
			);
			break;
		case 'A':
			$options = array();
			break;
		default:
			$options = array(
				'creater' => $user->uid,
			);
			break;
		}
		$options['rid'] = $rid;
		$options['page'] = $page;
		$options['size'] = $size;
		$options['orderby'] = $orderby;

		$app = $this->model('matter\enroll')->byId($app);
		$modelRec = $this->model('matter\enroll\record');

		$rst = $modelRec->find($app, $options);

		return new \ResponseData($rst);
	}
	/**
	 * 登记记录点赞
	 *
	 * $site
	 * $ek
	 */
	public function score_action($site, $ek) {
		/** 当前用户 */
		$user = $this->getUser($site);
		$openid = $user->openid;

		$modelRec = $this->model('app\enroll\record');
		if ($modelRec->hasScored($openid, $ek)) {
			/**
			 * 点过赞，再次点击，取消赞
			 */
			$this->model()->delete(
				'xxt_enroll_record_score',
				"enroll_key='$ek' and openid='$openid'"
			);
			$myScore = 0;
		} else {
			/**
			 * 点赞
			 */
			$i = array(
				'openid' => $openid,
				'nickname' => $user->nickname,
				'enroll_key' => $ek,
				'create_at' => time(),
				'score' => 1,
			);
			$this->model()->insert('xxt_enroll_record_score', $i, false);
			$myScore = 1;
		}
		/**
		 * 获得点赞的总数
		 */
		$score = $modelRec->score($ek);
		$this->model()->update('xxt_enroll_record', array('score' => $score), "enroll_key='$ek'");

		return new \ResponseData(array('myScore' => $myScore, 'score' => $score));
	}
	/**
	 * 返回对指定记录点赞的人
	 * @param string $site
	 * @param string $ek
	 */
	public function likerList_action($site, $ek, $page = 1, $size = 10) {
		$likers = $this->model('app\enroll\record')->likers($ek);

		return new \ResponseData(array('likers' => $likers));
	}
	/**
	 * 针对登记记录发表评论
	 *
	 * $site
	 * $ek
	 */
	public function remark_action($site, $ek) {
		$data = $this->getPostJson();
		if (empty($data->remark)) {
			return new \ResponseError('评论不允许为空！');
		}

		$modelEnroll = $this->model('app\enroll');
		/**
		 * 当前活动
		 */
		$q = array('aid,openid', 'xxt_enroll_record', "enroll_key='$ek'");
		$record = $this->model()->query_obj_ss($q);
		$app = $record->aid;
		$app = $modelEnroll->byId($app);
		/**
		 * 发表评论的用户
		 */
		$user = $this->getUser($site);
		if (empty($user->openid)) {
			return new \ResponseError('无法获得用户身份标识');
		}

		$remark = array(
			'openid' => $user->openid,
			'nickname' => $user->nickname,
			'enroll_key' => $ek,
			'create_at' => time(),
			'remark' => $this->model()->escape($data->remark),
		);
		$remark['id'] = $this->model()->insert('xxt_enroll_record_remark', $remark, true);
		$remark['nickname'] = $user->nickname;
		$this->model()->update("update xxt_enroll_record set remark_num=remark_num+1 where enroll_key='$ek'");
		/**
		 * 通知登记人有评论
		 */
		if ($app->remark_notice === 'Y' && !empty($app->remark_notice_page)) {
			$apis = $this->model('mp\mpaccount')->getApis($site);
			if ($apis && $apis->{$apis->mpsrc . '_custom_push'} === 'Y') {
				/**
				 * 发送评论提醒
				 */
				$url = 'http://' . $_SERVER['HTTP_HOST'] . "/rest/app/enroll?mpid=$site&aid=$app&ek=$ek&page=$app->remark_notice_page";
				$text = urlencode($remark['nickname'] . '对【');
				$text .= '<a href="' . $url . '">';
				$text .= urlencode($app->title);
				$text .= '</a>';
				$text .= urlencode('】发表了评论：' . $remark['remark']);
				$message = array(
					"msgtype" => "text",
					"text" => array(
						"content" => $text,
					),
				);
				/**
				 * 通知登记人
				 */
				if ($this->model('log')->canReceivePush($site, $record->openid)) {
					if ($record->openid !== $user->openid) {
						$this->sendByOpenid($site, $record->openid, $message);
					}
				}
				/**
				 * 通知其他发表了评论的用户
				 */
				$modelRec = $this->model('app\enroll\record');
				$others = $modelRec->remarkers($ek);
				foreach ($others as $other) {
					if ($other->openid === $record->openid || $other->openid === $remarker->openid) {
						continue;
					}
					$this->sendByOpenid($site, $other->openid, $message);
				}
			}
		}

		return new \ResponseData($remark);
	}
	/**
	 * 删除当前记录
	 *
	 * @param string $site
	 * @param string $app
	 */
	public function remove_action($site, $app, $ek) {
		$modelRec = $this->model('matter\enroll\record');

		$rst = $modelRec->removeByUser($site, $app, $ek);

		return new \ResponseData($rst);
	}
}