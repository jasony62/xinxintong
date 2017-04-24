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
		if (false === ($oEnrollApp = $modelEnl->byId($app, ['cascaded' => 'N']))) {
			header('HTTP/1.0 500 parameter error:app dosen\'t exist.');
			die('登记活动不存在');
		}

		// 当前访问用户的基本信息
		$user = $this->who;
		// 提交的数据
		$enrolledData = $this->getPostJson();
		// 检查是否允许登记
		$rst = $this->_canEnroll($site, $oEnrollApp, $user, $enrolledData, $ek);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}
		/**
		 * 检查是否存在匹配的登记记录
		 */
		if (!empty($oEnrollApp->enroll_app_id)) {
			$matchApp = $modelEnl->byId($oEnrollApp->enroll_app_id);
			if (empty($matchApp)) {
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
		if (!empty($oEnrollApp->group_app_id)) {
			$groupApp = $this->model('matter\group')->byId($oEnrollApp->group_app_id);
			if (empty($groupApp)) {
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
		$modelRec->setOnlyWriteDbConn(true);
		if (empty($ek)) {
			/* 插入登记数据 */
			$ek = $modelRec->enroll($site, $oEnrollApp, $user);
			/* 处理自定义信息 */
			$rst = $modelRec->setData($user, $oEnrollApp, $ek, $enrolledData, $submitkey, true);
			/* 登记提交的积分奖励 */
			$modelCoin = $this->model('site\coin\log');
			$modelCoin->award($oEnrollApp, $user, 'site.matter.enroll.submit');
		} else {
			/* 重新插入新提交的数据 */
			$rst = $modelRec->setData($user, $oEnrollApp, $ek, $enrolledData, $submitkey);
			if ($rst[0] === true) {
				/* 已经登记，更新原先提交的数据，只要进行更新操作就设置为未审核通过的状态 */
				$updatedEnrollRec['enroll_at'] = time();
				$updatedEnrollRec['userid'] = $user->uid;
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
		$this->_logSubmit($site, $oEnrollApp, $ek);
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
		$oSchema = $this->model('site\user\memberschema')->byId($schemaId, 'id,title,attr_mobile,attr_email,attr_name,extattr');
		$modelMem = $this->model('site\user\member');

		$existentMember = $modelMem->byUser($user->uid, ['schemas' => $schemaId]);
		if (count($existentMember)) {
			$memberId = $existentMember[0]->id;
			$member->id = $memberId;
			$rst = $modelMem->modify($siteId, $oSchema, $memberId, $member);
		} else {
			$rst = $modelMem->createByApp($siteId, $oSchema, $user->uid, $member);
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
			if ($mapping->src === 'matter') {
				if (isset($oApp->{$mapping->id})) {
					$value = $oApp->{$mapping->id};
				}
			} else if ($mapping->src === 'text') {
				$value = $mapping->name;
			}
			!isset($value) && $value = '';
			$params->{$param->pname} = $value;
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

		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		/*当前访问用户的基本信息*/
		$user = $this->who;
		/**登记数据*/
		if (empty($openedek)) {
			// 获得最后一条登记数据。登记记录有可能未进行过登记
			$record = $modelRec->getLast($oApp, $user, ['fields' => '*']);
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
	 * @param string $ek
	 *
	 */
	public function score_action($ek) {
		/** 当前用户 */
		$user = $this->who;

		$modelRec = $this->model('matter\enroll\record');
		if ($modelRec->hasUserScored($user->uid, $ek)) {
			/**
			 * 点过赞，再次点击，取消赞
			 */
			$modelRec->delete(
				'xxt_enroll_record_score',
				['enroll_key' => $ek, 'userid' => $user->uid]
			);
			$myScore = 0;
		} else {
			/**
			 * 点赞
			 */
			$scoreLog = new \stdClass;
			$scoreLog->userid = $user->uid;
			$scoreLog->nickname = $user->nickname;
			$scoreLog->enroll_key = $ek;
			$scoreLog->create_at = time();
			$scoreLog->score = 1;

			$modelRec->insert('xxt_enroll_record_score', $scoreLog, false);
			$myScore = 1;
		}
		/**
		 * 获得点赞的总数
		 */
		$score = $modelRec->scoreById($ek);
		$modelRec->update('xxt_enroll_record', ['score' => $score], ['enroll_key' => $ek]);

		return new \ResponseData(array('myScore' => $myScore, 'score' => $score));
	}
	/**
	 * 返回对指定记录点赞的人
	 *
	 * @param string $site
	 * @param string $ek
	 *
	 */
	public function likerList_action($site, $ek, $page = 1, $size = 10) {
		$likers = $this->model('app\enroll\record')->likers($ek);

		return new \ResponseData(array('likers' => $likers));
	}
	/**
	 * 针对登记记录发表评论
	 *
	 * @param string $ek
	 */
	public function remark_action($ek) {
		$data = $this->getPostJson();
		if (empty($data->content)) {
			return new \ResponseError('评论内容不允许为空');
		}

		$modelRec = $this->model('matter\enroll\record');
		$oRecord = $modelRec->byId($ek);
		if (false === $oRecord) {
			return new \ObjectNotFoundError();
		}
		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($oRecord->siteid, ['cascaded' => 'N']);
		/**
		 * 发表评论的用户
		 */
		$user = $this->who;

		$remark = new \stdClass;
		$remark->userid = $user->uid;
		$remark->nickname = $user->nickname;
		$remark->enroll_key = $ek;
		$remark->create_at = time();
		$remark->content = $modelRec->escape($data->content);

		$remark->id = $modelRec->insert('xxt_enroll_record_remark', $remark, true);

		$modelRec->update("update xxt_enroll_record set remark_num=remark_num+1 where enroll_key='$ek'");

		$this->_notifyHasRemark();

		return new \ResponseData($remark);
	}
	/**
	 * 通知有新评论
	 */
	private function _notifyHasRemark() {

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