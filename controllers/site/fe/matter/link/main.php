<?php
namespace site\fe\matter\link;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 链接
 */
class main extends \site\fe\matter\base {
	/**
	 *
	 */
	private function _checkInviteToken($userid, $oMatter) {
		if (empty($_GET['inviteToken'])) {
			die('参数不完整，未通过邀请访问控制');
		}
		$inviteToken = $_GET['inviteToken'];

		$rst = $this->model('invite\token')->checkToken($inviteToken, $userid, $oMatter);
		if (false === $rst[0]) {
			die($rst[1]);
		}

		return true;
	}
	/**
	 *
	 */
	public function index_action($site, $id) {
		$oLink = $this->model('matter\link')->byIdWithParams($id);

		$oInvitee = new \stdClass;
		$oInvitee->id = $oLink->siteid;
		$oInvitee->type = 'S';
		$oInvite = $this->model('invite')->byMatter($oLink, $oInvitee, ['fields' => 'id,code,expire_at,state']);
		if ($oInvite && $oInvite->state === '1') {
			$this->_checkInviteToken($this->who->uid, $oLink);
		}

		if (!$this->afterSnsOAuth()) {
			/* 检查是否需要第三方社交帐号OAuth */
			$this->_requireSnsOAuth($site);
		}

		$result = $this->checkEntryRule($oLink, true);
		if ($result[0] === false) {
			$this->outputInfo($result[1]);
		}

		switch ($oLink->urlsrc) {
		case 0: // 外部链接
			if ($oLink->embedded === 'Y') {
				\TPL::assign('title', $oLink->title);
				\TPL::output('/site/fe/matter/link/main');
				exit;
			}
			/* 页面跳转 */
			$url = $oLink->url;
			if (preg_match('/^(http:|https:)/', $url) === 0) {
				$url = 'http://' . $url;
			}
			if ($oLink->method == 'GET') {
				if (isset($oLink->params)) {
					$url .= (strpos($url, '?') === false) ? '?' : '&';
					$url .= $this->_spliceParams($oLink->siteid, $oLink->params);
				}
				$this->redirect($url);
			} elseif ($oLink->method == 'POST') {
				if (isset($oLink->params)) {
					$posted = $this->_spliceParams($oLink->siteid, $oLink->params);
				}
				$ch = curl_init(); //初始化curl
				curl_setopt($ch, CURLOPT_URL, $url); //设置链接
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //设置是否返回信息
				//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_REFERER, 1);
				curl_setopt($ch, CURLOPT_HEADER, 1); //设置返回的信息是否包含http头
				curl_setopt($ch, CURLOPT_POST, 1); //设置为POST方式
				if (!empty($posted)) {
					$header = array("Content-type: application/x-www-form-urlencoded");
					curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $posted);
				}
				$response = curl_exec($ch);
				if (curl_errno($ch)) {
					echo curl_error($ch);
				} elseif (curl_getinfo($ch, CURLINFO_HTTP_CODE) == '302') {
					/**
					 * 页面跳转
					 */
					$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
					$header = substr($response, 0, $headerSize);
					$matched = array();
					if (preg_match('/Location:(.*)\r\n/i', $header, $matched)) {
						$location = $matched[1];
						header("Location: $location");
					} else {
						echo 'Parse header error!';
					}
				} else {
					/**
					 * 返回内容
					 */
					$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
					$body = substr($response, $headerSize);
					echo $body;
				}
				curl_close($ch);
				exit;
			}
			break;
		case 1: // 多图文
			//require_once dirname(__FILE__) . '/page_news.php';
			//$page = new page_news((int) $oLink->url, $openid);
			break;
		case 2: // 频道
			$channelUrl = $this->model('matter\channel')->getEntryUrl($oLink->siteid, (int) $oLink->url);
			$this->redirect($channelUrl);
			break;
		}
	}
	/**
	 * 返回链接定义
	 */
	public function get_action($id) {
		$oLink = $this->model('matter\link')->byIdWithParams($id);
		if (false === $oLink) {
			return new \ObjectNotFoundError();
		}

		$url = $oLink->url;
		if (preg_match('/^(http:|https:)/', $url) === 0) {
			$url = 'http://' . $url;
		}
		if (isset($oLink->params)) {
			$url .= (strpos($url, '?') === false) ? '?' : '&';
			$url .= $this->_spliceParams($oLink->siteid, $oLink->params);
		}
		$oLink->fullUrl = $url;

		$oInvitee = new \stdClass;
		$oInvitee->id = $oLink->siteid;
		$oInvitee->type = 'S';
		$oInvite = $this->model('invite')->byMatter($oLink, $oInvitee, ['fields' => 'id,code,expire_at,state']);
		if ($oInvite && $oInvite->state === '1') {
			$oLink->invite = $oInvite;
		}

		/* 当前访问用户的基本信息 */
		$oUser = $this->who;

		/* 补充联系人信息，是在什么情况下都需要补充吗？ 应该在限制了联系人访问的情况下，而且应该只返回相关的 */
		$modelMem = $this->model('site\user\member');
		if (empty($oUser->unionid)) {
			$aMembers = $modelMem->byUser($oUser->uid);
			if (count($aMembers)) {
				!isset($oUser->members) && $oUser->members = new \stdClass;
				foreach ($aMembers as $oMember) {
					$oUser->members->{$oMember->schema_id} = $oMember;
				}
			}
		} else {
			$modelAcnt = $this->model('site\user\account');
			$aUnionUsers = $modelAcnt->byUnionid($oUser->unionid, ['siteid' => $oLink->siteid, 'fields' => 'uid']);
			foreach ($aUnionUsers as $oUnionUser) {
				$aMembers = $modelMem->byUser($oUnionUser->uid);
				if (count($aMembers)) {
					!isset($oUser->members) && $oUser->members = new \stdClass;
					foreach ($aMembers as $oMember) {
						$oUser->members->{$oMember->schema_id} = $oMember;
					}
				}
			}
		}

		$data = [];
		$data['link'] = $oLink;
		$data['user'] = $oUser;

		return new \ResponseData($data);
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
	/**
	 *
	 */
	private function _getSnsOpenid() {
		if ($this->userAgent() === 'wx') {
			if (isset($this->who->sns->wx)) {
				return $this->who->sns->wx->openid;
			}
			if (isset($this->who->sns->qy)) {
				return $this->who->sns->qy->openid;
			}
		} else if ($this->userAgent() === 'yx') {
			if (isset($this->who->sns->yx)) {
				return $this->who->sns->yx->openid;
			}
		}
		return '';
	}
	/**
	 * 拼接URL中的参数
	 */
	private function _spliceParams($siteId, &$params) {
		$pairs = array();
		foreach ($params as $p) {
			switch ($p->pvalue) {
			case '{{site}}':
				$v = $siteId;
				break;
			case '{{openid}}':
				$v = $this->_getSnsOpenid();
				break;
			default:
				$v = $p->pvalue;
			}
			$pairs[] = "$p->pname=$v";
		}
		$spliced = implode('&', $pairs);

		return $spliced;
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
	private function checkEntryRule($oMatter, $bRedirect = false) {
		$oUser = $this->who;
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
}