<?php
namespace site\fe\matter;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 *
 */
class base extends \site\fe\base {
	/**
	 * 打开页面时设置客户端的标识，用户在后续的操作中判断调用是否来源于之前的客户端
	 */
	protected function _setAgentEnter($matterId) {
		/* user */
		$user = $this->who;
		/* set key */
		$_SESSION['AGENTENTER_' . $matterId . '_' . $user->uid] = time();
	}
	/**
	 * 判断调用是否来源于之前的客户端
	 */
	protected function _isAgentEnter($matterId) {
		/* user */
		$user = $this->who;
		/* set key */
		return isset($_SESSION['AGENTENTER_' . $matterId . '_' . $user->uid]);
	}
	/**
	 * 检查是否需要第三方社交帐号认证
	 * 检查条件：
	 * 0、应用是否设置了需要认证
	 * 1、团队是否绑定了第三方社交帐号认证
	 * 2、平台是否绑定了第三方社交帐号认证
	 * 3、用户客户端是否可以发起认证
	 *
	 * @param object $oApp
	 */
	protected function requireSnsOAuth($oMatter) {
		if (empty($oMatter->entryRule)) {
			return false;
		}
		$oEntryRule = $oMatter->entryRule;
		if (empty($oEntryRule->scope)) {
			return false;
		}
		$oScope = $oEntryRule->scope;

		$userAgent = $this->userAgent(); // 客户端类型
		if (!in_array($userAgent, ['wx', 'yx'])) {
			return false;
		}

		$aRequireSns = []; // 需要进行OAuth的社交平台
		/**
		 * 如果限通讯录用户访问，只要有一个通讯录限制为公众号关注用户，就要做oauth
		 */
		if ($userAgent === 'wx' && isset($oScope->member) && $oScope->member === 'Y' && isset($oEntryRule->member)) {
			$modelMs = $this->model('site\user\memberschema');
			foreach ($oEntryRule->member as $mschemaId => $oRule) {
				$oMschema = $modelMs->byId($mschemaId, ['fields' => 'is_wx_fan', 'cascaded' => 'N']);
				if ($oMschema && $oMschema->is_wx_fan === 'Y') {
					if (!isset($this->who->sns->wx)) {
						$aRequireSns['wx'] = true;
					}
					break;
				}
			}
		}
		/**
		 * 如果限制了社交平台用户访问
		 */
		if (isset($oScope->sns) && $oScope->sns === 'Y' && isset($oEntryRule->sns)) {
			if ($userAgent === 'wx') {
				if (!empty($oEntryRule->sns->wx->entry)) {
					if (!isset($this->who->sns->wx)) {
						$aRequireSns['wx'] = true;
					}
				}
				if (!empty($oEntryRule->sns->qy->entry)) {
					if (!isset($this->who->sns->qy)) {
						$aRequireSns['qy'] = true;
					}
				}
			}
			if ($userAgent === 'yx' && !empty($oEntryRule->sns->yx->entry)) {
				if (!isset($this->who->sns->yx)) {
					$aRequireSns['yx'] = true;
				}
			}
		}

		if (empty($aRequireSns)) {
			/* 没有需要验证的社交平台*/
			return false;
		}

		if (!empty($aRequireSns['wx'])) {
			$modelWx = $this->model('sns\wx');
			if (($wxConfig = $modelWx->bySite($oMatter->siteid)) && $wxConfig->joined === 'Y') {
				$this->snsOAuth($wxConfig, 'wx');
			} else if (($wxConfig = $modelWx->bySite('platform')) && $wxConfig->joined === 'Y') {
				$this->snsOAuth($wxConfig, 'wx');
			}
		}
		if (!empty($aRequireSns['qy'])) {
			if ($qyConfig = $this->model('sns\qy')->bySite($oMatter->siteid)) {
				if ($qyConfig->joined === 'Y') {
					$this->snsOAuth($qyConfig, 'qy');
				}
			}
		}
		if (!empty($aRequireSns['yx'])) {
			if ($yxConfig = $this->model('sns\yx')->bySite($oMatter->siteid)) {
				if ($yxConfig->joined === 'Y') {
					$this->snsOAuth($yxConfig, 'yx');
				}
			}
		}

		return false;
	}
	/**
	 * 检查通信录作为进入规则
	 */
	protected function checkMemberEntryRule($oMatter, $bRedirect) {
		$oEntryRule = $oMatter->entryRule;
		if ($this->getDeepValue($oEntryRule, 'optional.member') !== 'Y') {
			if (!isset($oEntryRule->member)) {
				$msg = '需要填写通讯录信息，请联系活动的组织者解决。';
			} else {
				$aResult = $this->enterAsMember($oMatter);
				/**
				 * 限通讯录用户访问
				 * 如果指定的任何一个通讯录要求用户关注公众号，但是用户还没有关注，那么就要求用户先关注公众号，再填写通讯录
				 */
				if (false === $aResult[0]) {
					if (true === $bRedirect) {
						$aMemberSchemaIds = [];
						$modelMs = $this->model('site\user\memberschema');
						foreach ($oEntryRule->member as $mschemaId => $oRule) {
							$oMschema = $modelMs->byId($mschemaId, ['fields' => 'is_wx_fan', 'cascaded' => 'N']);
							if ($oMschema->is_wx_fan === 'Y') {
								$oApp2 = clone $oMatter;
								$oApp2->entryRule = new \stdClass;
								$oApp2->entryRule->sns = (object) ['wx' => (object) ['entry' => 'Y']];
								$aResult = $this->checkSnsEntryRule($oApp2, $bRedirect);
								if (false === $aResult[0]) {
									return $aResult;
								}
							}
							$aMemberSchemaIds[] = $mschemaId;
						}
						$this->gotoMember($oMatter, $aMemberSchemaIds);
					} else {
						$msg = '您【ID:' . $this->who->uid . '】没有填写通讯录信息，不满足【' . $oMatter->title . '】的参与规则，无法访问，请联系活动的组织者解决。';
					}
				}
			}
			if (isset($msg)) {
				if (true === $bRedirect) {
					$oSite = $this->model('site')->byId($oMatter->siteid);
					$this->outputInfo($msg, $oSite);
				} else {
					return [false, $msg];
				}
			}
		}

		return [true];
	}
	/**
	 * 检查分组活动作为进入规则
	 */
	protected function checkGroupEntryRule($oMatter, $bRedirect) {
		$oEntryRule = $oMatter->entryRule;
		if ($this->getDeepValue($oEntryRule, 'optional.group') !== 'Y') {
			$oUser = $this->who;
			/* 限分组用户访问 */
			if (empty($oEntryRule->group->id)) {
				$msg = '没有指定作为进入规则的分组活动，请联系活动的组织者解决。';
			} else {
				$oGroupApp = $this->model('matter\group')->byId($oEntryRule->group->id, ['fields' => 'id,state,title']);
				if (false === $oGroupApp || $oGroupApp->state !== '1') {
					$msg = '【' . $oMatter->title . '】指定的分组活动不可访问，请联系活动的组织者解决。';
				} else {
					$bMatched = false;
					$oGroupUsr = $this->model('matter\group\record')->byUser($oGroupApp, $oUser->uid, ['fields' => 'team_id,team_title']);
					if (count($oGroupUsr)) {
						$oGroupUsr = $oGroupUsr[0];
						if (isset($oEntryRule->group->team->id)) {
							if ($oGroupUsr->team_id === $oEntryRule->group->team->id) {
								$bMatched = true;
							}
						} else {
							$bMatched = true;
						}
					}
					if (false === $bMatched) {
						$msg = '您【ID:' . $oUser->uid . '】目前的分组，不满足【' . $oMatter->title . '】的参与规则，无法访问，请联系活动的组织者解决。';
					}
				}
			}
			if (isset($msg)) {
				if (true === $bRedirect) {
					$oSite = $this->model('site')->byId($oMatter->siteid);
					$this->outputInfo($msg, $oSite);
				} else {
					return [false, $msg];
				}
			}
		}

		return [true];
	}
	/**
	 * 检查记录活动作为进入规则
	 */
	protected function checkEnrollEntryRule($oMatter, $bRedirect) {
		$oEntryRule = $oMatter->entryRule;
		if ($this->getDeepValue($oEntryRule, 'optional.enroll') !== 'Y') {
			$oUser = $this->who;
			if (empty($oEntryRule->enroll->id)) {
				$msg = '没有指定作为进入规则的记录活动，请联系活动的组织者解决。';
			} else {
				$oEnlApp = $this->model('matter\enroll')->byId($oEntryRule->enroll->id, ['fields' => 'id,state,title']);
				if (false === $oEnlApp && $oEnlApp->state !== '1') {
					$msg = '指定作为进入规则的记录活动不存在，请联系活动的组织者解决。';
				}
			}
			$oEnlUsr = $this->model('matter\enroll\user')->byId($oEnlApp, $oUser->uid, ['fields' => 'enroll_num']);
			if (false === $oEnlUsr || $oEnlUsr->enroll_num <= 0) {
				$msg = '您的用户信息，不满足【' . $oMatter->title . '】的参与规则，请联系活动的组织者解决。';
			}
			if (isset($msg)) {
				if (true === $bRedirect) {
					$oSite = $this->model('site')->byId($oMatter->siteid);
					$this->outputInfo($msg, $oSite);
				} else {
					return [false, $msg];
				}
			}
		}

		return [true];
	}
	/**
	 * 检查是否已经关注公众号
	 */
	protected function checkSnsEntryRule($oMatter, $bRedirect) {
		$aResult = $this->enterAsSns($oMatter);
		if (false === $aResult[0]) {
			$msg = '您没有关注公众号，不满足【' . $oMatter->title . '】的参与规则，无法访问，请联系活动的组织者解决。';
			if (true === $bRedirect) {
				$oEntryRule = $oMatter->entryRule;
				if (!empty($oEntryRule->sns->wx->entry)) {
					/* 通过邀请链接访问 */
					if (!empty($_GET['inviteToken'])) {
						$oMatter->params = new \stdClass;
						$oMatter->params->inviteToken = $_GET['inviteToken'];
					}
					$this->snsWxQrcodeFollow($oMatter);
				} else if (!empty($oEntryRule->sns->qy->entry)) {
					$this->snsFollow($oMatter->siteid, 'qy', $oMatter);
				} else if (!empty($oEntryRule->sns->yx->entry)) {
					$this->snsFollow($oMatter->siteid, 'yx', $oMatter);
				} else {
					$this->outputInfo($msg);
				}
			} else {
				return [false, $msg];
			}
		}

		return [true];
	}
	/**
	 * 限社交网站用户参与
	 */
	protected function enterAsSns($oMatter) {
		$oEntryRule = $oMatter->entryRule;
		$bFollowed = false;
		$oFollowedRule = null;

		if (isset($oEntryRule->sns)) {
			$oUser = $this->who;

			/* 检查用户是否已经关注公众号 */
			$fnCheckSnsFollow = function ($snsName, $matterSiteId, $openid) {
				if ($snsName === 'wx') {
					$modelWx = $this->model('sns\wx');
					if (($wxConfig = $modelWx->bySite($matterSiteId)) && $wxConfig->joined === 'Y') {
						$snsSiteId = $matterSiteId;
					} else {
						$snsSiteId = 'platform';
					}
				} else {
					$snsSiteId = $matterSiteId;
				}
				// 检查用户是否已经关注
				$modelSnsUser = $this->model('sns\\' . $snsName . '\fan');
				if ($modelSnsUser->isFollow($snsSiteId, $openid)) {
					return true;
				}

				return false;
			};

			foreach ($oEntryRule->sns as $snsName => $rule) {
				if (isset($oUser->sns->{$snsName})) {
					/* 缓存的信息 */
					$snsUser = $oUser->sns->{$snsName};
					if ($fnCheckSnsFollow($snsName, $oMatter->siteid, $snsUser->openid)) {
						$bFollowed = true;
						$oFollowedRule = $rule;
						break;
					}
				} else {
					$modelAcnt = $this->model('site\user\account');
					$propSnsOpenid = $snsName . '_openid';
					if (empty($oUser->unionid)) {
						/* 当前站点用户绑定的信息 */
						$oSiteUser = $modelAcnt->byId($oUser->uid, ['fields' => $propSnsOpenid]);
						if ($oSiteUser && $fnCheckSnsFollow($snsName, $oMatter->siteid, $oSiteUser->{$propSnsOpenid})) {
							$bFollowed = true;
							$oFollowedRule = $rule;
							break;
						}
					} else {
						/* 当前注册用户绑定的信息 */
						$aSiteUsers = $modelAcnt->byUnionid($oUser->unionid, ['siteid' => $oMatter->siteid, 'fields' => $propSnsOpenid]);
						foreach ($aSiteUsers as $oSiteUser) {
							$oSiteUser = $modelAcnt->byId($oUser->uid, ['fields' => $propSnsOpenid]);
							if ($oSiteUser && $fnCheckSnsFollow($snsName, $oMatter->siteid, $oSiteUser->{$propSnsOpenid})) {
								$bFollowed = true;
								$oFollowedRule = $rule;
								break;
							}
						}
						if ($bFollowed) {
							break;
						}
					}
				}
			}
		}

		return [$bFollowed, $oFollowedRule];
	}
	/**
	 * 限通讯录用户参与
	 * 1、找到匹配的通讯录用户
	 * 2、找到的用户是通过审核的状态
	 * 3、如果通讯录限制了关注公众号，还要检查找到的用户是否关注了公众号
	 */
	protected function enterAsMember($oApp) {
		if (!isset($oApp->entryRule->member)) {
			return [false];
		}

		$oEntryRule = $oApp->entryRule;
		$oUser = $this->who;
		$bMatched = false;
		$bMatchedRule = null;

		/* 检查用户是否已经关注公众号 */
		$fnCheckSnsFollow = function ($mschemaId, $oOriginalMatter) {
			$bPassed = true;
			$modelMs = $this->model('site\user\memberschema');
			$oMschema = $modelMs->byId($mschemaId, ['fields' => 'is_wx_fan', 'cascaded' => 'N']);
			if ($oMschema->is_wx_fan === 'Y') {
				$oApp2 = clone $oOriginalMatter;
				$oApp2->entryRule = new \stdClass;
				$oApp2->entryRule->sns = (object) ['wx' => (object) ['entry' => 'Y']];
				$aResult = $this->enterAsSns($oApp2);
				if (false === $aResult[0]) {
					$bPassed = false;
				}
			}

			return $bPassed;
		};

		foreach ($oEntryRule->member as $schemaId => $rule) {
			/* 检查用户的信息是否完整，是否已经通过审核 */
			$modelMem = $this->model('site\user\member');
			$modelAcnt = $this->model('site\user\account');
			if (empty($oUser->unionid)) {
				$oSiteUser = $modelAcnt->byId($oUser->uid, ['fields' => 'unionid']);
				if ($oSiteUser && !empty($oSiteUser->unionid)) {
					$unionid = $oSiteUser->unionid;
				}
			} else {
				$unionid = $oUser->unionid;
			}
			if (empty($unionid)) {
				$aMembers = $modelMem->byUser($oUser->uid, ['schemas' => $schemaId]);
				if (count($aMembers) === 1) {
					$oMember = $aMembers[0];
					if ($oMember->verified === 'Y') {
						if ($fnCheckSnsFollow($schemaId, $oApp)) {
							$bMatched = true;
							$bMatchedRule = $rule;
							break;
						}
					}
				}
			} else {
				$aUnionUsers = $modelAcnt->byUnionid($unionid, ['siteid' => $oApp->siteid, 'fields' => 'uid']);
				foreach ($aUnionUsers as $oUnionUser) {
					$aMembers = $modelMem->byUser($oUnionUser->uid, ['schemas' => $schemaId]);
					if (count($aMembers) === 1) {
						$oMember = $aMembers[0];
						if ($oMember->verified === 'Y') {
							if ($fnCheckSnsFollow($schemaId, $oApp)) {
								$bMatched = true;
								$bMatchedRule = $rule;
								break;
							}
						}
					}
				}
				if ($bMatched) {
					break;
				}
			}
		}

		return [$bMatched, $bMatchedRule];
	}
	/**
	 * 跳转到素材微信场景二维码关注页面
	 */
	protected function snsWxQrcodeFollow($oApp) {
		$rst = $this->model('sns\wx\call\qrcode')->createOneOff($oApp->siteid, $oApp);
		if ($rst[0] === false) {
			$this->snsFollow($oApp->siteid, 'wx', $oApp);
		} else {
			$sceneId = $rst[1]->scene_id;
			$this->snsFollow($oApp->siteid, 'wx', false, $sceneId);
		}

		return $rst[0];
	}
	/**
	 * 跳转到通讯录认证页
	 */
	protected function gotoMember($oMatter, $aMemberSchemas, $targetUrl = null) {
		$siteId = $oMatter->siteid;
		is_string($aMemberSchemas) && $aMemberSchemas = explode(',', $aMemberSchemas);
		if (count($aMemberSchemas) === 1) {
			$schema = $this->model('site\user\memberschema')->byId($aMemberSchemas[0], ['fields' => 'id,url']);
			strpos($schema->url, 'http') === false && $authUrl = APP_PROTOCOL . APP_HTTP_HOST;
			$authUrl .= $schema->url;
			$authUrl .= "?site=$siteId";
			$authUrl .= "&schema=" . $aMemberSchemas[0];
		} else {
			/**
			 * 让用户选择通过那个认证接口进行认证
			 */
			$authUrl = APP_PROTOCOL . APP_HTTP_HOST . '/rest/site/fe/user/memberschema';
			$authUrl .= "?site=$siteId";
			$authUrl .= "&schema=" . implode(',', $aMemberSchemas);
		}
		if (!empty($oMatter->type) && !empty($oMatter->id)) {
			$authUrl .= '&matter=' . $oMatter->type . ',' . $oMatter->id;
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
	/**
	 * 跳转到用户登陆注册页
	 */
	protected function gotoAccess() {
		$originUrl = $this->getRequestUrl();
		// 对url加密避免在浏览器地址栏上直接显示
		$originUrl = $this->model()->encrypt($originUrl, 'ENCODE', 'originUrl');
		$authUrl = '/rest/site/fe/user/access';
		$authUrl .= '?originUrl=' . $originUrl . '&urlEncryptKey=originUrl';

		$this->redirect($authUrl);
	}
	/**
	 * 检查参与规则
	 *
	 * @param object $oMatter
	 * @param boolean $bRedirect
	 * @param object $oUser
	 *
	 */
	protected function checkEntryRule($oMatter, $bRedirect = false, $oUser = null) {
		if (!isset($oMatter->entryRule->scope)) {
			return [true];
		}
		$oUser = isset($oUser) ? $oUser : $this->who;
		$oEntryRule = $oMatter->entryRule;
		$oScope = $oEntryRule->scope;

		if (isset($oScope->register) && $oScope->register === 'Y') {
			$checkRegister = $this->checkRegisterEntryRule($oUser);
			if ($checkRegister[0] === false) {
				if (true === $bRedirect) {
					$this->gotoAccess();
				} else {
					$msg = '未检测到您的注册信息，不满足【' . $oMatter->title . '】的参与规则，请登陆后再尝试操作。';
					return [false, $msg];
				}
			}
		}
		foreach (['member', 'sns', 'group', 'enroll'] as $item) {
			if (isset($oScope->{$item}) && $oScope->{$item} === 'Y') {
				$aCheckResult = $this->{'check' . ucfirst($item) . 'EntryRule'}($oMatter, $bRedirect);
				if (false === $aCheckResult[0]) {
					return $aCheckResult;
				}
			}
		}

		return [true];
	}
	/**
	 * 检查用户是否是注册用户
	 */
	protected function checkRegisterEntryRule($oUser) {
		$bCheckRegister = false;

		if (empty($oUser->unionid)) {
			$modelAct = $this->model('site\user\account');
			$getUserRegisterInfo = function ($unionid) use ($modelAct) {
				$q = [
					'count(uid)',
					'account',
					"uid = '" . $modelAct->escape($unionid) . "' and forbidden = 0",
				];
				$val = (int) $modelAct->query_val_ss($q);
				return $val;
			};

			$siteUser = $modelAct->byId($oUser->uid, ['fields' => 'unionid']);
			if ($siteUser && !empty($siteUser->unionid)) {
				$val = $getUserRegisterInfo($siteUser->unionid);
				if ($val > 0) {
					$bCheckRegister = true;
				}
			}
		} else {
			$bCheckRegister = true;
		}

		$result = [$bCheckRegister];

		return $result;
	}
}