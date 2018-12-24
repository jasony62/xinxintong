<?php
/**
 * 用户邀请
 */
class i extends TMS_CONTROLLER {
	/**
	 *
	 */
	public function get_access_rule() {
		$ruleAction = [
			'rule_type' => 'black',
		];

		return $ruleAction;
	}
	/**
	 * 访问用户邀请页
	 *
	 * @param string $inviteCode 链接的编码
	 *
	 */
	public function index_action($inviteCode = null) {
		if (empty($inviteCode)) {
			TPL::assign('title', APP_TITLE);
			TPL::output('site/fe/invite/entry');
			exit;
		}
		/**
		 * 检查邀请是否可用
		 */
		$modelInv = $this->model('invite');
		$oInvite = $modelInv->byCode($inviteCode);
		if (false === $oInvite) {
			TPL::assign('title', APP_TITLE);
			$this->outputError('指定编码【' . $inviteCode . '】的邀请不存在');
		}
		if (empty($oInvite->matter_id) || empty($oInvite->matter_type)) {
			TPL::assign('title', APP_TITLE);
			$this->outputError('指定编码【' . $inviteCode . '】的邀请不可用');
		}

		if (!$this->_afterSnsOAuth($oInvite->matter_siteid)) {
			/* 检查是否需要第三方社交帐号OAuth */
			$this->_requireSnsOAuth($oInvite->matter_siteid);
		}

		/* 要访问的素材 */
		$modelMat = $this->model('matter\\' . $oInvite->matter_type);
		switch ($oInvite->matter_type) {
			case 'channel':
				$fields = 'id,state,siteid';
				break;
			default:
				$fields = 'id,state,siteid,entry_rule';
				break;
		}
		$oMatter = $modelMat->byId($oInvite->matter_id, ['fields' => $fields]);
		if (false === $oMatter || $oMatter->state !== '1') {
			$this->outputError('邀请访问的素材【' . $oInvite->matter_title . '】不存在');
		}

		/* 被邀请的用户 */
		$modelWay = $this->model('site\fe\way');
		$oInvitee = $modelWay->who($oInvite->matter_siteid);

		/* 如果当前用户已经被邀请过，就不再进行验证或记录日志 */
		$modelInvLog = $this->model('invite\log');
		$aInviteLogs = $modelInvLog->byUser($oMatter, $oInvitee->uid);
		if (0 === count($aInviteLogs)) {
			TPL::assign('title', empty($oInvite->matter_title) ? APP_TITLE : $oInvite->matter_title);
			TPL::output('site/fe/invite/access');
			exit;
		} else {
			$oInviteLog = $aInviteLogs[0];
		}

		/* 更新邀请访问数据 */
		$modelInv->addInviterCount($oInvite);
		/**
		 * 设置访问控制，生成token
		 */
		$oAccessToken = $this->model('invite\token')->add($oInviteLog);

		$matterUrl = $modelMat->getEntryUrl($oMatter->siteid, $oMatter->id);
		$matterUrl .= strpos($matterUrl, '?') === false ? '?' : '&';
		$matterUrl .= 'inviteToken=' . $oAccessToken->token;

		$this->redirect($matterUrl);
	}
	/**
	 * 获得邀请对应的素材的访问地址
	 */
	public function matterUrl_action($invite) {
		$modelInv = $this->model('invite');
		$oInvite = $modelInv->byId($invite);
		if (false === $oInvite) {
			return new \ObjectNotFoundError();
		}
		/* 要访问的素材 */
		$modelMat = $this->model('matter\\' . $oInvite->matter_type);
		switch ($oInvite->matter_type) {
			case 'channel':
				$fields = 'id,state,siteid';
				break;
			default:
				$fields = 'id,state,siteid,entry_rule';
				break;
		}
		$oMatter = $modelMat->byId($oInvite->matter_id, ['fields' => $fields]);
		if (false === $oMatter || $oMatter->state !== '1') {
			return new \ObjectNotFoundError();
		}
		/* 被邀请的用户 */
		$modelWay = $this->model('site\fe\way');
		$oInvitee = $modelWay->who($oInvite->matter_siteid);

		$posted = $this->getPostJson();

		/* 检查进入规则 */
		if (isset($oMatter->entry_rule)) {
			$oEntryRule = is_string($oMatter->entry_rule) ? json_decode($oMatter->entry_rule) : $oMatter->entry_rule;
			if (isset($oEntryRule->scope)) {
				switch ($oEntryRule->scope) {
				case 'member':
					if (empty($oInvitee->unionid)) {
						return new \ResponseError('请登录后再提交通讯录信息');
					}
					$isMember = false;
					$modelMem = $this->model('site\user\member');
					if (is_array($oEntryRule->member)) {
						foreach ($oEntryRule->member as $mschemaId) {
							$oMembers = $modelMem->byUnionid($oMatter->siteid, $mschemaId, $oInvitee->unionid);
							if (!empty($oMembers)) {
								$isMember = true;
								break;
							}
						}
					} else {
						foreach ($oEntryRule->member as $mschemaId => $oRule) {
							$oMembers = $modelMem->byUnionid($oMatter->siteid, $mschemaId, $oInvitee->unionid);
							if (!empty($oMembers)) {
								$isMember = true;
								break;
							}
						}
					}
					if (false === $isMember) {
						/* 需要提交通讯录用户信息 */
						if (empty($posted->member)) {
							return new \ResponseError('参数不完整，没有提交通讯录所需信息');
						}
						$rst = $this->_submitMember($oMatter->siteid, $posted->member, $oInvitee);
						if (false === $rst[0]) {
							return new ResponseError($rst[1]);
						}
					}
					break;
				case 'group':
					break;
				}
			}
		}

		/* 检查邀请码 */
		if (empty($posted->inviteCode)) {
			return new \ResponseError('请提供邀请码');
		}
		$inviteCode = $posted->inviteCode;
		$modelCode = $this->model('invite\code');
		$result = $modelCode->checkAndUse($oInvite, $inviteCode, $oInvitee);
		if (false === $result[0]) {
			return new \ResponseError($result[1]);
		}
		$oInviteLog = $result[1];

		/* 更新邀请访问数据 */
		$modelInv->addInviterCount($oInvite);

		/**
		 * 设置访问控制，生成token
		 */
		$oAccessToken = $this->model('invite\token')->add($oInviteLog);

		$matterUrl = $modelMat->getEntryUrl($oMatter->siteid, $oMatter->id);
		$matterUrl .= strpos($matterUrl, '?') === false ? '?' : '&';
		$matterUrl .= 'inviteToken=' . $oAccessToken->token;

		return new \ResponseData($matterUrl);

	}
	/**
	 * 提交信息中包含的自定义用户信息
	 */
	private function _submitMember($siteId, $oMember, $oUser) {
		$schemaId = $oMember->schema_id;
		$oMschema = $this->model('site\user\memberschema')->byId($schemaId, ['fields' => 'siteid,id,title,auto_verified,attr_mobile,attr_email,attr_name,extattr']);
		$modelMem = $this->model('site\user\member');

		$existentMember = $modelMem->byUser($oUser->uid, ['schemas' => $schemaId]);
		if (count($existentMember)) {
			$memberId = $existentMember[0]->id;
			$oMember->id = $memberId;
			$oMember->verified = $existentMember[0]->verified;
			$oMember->identity = $existentMember[0]->identity;
			$rst = $modelMem->modify($oMschema, $memberId, $oMember);
		} else {
			$rst = $modelMem->createByApp($oMschema, $oUser->uid, $oMember);
			/**
			 * 将用户自定义信息和当前用户进行绑定
			 */
			if ($rst[0] === true) {
				$oMember = $rst[1];
				$this->model('site\fe\way')->bindMember($siteId, $oMember);
			}
		}

		return $rst;
	}
	/**
	 *
	 */
	protected function outputError($err, $title = '程序错误') {
		TPL::assign('title', $title);
		TPL::assign('body', $err);
		TPL::output('error');
		exit;
	}
	/**
	 * 检查是否当前的请求是OAuth后返回的请求
	 */
	private function _afterSnsOAuth($siteid) {
		$aAuth = []; // 当前用户的身份信息
		if (isset($_GET['mocker'])) {
			// 指定的模拟用户
			list($snsName, $openid) = explode(',', $_GET['mocker']);
			$snsUser = new \stdclass;
			$snsUser->openid = $openid;
			$aAuth['sns'][$snsName] = $snsUser;
		} else {
			$snsSiteId = false;
			if ($this->myGetcookie("_{$siteid}_oauthpending") === 'Y') {
				$snsSiteId = $siteid;
			} else if ($this->myGetcookie("_platform_oauthpending") === 'Y') {
				$snsSiteId = 'platform';
			}
			if (false === $snsSiteId) {
				return false;
			}
			// oauth回调
			$this->mySetcookie("_{$snsSiteId}_oauthpending", '', time() - 3600);
			if (isset($_GET['state']) && isset($_GET['code'])) {
				$state = $_GET['state'];
				if (strpos($state, 'snsOAuth-') === 0) {
					$code = $_GET['code'];
					$snsName = explode('-', $state);
					if (count($snsName) === 2) {
						$snsName = $snsName[1];
						if ($snsUser = $this->_snsOAuthUserByCode($snsSiteId, $code, $snsName)) {
							/* 企业用户仅包含openid */
							$aAuth['sns'][$snsName] = $snsUser;
						}
					}
				}
			}
		}

		if (!empty($aAuth)) {
			// 如果获得了用户的身份信息，更新保留的用户信息
			$modelWay = $this->model('site\fe\way');
			$this->who = $modelWay->who($siteid, $aAuth);
		}

		return true;
	}
	/**
	 * 检查是否需要第三方社交帐号认证
	 * 检查条件：
	 * 0、应用是否设置了需要认证
	 * 1、站点是否绑定了第三方社交帐号认证
	 * 2、平台是否绑定了第三方社交帐号认证
	 * 3、用户客户端是否可以发起认证
	 *
	 * @param string $site
	 */
	private function _requireSnsOAuth($siteid) {
		$modelWay = $this->model('site\fe\way');
		$user = $modelWay->who($siteid);
		$userAgent = $this->userAgent();

		if ($userAgent === 'wx') {
			if (!isset($user->sns->wx)) {
				$modelWx = $this->model('sns\wx');
				if (($wxConfig = $modelWx->bySite($siteid)) && $wxConfig->joined === 'Y') {
					$this->_snsOAuth($wxConfig, 'wx');
				} else if (($wxConfig = $modelWx->bySite('platform')) && $wxConfig->joined === 'Y') {
					$this->_snsOAuth($wxConfig, 'wx');
				}
			}
			if (!isset($user->sns->qy)) {
				if ($qyConfig = $this->model('sns\qy')->bySite($siteid)) {
					if ($qyConfig->joined === 'Y') {
						$this->_snsOAuth($qyConfig, 'qy');
					}
				}
			}
		} else if ($userAgent === 'yx') {
			if (!isset($user->sns->yx)) {
				if ($yxConfig = $this->model('sns\yx')->bySite($siteid)) {
					if ($yxConfig->joined === 'Y') {
						$this->_snsOAuth($yxConfig, 'yx');
					}
				}
			}
		}

		return false;
	}
	/**
	 * 执行OAuth操作
	 *
	 * 会在cookie保留结果5分钟
	 *
	 * $site
	 * $controller OAuth的回调地址
	 * $state OAuth回调时携带的参数
	 */
	private function _snsOAuth(&$snsConfig, $snsName, $ruri = '') {
		if (empty($ruri)) {
			$ruri = APP_PROTOCOL . APP_HTTP_HOST . $_SERVER['REQUEST_URI'];
		}

		switch ($snsName) {
		case 'qy':
			$snsProxy = $this->model('sns\qy\proxy', $snsConfig);
			$oauthUrl = $snsProxy->oauthUrl($ruri, 'snsOAuth-' . $snsName);
			break;
		case 'wx':
			if ($snsConfig->can_oauth === 'Y') {
				$snsProxy = $this->model('sns\wx\proxy', $snsConfig);
				$oauthUrl = $snsProxy->oauthUrl($ruri, 'snsOAuth-' . $snsName, 'snsapi_base');
			}
			break;
		case 'yx':
			if ($snsConfig->can_oauth === 'Y') {
				$snsProxy = $this->model('sns\yx\proxy', $snsConfig);
				$oauthUrl = $snsProxy->oauthUrl($ruri, 'snsOAuth-' . $snsName);
			}
			break;
		}
		if (isset($oauthUrl)) {
			/* 通过cookie判断是否是后退进入 */
			$this->mySetcookie("_{$snsConfig->siteid}_oauthpending", 'Y');
			$this->redirect($oauthUrl);
		}

		return false;
	}
	/**
	 * 通过OAuth接口获得用户信息
	 *
	 * @param string $site
	 * @param string $code
	 * @param string $snsName
	 */
	protected function _snsOAuthUserByCode($site, $code, $snsName) {
		$modelSns = $this->model('sns\\' . $snsName);
		$snsConfig = $modelSns->bySite($site);
		if (($snsConfig === false || $snsConfig->joined !== 'Y') && $snsName === 'wx') {
			$snsConfig = $modelSns->bySite('platform');
		}
		if ($snsConfig === false) {
			$this->model('log')->log($site, 'snsOAuthUserByCode', 'snsConfig: false', null, $_SERVER['REQUEST_URI']);
			return false;
		}
		$snsProxy = $this->model('sns\\' . $snsName . '\proxy', $snsConfig);
		$rst = $snsProxy->getOAuthUser($code);
		if ($rst[0] === false) {
			$this->model('log')->log($site, 'snsOAuthUserByCode', 'xxt oauth2 failed: ' . $rst[1], null, $_SERVER['REQUEST_URI']);
			$snsUser = false;
		} else {
			$snsUser = $rst[1];
		}

		return $snsUser;
	}
}