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
	 * @param string $code 链接的编
	 *
	 */
	public function index_action($code = null) {
		if (empty($code)) {
			TPL::assign('title', APP_TITLE);
			TPL::output('site/fe/invite/entry');
			exit;
		}
		/**
		 * 检查邀请是否可用
		 */
		$modelInv = $this->model('invite');
		$oInvite = $modelInv->byCode($code);
		if (false === $oInvite) {
			TPL::assign('title', APP_TITLE);
			$this->outputError('指定编码【' . $code . '】的邀请不存在');
		}
		if (empty($oInvite->matter_id) || empty($oInvite->matter_type)) {
			TPL::assign('title', APP_TITLE);
			$this->outputError('指定编码【' . $code . '】的邀请不可用');
		}

		if (!$this->_afterSnsOAuth()) {
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
		if ($this->userAgent() === 'wx') {
			if (!isset($this->who->sns->wx)) {
				$modelWx = $this->model('sns\wx');
				if (($wxConfig = $modelWx->bySite($siteid)) && $wxConfig->joined === 'Y') {
					$this->snsOAuth($wxConfig, 'wx');
				} else if (($wxConfig = $modelWx->bySite('platform')) && $wxConfig->joined === 'Y') {
					$this->snsOAuth($wxConfig, 'wx');
				}
			}
			if (!isset($this->who->sns->qy)) {
				if ($qyConfig = $this->model('sns\qy')->bySite($siteid)) {
					if ($qyConfig->joined === 'Y') {
						$this->snsOAuth($qyConfig, 'qy');
					}
				}
			}
		} else if ($this->userAgent() === 'yx') {
			if (!isset($this->who->sns->yx)) {
				if ($yxConfig = $this->model('sns\yx')->bySite($siteid)) {
					if ($yxConfig->joined === 'Y') {
						$this->snsOAuth($yxConfig, 'yx');
					}
				}
			}
		}

		return false;
	}
}