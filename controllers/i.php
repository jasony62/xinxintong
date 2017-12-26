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

		/* 要访问的素材 */
		$modelMat = $this->model('matter\\' . $oInvite->matter_type);
		$oMatter = $modelMat->byId($oInvite->matter_id, ['fields' => 'siteid,id,entry_rule']);
		if (false === $oMatter) {
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
		$oMatter = $modelMat->byId($oInvite->matter_id, ['fields' => 'siteid,id,entry_rule']);
		if (false === $oMatter) {
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
				case 'member';
					if (empty($oInvitee->unionid)) {
						return new \ResponseError('请登录后再提交通讯录信息');
					}
					$isMember = false;
					$modelMem = $this->model('site\user\member');
					foreach ($oEntryRule->member as $mschemaId) {
						$oMembers = $modelMem->byUnionid($oMatter->siteid, $mschemaId, $oInvitee->unionid);
						if (!empty($oMembers)) {
							$isMember = true;
							break;
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
	 * 检查素材进入规则
	 *
	 * @param object $oMatter
	 * @param boolean $redirect
	 *
	 * @return string page 页面名称
	 *
	 */
	private function _checkEntryRule($oUser, $oMatter, $bRedirect = false) {
		$oEntryRule = $oMatter->entry_rule;
		$bMatched = false;
		$result = '';
		if (isset($oEntryRule->scope) && $oEntryRule->scope === 'group') {
			/* 限分组用户访问 */
			if (isset($oEntryRule->group)) {
				!is_object($oEntryRule->group) && $oEntryRule->group = (object) $oEntryRule->group;
				$oGroupApp = $oEntryRule->group;
				if (isset($oGroupApp->id)) {
					$oGroupUsr = $this->model('matter\group\player')->byUser($oGroupApp, $oUser->uid, ['fields' => 'round_id,round_title']);
					if (count($oGroupUsr)) {
						$oGroupUsr = $oGroupUsr[0];
						if (isset($oGroupApp->round) && isset($oGroupApp->round->id)) {
							if ($oGroupUsr->round_id === $oGroupApp->round->id) {
								$bMatched = true;
							}
						} else {
							$bMatched = true;
						}
					}
				}
			}
			if (!$bMatched) {
				$result = '您目前不满足【' . $oMatter->title . '】的进入规则，无法访问，请联系活动的组织者解决';
			}
		} else if (isset($oEntryRule->scope) && $oEntryRule->scope === 'member') {
			/* 限通讯录用户访问 */
			foreach ($oEntryRule->member as $schemaId) {
				/* 检查用户的信息是否完整，是否已经通过审核 */
				$modelMem = $this->model('site\user\member');
				if (empty($oUser->unionid)) {
					$aMembers = $modelMem->byUser($oUser->uid, ['schemas' => $schemaId]);
					if (count($aMembers) === 1) {
						$oMember = $aMembers[0];
						if ($oMember->verified === 'Y') {
							$bMatched = true;
							break;
						}
					}
				} else {
					$modelAcnt = $this->model('site\user\account');
					$aUnionUsers = $modelAcnt->byUnionid($oUser->unionid, ['siteid' => $oMatter->siteid, 'fields' => 'uid']);
					foreach ($aUnionUsers as $oUnionUser) {
						$aMembers = $modelMem->byUser($oUnionUser->uid, ['schemas' => $schemaId]);
						if (count($aMembers) === 1) {
							$oMember = $aMembers[0];
							if ($oMember->verified === 'Y') {
								$bMatched = true;
								break;
							}
						}
					}
					if ($bMatched) {
						break;
					}
				}
			}
			if (!$bMatched) {
				$result = '$memberschema';
			}
		} else if (isset($oEntryRule->scope) && $oEntryRule->scope === 'sns') {
			foreach ($oEntryRule->sns as $snsName) {
				if (isset($oUser->sns) && isset($oUser->sns->{$snsName})) {
					// 检查用户对应的公众号
					if ($snsName === 'wx') {
						$modelWx = $this->model('sns\wx');
						if (($wxConfig = $modelWx->bySite($oMatter->siteid)) && $wxConfig->joined === 'Y') {
							$snsSiteId = $oMatter->siteid;
						} else {
							$snsSiteId = 'platform';
						}
					} else {
						$snsSiteId = $oMatter->siteid;
					}
					// 检查用户是否已经关注
					if ($snsUser = $oUser->sns->{$snsName}) {
						$modelSnsUser = $this->model('sns\\' . $snsName . '\fan');
						if ($modelSnsUser->isFollow($snsSiteId, $snsUser->openid)) {
							$bMatched = true;
							break;
						}
					}
				}
			}
			if (!$bMatched) {
				$result = '$mpfollow';
			}
		} else {
			$bMatched = true;
		}
		/* 内置页面 */
		if (!empty($result)) {
			switch ($result) {
			case '$memberschema':
				$aMemberSchemas = array();
				foreach ($oEntryRule->member as $schemaId) {
					$aMemberSchemas[] = $schemaId;
				}
				if ($bRedirect) {
					/*页面跳转*/
					$this->gotoMember($oMatter->siteid, $aMemberSchemas, $oUser->uid);
				} else {
					/*返回地址*/
					$this->gotoMember($oMatter->siteid, $aMemberSchemas, $oUser->uid, false);
				}
				break;
			case '$mpfollow':
				$snss = array();
				foreach ($oEntryRule->sns as $sns) {
					$snss[] = $sns;
				}
				if (in_array('wx', $snss)) {
					$this->snsFollow($oMatter->siteid, 'wx', $oMatter);
				} else if (in_array('qy', $snss)) {
					$this->snsFollow($oMatter->siteid, 'qy', $oMatter);
				} else if (in_array('yx', $snss)) {
					$this->snsFollow($oMatter->siteid, 'yx', $oMatter);
				}
				break;
			}
		}

		return [$bMatched, $result];
	}
	/**
	 * 跳转到用户认证页
	 */
	protected function gotoMember($siteId, $aMemberSchemas, $userid, $targetUrl = null) {
		is_string($aMemberSchemas) && $aMemberSchemas = explode(',', $aMemberSchemas);
		/**
		 * 如果不是注册用户，要求先进行认证
		 */
		if (count($aMemberSchemas) === 1) {
			$schema = $this->model('site\user\memberschema')->byId($aMemberSchemas[0], ['fields' => 'id,url']);
			strpos($schema->url, 'http') === false && $authUrl = 'http://' . APP_HTTP_HOST;
			$authUrl .= $schema->url;
			$authUrl .= "?site=$siteId";
			$authUrl .= "&schema=" . $aMemberSchemas[0];
		} else {
			/**
			 * 让用户选择通过那个认证接口进行认证
			 */
			$authUrl = 'http://' . APP_HTTP_HOST . '/rest/site/fe/user/memberschema';
			$authUrl .= "?site=$siteId";
			$authUrl .= "&schema=" . implode(',', $aMemberSchemas);
		}
		/**
		 * 返回身份认证页
		 */
		if ($targetUrl === false) {
			/**
			 * 直接返回认证地址
			 * todo angular无法自动执行初始化，所以只能返回URL，由前端加载页面
			 */
			$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
			header($protocol . ' 401 Unauthorized');
			die("$authUrl");
		} else {
			/**
			 * 跳转到认证接口
			 */
			if (empty($targetUrl)) {
				$targetUrl = $this->getRequestUrl();
			}
			/**
			 * 将跳转信息保存在cookie中
			 */
			$targetUrl = $this->model()->encrypt($targetUrl, 'ENCODE', $siteId);
			$this->mySetCookie("_{$siteId}_mauth_t", $targetUrl, time() + 300);
			$this->redirect($authUrl);
		}
	}
}