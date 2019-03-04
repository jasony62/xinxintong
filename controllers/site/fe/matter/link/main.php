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
			return [false, '参数不完整，未通过邀请访问控制'];
		}
		$inviteToken = $_GET['inviteToken'];

		$rst = $this->model('invite\token')->checkToken($inviteToken, $userid, $oMatter);

		return $rst;
	}
	/**
	 *
	 */
	public function index_action($site, $id) {
		$oLink = $this->model('matter\link')->byIdWithParams($id);

		$modelInvite = $this->model('invite');
		$oInvitee = new \stdClass;
		$oInvitee->id = $oLink->siteid;
		$oInvitee->type = 'S';
		$passInvite = false; // 是否通过邀请
		$bychannelInvite = false; // 是否有频道开启了邀请
		if (!empty($oLink->channels)) {
			foreach ($oLink->channels as $channel) {
				$oInvite = $modelInvite->byMatter($channel, $oInvitee, ['fields' => 'id,code,expire_at,state']);
				if ($oInvite && $oInvite->state === '1') {
					$bychannelInvite = true;
					$rst = $this->_checkInviteToken($this->who->uid, $channel);
					if ($rst[0]) {
						$passInvite = true;
						break;
					} else {
						// 获取频道的邀请链接 如果有多个频道时默认最后一个频道？？
						$passInviteUrl = $modelInvite->getEntryUrl($oInvite);
					}
				}
			}
		}
		if (!$passInvite) {
			$oInvite = $modelInvite->byMatter($oLink, $oInvitee, ['fields' => 'id,code,expire_at,state']);
			if ($oInvite && $oInvite->state === '1') {
				$rst = $this->_checkInviteToken($this->who->uid, $oLink);
				if ($rst[0]) {
					$passInvite = true;
				} else {
					$passInviteUrl = $modelInvite->getEntryUrl($oInvite);
				}
			} else {
				// 如果都没有开启邀请则通过
				if (!$bychannelInvite) {
					$passInvite = true;
				}
			}
		}
		if (!$passInvite) {
			$this->redirect($passInviteUrl);
		}
		if (!$this->afterSnsOAuth()) {
			/* 检查是否需要第三方社交帐号OAuth */
			if ($oLink->urlsrc == 0 && $oLink->embedded === 'Y' && (strpos($oLink->url, 'https') === false)) {
				$callbackUrl = 'http://' . APP_HTTP_HOST . $_SERVER['REQUEST_URI'];
			} else {
				$callbackUrl = APP_PROTOCOL . APP_HTTP_HOST . $_SERVER['REQUEST_URI'];
			}
			$this->_requireSnsOAuth($site, $callbackUrl);
		}

		$this->checkEntryRule($oLink, true);

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
		$modelLink = $this->model('matter\link');
		$oLink = $modelLink->byIdWithParams($id);
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

		/* 链接所属的频道 */
		$oLink->channels = $this->model('matter\channel')->byMatter($oLink->id, 'link', ['public_visible' => 'Y']);
		if (count($oLink->channels) && !isset($oLink->config->nav->app)) {
			$aNavApps = [];
			foreach ($oLink->channels as $oChannel) {
				if (!empty($oChannel->config->nav->app)) {
					$aNavApps = array_merge($aNavApps, $oChannel->config->nav->app);
				}
			}
			if (!isset($oLink->config->nav)) {
				$oLink->config->nav = new \stdClass;
			}
			$oLink->config->nav->app = $aNavApps;
		}
		// 附件
		$oLink->attachments = $modelLink->query_objs_ss(
			[
				'*',
				'xxt_matter_attachment',
				['matter_id' => $id, 'matter_type' => 'link'],
			]
		);

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
	private function _requireSnsOAuth($siteid, $callbackUrl = '') {
		if ($this->userAgent() === 'wx') {
			if (!isset($this->who->sns->wx)) {
				$modelWx = $this->model('sns\wx');
				if (($wxConfig = $modelWx->bySite($siteid)) && $wxConfig->joined === 'Y') {
					$this->snsOAuth($wxConfig, 'wx', $callbackUrl);
				} else if (($wxConfig = $modelWx->bySite('platform')) && $wxConfig->joined === 'Y') {
					$this->snsOAuth($wxConfig, 'wx', $callbackUrl);
				}
			}
			if (!isset($this->who->sns->qy)) {
				if ($qyConfig = $this->model('sns\qy')->bySite($siteid)) {
					if ($qyConfig->joined === 'Y') {
						$this->snsOAuth($qyConfig, 'qy', $callbackUrl);
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
	 * 下载附件
	 */
	public function attachmentGet_action($site, $linkId, $attachmentid) {
		if (empty($site) || empty($linkId) || empty($attachmentid)) {
			die('参数不完整');
		}

		$user = $this->who;
		/**
		 * 访问控制
		 */
		$modelArticle = $this->model('matter\link');
		$oLink = $modelArticle->byId($linkId);
		if ($oLink === false || $oLink->state !== '1') {
			die('指定的活动不存在，请检查参数是否正确');
		}
		/**
		 * 记录日志
		 */
		$this->attachmentGet($oLink, $attachmentid);
	}
}