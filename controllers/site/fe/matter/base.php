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
	 * @param string $site
	 * @param object $app
	 */
	protected function requireSnsOAuth($oApp) {
		$oEntryRule = isset($oApp->entryRule) ? $oApp->entryRule : $oApp->entry_rule;
		if ($this->userAgent() === 'wx') {
			if (!empty($oEntryRule->sns->wx->entry)) {
				if (!isset($this->who->sns->wx)) {
					$modelWx = $this->model('sns\wx');
					if (($wxConfig = $modelWx->bySite($oApp->siteid)) && $wxConfig->joined === 'Y') {
						$this->snsOAuth($wxConfig, 'wx');
					} else if (($wxConfig = $modelWx->bySite('platform')) && $wxConfig->joined === 'Y') {
						$this->snsOAuth($wxConfig, 'wx');
					}
				}
			}
			if (!empty($oEntryRule->sns->qy->entry)) {
				if (!isset($this->who->sns->qy)) {
					if ($qyConfig = $this->model('sns\qy')->bySite($oApp->siteid)) {
						if ($qyConfig->joined === 'Y') {
							$this->snsOAuth($qyConfig, 'qy');
						}
					}
				}
			}
		} else if (!empty($oEntryRule->sns->yx->entry) && $this->userAgent() === 'yx') {
			if (!isset($this->who->sns->yx)) {
				if ($yxConfig = $this->model('sns\yx')->bySite($oApp->siteid)) {
					if ($yxConfig->joined === 'Y') {
						$this->snsOAuth($yxConfig, 'yx');
					}
				}
			}
		}

		return false;
	}
	/**
	 *
	 */
	protected function checkSnsEntryRule($oApp, $bRedirect) {
		$aResult = $this->enterAsSns($oApp);
		if (false === $aResult[0]) {
			$msg = '您没有关注公众号，不满足【' . $oApp->title . '】的参与规则，无法访问，请联系活动的组织者解决。';
			if (true === $bRedirect) {
				$oEntryRule = $oApp->entryRule;
				if (!empty($oEntryRule->sns->wx->entry)) {
					/* 通过邀请链接访问 */
					if (!empty($_GET['inviteToken'])) {
						$oApp->params = new \stdClass;
						$oApp->params->inviteToken = $_GET['inviteToken'];
					}
					$this->snsWxQrcodeFollow($oApp);
				} else if (!empty($oEntryRule->sns->qy->entry)) {
					$this->snsFollow($oApp->siteid, 'qy', $oApp);
				} else if (!empty($oEntryRule->sns->yx->entry)) {
					$this->snsFollow($oApp->siteid, 'yx', $oApp);
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
	protected function enterAsSns($oApp) {
		$oEntryRule = isset($oApp->entryRule) ? $oApp->entryRule : $oApp->entry_rule;
		$oUser = $this->who;
		$bFollowed = false;
		$oFollowedRule = null;
		foreach ($oEntryRule->sns as $snsName => $rule) {
			if (isset($oUser->sns->{$snsName})) {
				// 检查用户对应的公众号
				if ($snsName === 'wx') {
					$modelWx = $this->model('sns\wx');
					if (($wxConfig = $modelWx->bySite($oApp->siteid)) && $wxConfig->joined === 'Y') {
						$snsSiteId = $oApp->siteid;
					} else {
						$snsSiteId = 'platform';
					}
				} else {
					$snsSiteId = $oApp->siteid;
				}
				// 检查用户是否已经关注
				$snsUser = $oUser->sns->{$snsName};
				$modelSnsUser = $this->model('sns\\' . $snsName . '\fan');
				if ($modelSnsUser->isFollow($snsSiteId, $snsUser->openid)) {
					$bFollowed = true;
					$oFollowedRule = $rule;
					break;
				}
			}
		}

		return [$bFollowed, $oFollowedRule];
	}
	/**
	 * 限通讯录用户参与
	 */
	protected function enterAsMember($oApp) {
		$oEntryRule = isset($oApp->entryRule) ? $oApp->entryRule : $oApp->entry_rule;
		$oUser = $this->who;
		$bMatched = false;
		$bMatchedRule = null;

		foreach ($oEntryRule->member as $schemaId => $rule) {
			/* 检查用户的信息是否完整，是否已经通过审核 */
			$modelMem = $this->model('site\user\member');
			if (empty($oUser->unionid)) {
				$aMembers = $modelMem->byUser($oUser->uid, ['schemas' => $schemaId]);
				if (count($aMembers) === 1) {
					$oMember = $aMembers[0];
					if ($oMember->verified === 'Y') {
						$bMatched = true;
						$bMatchedRule = $rule;
						break;
					}
				}
			} else {
				$modelAcnt = $this->model('site\user\account');
				$aUnionUsers = $modelAcnt->byUnionid($oUser->unionid, ['siteid' => $oApp->siteid, 'fields' => 'uid']);
				foreach ($aUnionUsers as $oUnionUser) {
					$aMembers = $modelMem->byUser($oUnionUser->uid, ['schemas' => $schemaId]);
					if (count($aMembers) === 1) {
						$oMember = $aMembers[0];
						if ($oMember->verified === 'Y') {
							$bMatched = true;
							$bMatchedRule = $rule;
							break;
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
	 * 访问控制设置
	 *
	 * 检查当前用户是否为认证用户
	 * 检查当前用户是否在白名单中
	 *
	 * 如果用户没有认证，跳转到认证页
	 *
	 */
	protected function accessControl($siteId, $objId, $memberSchemas, $userid, &$obj, $targetUrl = null) {
		$model = $this->model();
		$siteId = $model->escape($siteId);
		$objId = $model->escape($objId);
		$aMemberSchemas = explode(',', $memberSchemas);
		$members = array();
		foreach ($aMemberSchemas as $memberSchema) {
			if (isset($this->who->members->{$memberSchema})) {
				$members[] = $this->who->members->{$memberSchema};
			}
		}
		if (empty($members)) {
			$members = $this->model('site\user\member')->byUser($userid, ['schemas' => $memberSchemas]);
		}
		//如果是企业号的用户访问
		if (isset($this->who->sns->qy) && empty($members)) {
			//根据openid查询粉丝表
			$openid = $this->who->sns->qy->openid;
			$p = array(
				'siteid,openid,nickname,mobile,email,sync_at',
				'xxt_site_qyfan',
				"siteid='$siteId' and openid='$openid' and subscribe_at > 0 and unsubscribe_at = 0 ",
			);
			$qySnsUser = $model->query_obj_ss($p);
			if ($qySnsUser) {
				$members['qy'] = $qySnsUser;
			}

		}
		if (empty($members)) {
			if ($this->userAgent() === 'wx') {
				if (isset($this->who->sns->wx)) {
					$openid = $this->who->sns->wx->openid;
				} else if (isset($this->who->sns->qy)) {
					$openid = $this->who->sns->qy->openid;
				}
			} else if ($this->userAgent() === 'yx') {
				if (isset($this->who->sns->yx)) {
					$openid = $this->who->sns->yx->openid;
				}
			}
		}
		if (empty($members)) {
			/**
			 * 如果不是认证用户，先进行认证
			 */
			$this->gotoMember($obj, $aMemberSchemas, $targetUrl);
		} else {
			$passed = false;
			//如果时从企业号进入的用户不需要认证
			if (isset($members['qy'])) {
				$passed = true;
			} else {
				foreach ($members as $member) {
					if ($this->canAccessObj($siteId, $objId, $member, $memberSchemas, $obj)) {
						/**
						 * 检查用户是否通过了验证
						 */
						$q = array(
							'verified',
							'xxt_site_member',
							"siteid='$siteId' and id='$member->id'",
						);
						if ('Y' !== $model->query_val_ss($q)) {
							$r = $this->model('site\user\memberschema')->getNotpassStatement($member->schema_id, $siteId);
							$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
							header($protocol . ' 401 Unauthorized');
							\TPL::assign('title', '访问控制未通过');
							\TPL::assign('body', $r);
							\TPL::output('error');
							exit;
						}
						$passed = true;
						break;
					}
				}
				!$passed && $this->gotoOutAcl($siteId, $member->schema_id);

				return $member;
			}
		}
	}
	/**
	 *
	 */
	private function gotoOutAcl($mpid, $authid) {
		$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
		header($protocol . ' 401 Unauthorized');
		$r = $this->model('user/authapi')->getAclStatement($authid, $mpid);
		TPL::assign('title', '访问控制未通过');
		TPL::assign('body', $r);
		TPL::output('error');
		exit;
	}
}