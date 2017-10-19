<?php
namespace site\fe\matter\signin;

include_once dirname(__FILE__) . '/base.php';
/**
 * 签到活动记录
 */
class record extends base {
	/**
	 * 解决跨域异步提交问题
	 */
	public function submitkeyGet_action() {
		/* support CORS */
		header('Access-Control-Allow-Origin:*');
		$key = md5(uniqid() . mt_rand());

		return new \ResponseData($key);
	}
	/**
	 * 提交登记数据并签到
	 *
	 * 执行签到，在每个轮次上只能进行一次签到，第一次签到后再提交也不会更改签到时间等信息
	 *
	 * @param string $site
	 * @param string $app
	 * @param string $ek enrollKey 如果要更新之前已经提交的数据，需要指定
	 * @param string $submitkey 支持文件分段上传
	 *
	 */
	public function submit_action($site, $app, $submitkey = '') {
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

		$modelApp = $this->model('matter\signin');
		if (false === ($oSigninApp = $modelApp->byId($app, ['cascaded' => 'N']))) {
			header('HTTP/1.0 500 parameter error:app dosen\'t exist.');
			die('签到活动不存在');
		}

		$oUser = clone $this->who;

		/**
		 * 提交的数据
		 */
		$oSigninData = $this->getPostJson();
		/**
		 * 签到用户昵称
		 */
		if ((isset($oSigninApp->assignedNickname->valid) && $oSigninApp->assignedNickname->valid === 'Y') && isset($oSigninApp->assignedNickname->schema->id)) {
			$oUser->nickname = empty($oSigninData->{$oSigninApp->assignedNickname->schema->id}) ? '' : $oSigninData->{$oSigninApp->assignedNickname->schema->id};
		} else {
			$userNickname = $modelApp->getUserNickname($oSigninApp, $oUser);
			$oUser->nickname = $userNickname;
		}
		/**
		 * 包含用户身份信息
		 */
		if (isset($oSigninData->member) && isset($oSigninData->member->schema_id)) {
			$member = clone $oSigninData->member;
			$rst = $this->_submitMember($site, $member, $oUser);
			if ($rst[0] === false) {
				return new \ParameterError($rst[1]);
			}
		}
		/**
		 * 签到并保存登记的数据
		 */
		$modelRec = $this->model('matter\signin\record')->setOnlyWriteDbConn(true);
		$oSignState = $modelRec->signin($oUser, $oSigninApp, $oSigninData);
		// 保存签到登记数据
		empty($submitkey) && $submitkey = $oUser->uid;
		$rst = $modelRec->setData($site, $oSigninApp, $oSignState->ek, $oSigninData, $submitkey);
		if (false === $rst[0]) {
			return new \ResponseError($rst[1]);
		}
		/**
		 * 检查签到数据是否在报名表中
		 */
		if (!empty($oSigninApp->enroll_app_id)) {
			$oEnrollApp = $this->model('matter\enroll')->byId($oSigninApp->enroll_app_id, ['cascaded' => 'N']);
			if ($oEnrollApp) {
				/*获得要检查的数据*/
				$dataSchemas = $oSigninApp->dataSchemas;
				$requireCheckedData = new \stdClass;
				foreach ($dataSchemas as $dataSchema) {
					if (isset($dataSchema->requireCheck) && $dataSchema->requireCheck === 'Y') {
						$requireCheckedData->{$dataSchema->id} = isset($oSigninData->{$dataSchema->id}) ? $oSigninData->{$dataSchema->id} : '';
					}
				}
				if ($oSigninApp->mission_phase_id) {
					/* 需要匹配项目阶段 */
					$requireCheckedData->phase = $oSigninApp->mission_phase_id;
				}
				/* 在指定的登记活动中检查数据 */
				$modelEnrollRec = $this->model('matter\enroll\record');
				$enrollRecords = $modelEnrollRec->byData($oEnrollApp, $requireCheckedData);
				if (!empty($enrollRecords)) {
					/**
					 * 找报名表中找到对应的记录，如果找到多条记录怎么办？
					 */
					$oEnrollRecord = $enrollRecords[0];
					if ($oEnrollRecord->verified === 'Y') {
						$enrollData = $enrollRecords[0]->data;
						foreach ($enrollData as $n => $v) {
							!isset($oSigninData->{$n}) && $oSigninData->{$n} = $v;
						}
						// 记录报名数据
						$modelRec->setData($site, $oSigninApp, $oSignState->ek, $oSigninData, $submitkey);
						// 记录验证状态
						$modelRec->update(
							'xxt_signin_record',
							['verified' => 'Y', 'verified_enroll_key' => $oEnrollRecord->enroll_key],
							"enroll_key='{$oSignState->ek}'"
						);
						$oSignState->verified = 'Y';
						// 返回指定的验证成功页
						if (isset($oSigninApp->entry_rule->success->entry)) {
							$oSignState->forword = $oSigninApp->entry_rule->success->entry;
						}
						/* 如果分组数据中未包含用户信息，更新用户信息 */
						if (empty($oEnrollRecord->userid)) {
							$oUserAcnt = $this->model('site\user\account')->byId($oUser->uid, ['fields' => 'wx_openid,yx_openid,qy_openid,headimgurl']);
							if (false === $oUserAcnt) {
								$oUserAcnt = new \stdClass;
							}
							$oUserAcnt->userid = $oUser->uid;
							$oUserAcnt->nickname = $modelEnrollRec->escape($oUser->nickname);
							$modelEnrollRec->update('xxt_enroll_record', $oUserAcnt, ['id' => $oEnrollRecord->id]);
						}
					}
				}
				if (!isset($oSignState->verified)) {
					/**
					 * 没有在报名表中找到对应的记录
					 */
					$modelRec->update(
						'xxt_signin_record',
						['verified' => 'N', 'verified_enroll_key' => ''],
						"enroll_key='{$oSignState->ek}'"
					);
					$oSignState->verified = 'N';
					if (isset($oSigninApp->entry_rule->fail->entry)) {
						$oSignState->forword = $oSigninApp->entry_rule->fail->entry;
					}
				}
			}
		}
		/**
		 * 检查是否存在匹配的分组记录
		 */
		if (!empty($oSigninApp->group_app_id)) {
			$oGroupApp = $this->model('matter\group')->byId($oSigninApp->group_app_id);
			if (empty($oGroupApp)) {
				return new \ParameterError('指定的登记匹配分组活动不存在');
			}
			/* 获得要检查的登记项 */
			$requireCheckedData = new \stdClass;
			$dataSchemas = json_decode($oSigninApp->data_schemas);
			foreach ($dataSchemas as $dataSchema) {
				if (isset($dataSchema->requireCheck) && $dataSchema->requireCheck === 'Y') {
					if (isset($dataSchema->fromApp) && $dataSchema->fromApp === $oSigninApp->group_app_id) {
						$requireCheckedData->{$dataSchema->id} = isset($oSigninData->{$dataSchema->id}) ? $oSigninData->{$dataSchema->id} : '';
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
			$matchedData = $oGroupRecord->data;
			foreach ($matchedData as $n => $v) {
				!isset($oSigninData->{$n}) && $oSigninData->{$n} = $v;
			}
			if (isset($oGroupRecord->round_id)) {
				$oSigninData->_round_id = $oGroupRecord->round_id;
			}
		}
		/* 记录操作日志 */
		$this->_logSubmit($oSigninApp, $oSignState->ek);

		/**
		 * 当前轮次签到
		 */
		if (false === $oSignState->signed) {
			/**
			 * 发放签到积分
			 */
			$modelCoin = $this->model('matter\signin\coin')->setOnlyWriteDbConn(true);
			$modelClog = $this->model('site\coin\log')->setOnlyWriteDbConn(true);
			if ($oSignState->late) {
				$coinEvent = 'site.matter.signin.submit.late';
			} else {
				$coinEvent = 'site.matter.signin.submit.ontime';
			}
			$aCoinRules = $modelCoin->rulesByMatter($coinEvent, $oSigninApp);
			$modelClog->award($oSigninApp, $oUser, $coinEvent, $aCoinRules);
			/**
			 * 更新项目用户信息
			 */
			if (!empty($oSigninApp->mission_id)) {
				$modelMisUsr = $this->model('matter\mission\user');
				$modelMisUsr->setOnlyWriteDbConn(true);
				$oMission = $this->model('matter\mission')->byId($oSigninApp->mission_id, ['fields' => 'siteid,id,user_app_type,user_app_id']);
				if ($oMission->user_app_type === 'group') {
					$oMisUsrGrpApp = (object) ['id' => $oMission->user_app_id];
					$oMisGrpUser = $this->model('matter\group\player')->byUser($oMisUsrGrpApp, $oUser->uid, ['onlyOne' => true, 'round_id']);
				}
				$oMisUsr = $modelMisUsr->byId($oMission, $oUser->uid, ['fields' => 'id,nickname,group_id,last_signin_at,signin_num,user_total_coin']);
				if (false === $oMisUsr) {
					$aNewMisUser = ['last_signin_at' => time(), 'signin_num' => 1];
					if (!empty($oMisGrpUser->round_id)) {
						$aNewMisUser['group_id'] = $oMisGrpUser->round_id;
					}
					if (!empty($aCoinRules)) {
						$aNewMisUser['user_total_coin'] = 0;
						foreach ($aCoinRules as $rule) {
							$aNewMisUser['user_total_coin'] = $aNewMisUser['user_total_coin'] + (int) $rule->actor_delta;
						}
					}
					$modelMisUsr->add($oMission, $oUser, $aNewMisUser);
				} else {
					$aUpdMisUser = ['last_signin_at' => time(), 'signin_num' => $oMisUsr->signin_num + 1];
					if ($oMisUsr->nickname !== $oUser->nickname) {
						$aUpdMisUser['nickname'] = $oUser->nickname;
					}
					if (isset($oMisGrpUser->round_id)) {
						if ($oMisUsr->group_id !== $oMisGrpUser->round_id) {
							$aUpdMisUser['group_id'] = $oMisGrpUser->round_id;
						}
					}
					if (!empty($aCoinRules)) {
						$aUpdMisUser['user_total_coin'] = (int) $oMisUsr->user_total_coin;
						foreach ($aCoinRules as $rule) {
							$aUpdMisUser['user_total_coin'] = $aUpdMisUser['user_total_coin'] + (int) $rule->actor_delta;
						}
					}
					$modelMisUsr->update(
						'xxt_mission_user',
						$aUpdMisUser,
						['id' => $oMisUsr->id]
					);
				}
			}
		}
		/**
		 * 通知登记活动事件接收人
		 */
		if ($oSigninApp->notify_submit === 'Y') {
			$this->_notifyReceivers($oSigninApp, $oSignState->ek);
		}

		return new \ResponseData($oSignState);
	}
	/**
	 * 记录用户提交日志
	 *
	 * @param object $oApp
	 *
	 */
	private function _logSubmit($oApp, $ek) {
		$modelLog = $this->model('matter\log');

		$logUser = new \stdClass;
		$logUser->userid = $this->who->uid;
		$logUser->nickname = $this->who->nickname;

		$operation = new \stdClass;
		$operation->name = 'submit';
		$operation->data = $this->model('matter\signin\record')->byId($ek, ['fields' => 'enroll_key,signin_log,data']);

		$client = new \stdClass;
		$client->agent = $_SERVER['HTTP_USER_AGENT'];
		$client->ip = $this->client_ip();

		$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

		$logid = $modelLog->addUserMatterOp($oApp->siteid, $logUser, $oApp, $operation, $client, $referer);

		return $logid;
	}
	/**
	 * 提交信息中包含的自定义用户信息
	 */
	private function _submitMember($siteId, &$member, &$user) {
		$schemaId = $member->schema_id;
		$oMschema = $this->model('site\user\memberschema')->byId($schemaId, ['fields' => 'siteid,id,title,auto_verified,attr_mobile,attr_email,attr_name,extattr']);
		$modelMem = $this->model('site\user\member');

		$existentMember = $modelMem->byUser($user->uid, array('schemas' => $schemaId));
		if (count($existentMember)) {
			$memberId = $existentMember[0]->id;
			$member->id = $memberId;
			$member->verified = $existentMember[0]->verified;
			$member->identity = $existentMember[0]->identity;
			$rst = $modelMem->modify($oMschema, $memberId, $member);
		} else {
			$rst = $modelMem->createByApp($oMschema, $user->uid, $member);
		}
		$member->schema_id = $schemaId;

		return $rst;
	}
	/**
	 * 通知签到活动事件接收人
	 *
	 * @param string $siteId
	 * @param object $app
	 * @param string $ek
	 *
	 */
	private function _notifyReceivers(&$oApp, $ek) {
		$receivers = $this->model('matter\signin\receiver')->byApp($oApp->siteid, $oApp->id);
		if (count($receivers) === 0) {
			return false;
		}
		/* 获得活动的管理员链接 */
		$appURL = $this->model('matter\signin')->getOpUrl($oApp->siteid, $oApp->id);
		$modelQurl = $this->model('q\url');
		$noticeURL = $modelQurl->urlByUrl($oApp->siteid, $appURL);
		/* 模板消息参数 */
		$params = new \stdClass;
		$notice = $this->model('site\notice')->byName($oApp->siteid, 'site.signin.submit');
		if ($notice === false) {
			return false;
		}
		$tmplConfig = $this->model('matter\tmplmsg\config')->byId($notice->tmplmsg_config_id, ['cascaded' => 'Y']);
		if (!isset($tmplConfig->tmplmsg)) {
			return false;
		}
		foreach ($tmplConfig->tmplmsg->params as $param) {
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
		$modelTmplBat->send($oApp->siteid, $tmplConfig->msgid, $receivers, $params, ['event_name' => 'site.signin.submit', 'send_from' => 'signin:' . $oApp->id . ':' . $ek]);

		return true;
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
		header('Access-Control-Allow-Origin:*');
		header('Access-Control-Allow-Methods:POST');
		if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
			exit;
		}
		if (empty($submitkey)) {
			$user = $this->who;
			$submitkey = $user->uid;
		}
		/** 分块上传文件 */
		if (defined('SAE_TMP_PATH')) {
			$dest = '/' . $app . '/' . $submitkey . '_' . $_POST['resumableFilename'];
			$resumable = \TMS_APP::M('fs/resumableAliOss', $site, $dest, 'xinxintong');
			$resumable->handleRequest();
		} else {
			$modelFs = \TMS_APP::M('fs/local', $site, '_resumable');
			$dest = $submitkey . '_' . $_POST['resumableIdentifier'];
			$resumable = \TMS_APP::M('fs/resumable', $site, $dest, $modelFs);
			$resumable->handleRequest($_POST);
		}

		return new \ResponseData('ok');
	}
	/**
	 * 返回指定记录或最后一条记录
	 *
	 * @param string $site
	 * @param string $app
	 */
	public function get_action($site, $app) {
		$modelApp = $this->model('matter\signin');
		$modelRec = $this->model('matter\signin\record');
		$options = ['cascade' => 'N'];

		$app = $modelApp->byId($app, $options);

		// 当前访问用户的基本信息
		$user = $this->who;

		// 登记数据
		$options = array(
			'fields' => '*',
		);

		$record = $modelRec->byUser($user, $app, $options);

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

		$modelRec = $this->model('matter\signin\record');

		$rst = $modelRec->byApp($app, $options);

		return new \ResponseData($rst);
	}
}