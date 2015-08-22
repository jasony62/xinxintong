<?php
include_once dirname(__FILE__) . '/xxt_base.php';
/**
 * member
 */
class member_base extends xxt_base {
	/**
	 *
	 */
	protected function getCookieKey($mpid) {
		$q = array('creater', 'xxt_mpaccount', "mpid='$mpid'");
		if (!($mpCreater = $this->model()->query_val_ss($q))) {
			die('invalid parameters.');
		}

		return md5($mpid . $mpCreater);
	}
	/**
	 * 设置代表用户认真身份的cookie
	 */
	protected function setCookie4Member($mpid, $authid, $mid) {
		$authapi = $this->model('user/authapi')->byId($authid, 'validity');
		$key = $this->getCookieKey($mpid);
		$encoded = $this->model()->encrypt($mid, 'ENCODE', $key);
		$expireAt = $authapi->validity == 0 ? null : time() + (86400 * (int) $authapi->validity);
		$this->mySetCookie("_{$mpid}_{$authid}_member", $encoded, $expireAt);
	}
	/**
	 * 判断是否为注册用户的条件是
	 *
	 * 1、cookie中记录了mid
	 * 2、mid在注册用户表中存在，且处于可用状态
	 *
	 * return $mid member's id
	 */
	protected function getCookieMember($mpid, $aAuthapis = array()) {
		empty($aAuthapis) && die('没有指定认证接口');

		$members = array();
		$cookiekey = $this->getCookieKey($mpid);
		foreach ($aAuthapis as $authid) {
			if ($encoded = $this->myGetCookie("_{$mpid}_{$authid}_member")) {
				if ($mid = $this->model()->encrypt($encoded, 'DECODE', $cookiekey)) {
					/**
					 * 检查数据库中是否有匹配的记录
					 */
					$q = array(
						'*',
						'xxt_member',
						"authapi_id=$authid and mid='$mid' and forbidden='N'",
					);
					if ($member = $this->model()->query_obj_ss($q)) {
						$members[] = $member;
					}

				}
			}
		}
		return $members;
	}
	/**
	 *
	 */
	protected function getMembersByMpid($mpid, $aAuthapis = null, $openid = null) {
		if (empty($aAuthapis)) {
			$authapis = $this->model('user/authapi')->byMpid($mpid, 'Y', 'N');
			if (empty($authapis)) {
				return false;
			}

			foreach ($authapis as $k => $v) {
				$aAuthapis[] = $v->authid;
			}

		} else if (is_string($aAuthapis)) {
			$aAuthapis = explode(',', $aAuthapis);
		}
		if (!empty($openid)) {
			$authids = implode(',', $aAuthapis);
			/**
			 * 优先根据openid判断用户的身份
			 */
			$q = array(
				'*',
				'xxt_member m',
				"m.forbidden='N' and m.authapi_id in($authids) and m.openid='$openid'",
			);
			$members = $this->model()->query_objs_ss($q);
		} else {
			$members = $this->getCookieMember($mpid, $aAuthapis);
		}

		return $members;
	}
	/**
	 * 获得当前访问用户的信息
	 *
	 * $mpid
	 * $act
	 * $openid
	 * $matter
	 * $checkAccessControl
	 */
	protected function &getUser($mpid, $options = array()) {
		$sAuthapis = isset($options['authapis']) ? $options['authapis'] : null;
		$openid = isset($options['openid']) ? $options['openid'] : '';
		$matter = isset($options['matter']) ? $options['matter'] : null;
		// return value
		$user = new \stdClass;
		/**
		 * 获得当前用户的访客id
		 */
		$vid = $this->getVisitorId($mpid);
		$user->vid = $vid;
		/**
		 * 获得当前用户的openid
		 */
		empty($openid) && $openid = $this->getCookieOAuthUser($mpid);
		if (!$this->getClientSrc() && empty($openid) && !empty($sAuthapis)) {
			/**
			 * 如果是非微信，易信客户端访问，无法通过OAuth获得openid，检查是否可以通过cookie中的认证用户信息获得openid
			 */
			$aAuthapis = explode(',', $sAuthapis);
			$members = $this->getCookieMember($mpid, $aAuthapis);
			!empty($members) && $openid = $members[0]->openid;
		}
		$user->openid = $openid;
		/**
		 * 用户详细信息
		 */
		if (isset($options['verbose'])) {
			if (isset($options['verbose']['member'])) {
				/**
				 * 用户认证身份
				 */
				$members = $this->getMembersByMpid($mpid, $sAuthapis, $openid);
				if ($matter && isset($matter->access_control) && $matter->access_control === 'Y') {
					$membersInAcl = array();
					foreach ($members as $member) {
						if ($this->canAccessObj($mpid, $matter->id, $member, $sAuthapis, $matter)) {
							$membersInAcl[] = $member;
						}
					}
				}
				$user->members = $members;
				isset($membersInAcl) && $user->membersInAcl = $membersInAcl;
			}
			if (isset($options['verbose']['fan'])) {
				/**
				 * 关注用户信息
				 */
				if (empty($openid) && !empty($members)) {
					$fan = $this->model('user/fans')->byMid($members[0]->mid, '*');
					$openid = $fan->openid;
				} else if (!empty($openid)) {
					$fan = $this->model('user/fans')->byOpenid($mpid, $openid);
				} else {
					$fan = null;
				}

				$user->fan = $fan;
			}
		}

		return $user;
	}
	/**
	 * 跳转到用户认证页
	 */
	protected function gotoAuth($mpid, $aAuthapis, $openid, $targetUrl = null) {
		is_string($aAuthapis) && $aAuthapis = explode(',', $aAuthapis);
		/**
		 * 如果不是注册用户，要求先进行认证
		 */
		if (count($aAuthapis) === 1) {
			$authapi = $this->model('user/authapi')->byId($aAuthapis[0], 'authid,url');
			strpos($authapi->url, 'http') === false && $authUrl = 'http://' . $_SERVER['HTTP_HOST'];
			$authUrl .= $authapi->url;
			$authUrl .= "?mpid=$mpid";
			!empty($openid) && $authUrl .= "&openid=$openid";
			$authUrl .= "&authid=" . $aAuthapis[0];
		} else {
			/**
			 * 让用户选择通过那个认证接口进行认证
			 */
			$authUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/rest/member/authoptions';
			$authUrl .= "?mpid=$mpid";
			!empty($openid) && $authUrl .= "&openid=$ooid";
			$authUrl .= "&authids=" . implode(',', $aAuthapis);
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
			$targetUrl = $this->model()->encrypt($targetUrl, 'ENCODE', $mpid);
			$this->mySetCookie("_{$mpid}_mauth_t", $targetUrl, time() + 300);
			$this->redirect($authUrl);
		}
	}
	/**
	 *
	 */
	protected function gotoOutAcl($mpid, $authid) {
		$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
		header($protocol . ' 401 Unauthorized');
		$r = $this->model('user/authapi')->getAclStatement($authid, $mpid);
		TPL::assign('title', '访问控制未通过');
		TPL::assign('body', $r);
		TPL::output('error');
		exit;
	}
	/**
	 * 用户身份认证和绑定
	 *
	 * $mpid
	 * $aAuthapis
	 * $targetUrl
	 * 成功后跳转回指定$targetUrl
	 * 若url===false，说明不跳转，而是前端通知
	 *
	 * 如果没有提供用户的公众号身份信息，且公众号开通OAuth认证接口，那么就通过认证接口获取openid
	 * 如何知道当前用户是哪来的呢？因为OAuth必须在微信或易信的客户端中打开，所以可以通过当前的浏览器判断是从哪里来的
	 * if (preg_match('/yixin/i', $user_agent)) {} elseif (preg_match('/MicroMessenger/i', $user_agent)) {}
	 *
	 * 用户的实际身份是不变的，无论何时进行绑定都不应该变
	 * 所以可能，用户提供了身份信息，但是并没有和公众号的身份绑定起来
	 * 如果有些业务逻辑必须要求这两种身份之间进行绑定，再做绑定
	 * 也就是说，身份的认证必须做，绑定不一定能做，但是允许以后再绑定
	 *
	 * 假如用户通过公众号发起了一个请求，但是openid并没有和真实身份进行绑定，那么就可以要求再次绑定
	 *
	 * 因为公众账号本身的业务逻辑并不需要认证过的真实身份。
	 *
	 * 假如我要通过公众号查找我对图文发表过的评论
	 * 那么就要检查我的openid是否能够对应到mid
	 * 结果发现找不到，就进行身份绑定
	 * 结果发现已经有身份信息的cookie，就直接绑定
	 * 没有cookie就重新认真
	 *
	 */
	protected function authenticate($runningMpid, $aAuthapis, $targetUrl = null, $openid = null) {
		empty($aAuthapis) && die('aAuthapis is emtpy.');

		$members = $this->getMembersByMpid($runningMpid, $aAuthapis, $openid);
		/**
		 * 获得用户身份信息
		 */
		if (!empty($members)) {
			return $members;
		}

		/**
		 * 如果不是注册用户，要求先进行认证
		 */
		$this->gotoAuth($runningMpid, $aAuthapis, $openid, $targetUrl);
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
	protected function accessControl($runningMpid, $objId, $authapis, $openid, &$obj, $targetUrl = null) {
		$aAuthapis = explode(',', $authapis);
		$members = $this->authenticate($runningMpid, $aAuthapis, $targetUrl, $openid);
		$passed = false;
		foreach ($members as $member) {
			if ($this->canAccessObj($runningMpid, $objId, $member, $authapis, $obj)) {
				/**
				 * 检查用户是否通过了验证
				 */
				$q = array(
					'verified',
					'xxt_member',
					"mpid='$runningMpid' and mid='$member->mid'",
				);
				if ('Y' !== $this->model()->query_val_ss($q)) {
					$r = $this->model('user/authapi')->getNotpassStatement($member->authapi_id, $runningMpid);
					$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
					header($protocol . ' 401 Unauthorized');
					TPL::assign('title', '访问控制未通过');
					TPL::assign('body', $r);
					TPL::output('error');
					exit;
				}
				$passed = true;
				break;
			}
		}
		!$passed && $this->gotoOutAcl($runningMpid, $member->authapi_id);

		return $member;
	}
	/**
	 *
	 * $mpid
	 * $code
	 * $mocker
	 */
	protected function doAuth($mpid, $code, $mocker) {
		$openid = $this->getCookieOAuthUser($mpid);
		if (empty($openid)) {
			if ($code !== null) {
				$openid = $this->getOAuthUserByCode($mpid, $code);
			} else {
				if (!empty($mocker)) {
					$openid = $mocker;
					$this->setCookieOAuthUser($mpid, $mocker);
				} else {
					if (!$this->oauth($mpid)) {
						$openid = null;
					}

				}
			}
		}

		return $openid;
	}
	/**
	 * 执行OAuth操作
	 *
	 * 会在cookie保留结果5分钟
	 *
	 * $mpid
	 * $controller OAuth的回调地址
	 * $state OAuth回调时携带的参数
	 */
	protected function oauth($mpid) {
		empty($mpid) && die('mpid is emtpy, cannot execute oauth.');
		/**
		 * 只有通过易信，微信客户端发起才有效
		 */
		$csrc = $this->getClientSrc();
		if ($csrc !== 'yx' && $csrc !== 'wx') {
			return false;
		}

		/**
		 * 如果公众号开放了OAuth接口，通过OAuth获得openid
		 */
		$httpHost = $_SERVER['HTTP_HOST'];
		$httpHost = str_replace('www.', '', $_SERVER['HTTP_HOST']);
		$ruri = "http://$httpHost" . $_SERVER['REQUEST_URI'];

		$app = $this->model('mp\mpaccount')->byId($mpid, 'mpsrc');

		switch ($app->mpsrc) {
		case 'qy':
			$mpproxy = $this->model('mpproxy/qy', $mpid);
			break;
		case 'wx':
			$fea = $this->model('mp\mpaccount')->getApis($mpid);
			if ($fea->wx_oauth === 'Y') {
				$mpproxy = $this->model('mpproxy/wx', $mpid);
			}

			break;
		case 'yx':
			$fea = $this->model('mp\mpaccount')->getApis($mpid);
			if ($fea->yx_oauth === 'Y') {
				$mpproxy = $this->model('mpproxy/yx', $mpid);
			}

			break;
		}
		if (isset($mpproxy)) {
			$oauthUrl = $mpproxy->oauthUrl($mpid, $ruri);
			$this->redirect($oauthUrl);
		}

		return false;
	}
	/**
	 * 通过OAuth接口获得用户信息
	 *
	 * $mpid
	 * $code
	 */
	protected function getOAuthUserByCode($mpid, $code) {
		$csrc = $this->getClientSrc();
		if ($csrc !== 'yx' && $csrc !== 'wx') {
			return null;
		}

		if ($this->myGetcookie("_{$mpid}_oauth")) {
			return $this->getCookieOAuthUser($mpid);
		}

		/**
		 * 获得openid
		 */
		$mpa = $this->model('mp\mpaccount')->byId($mpid, 'mpsrc');
		$mpproxy = $this->model('mpproxy/' . $mpa->mpsrc, $mpid);
		$rst = $mpproxy->getOAuthUser($code);
		if ($rst[0] === false) {
			die('oauth2 failed:' . $rst[1]);
		}

		/**
		 * 将openid保存在cookie，可用于进行用户身份绑定
		 */
		$openid = $rst[1];
		$this->setCookieOAuthUser($mpid, $openid);

		return $openid;
	}
	/**
	 * 在cookie中保存OAuth用户信息
	 * $mpid
	 * $openid
	 */
	protected function setCookieOAuthUser($mpid, $openid) {
		$encoded = $this->model()->encrypt($openid, 'ENCODE', $mpid);
		$this->mySetcookie("_{$mpid}_oauth", $encoded);

		return true;
	}
	/**
	 * 返回当前的用户
	 *
	 * $mpid
	 * $who
	 */
	protected function getCookieOAuthUser($mpid) {
		if ($openid = $this->myGetcookie("_{$mpid}_oauth")) {
			$openid = $this->model()->encrypt($openid, 'DECODE', $mpid);
			if (0 === strpos($openid, '[')) {
				$openid = json_decode($openid);
				$openid = $openid[0];
				$this->setCookieOAuthUser($mpid, $openid);
			}
		} else {
			$openid = '';
		}

		return $openid;
	}
	/**
	 *
	 * 要求关注
	 *
	 * $runningMpid
	 * $ooid
	 *
	 */
	protected function askFollow($runningMpid, $ooid) {
		$isfollow = $this->model('user/fans')->isFollow($runningMpid, $ooid);

		if (!$isfollow) {
			$fea = $this->model('mp\mpaccount')->getFeatures($runningMpid);

			$mp = $this->model('mp\mpaccount')->byId($runningMpid, 'parent_mpid');
			if (!empty($mp->parent_mpid)) {
				$pfea = $this->model('mp\mpaccount')->getFeatures($mp->parent_mpid);
				empty($fea->follow_ele) && !empty($pfea->follow_ele) && $fea->follow_ele = $pfea->follow_ele;
				empty($fea->follow_css) && !empty($pfea->follow_css) && $fea->follow_css = $pfea->follow_css;
			}
			$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
			header($protocol . ' 401 Unauthorized');
			TPL::assign('follow_ele', $fea->follow_ele);
			TPL::assign('follow_css', $fea->follow_css);
			TPL::output('follow');
			exit;
		}

		return true;
	}
	/**
	 * 微信jssdk包
	 *
	 * $mpid
	 * $url
	 */
	public function wxjssdksignpackage_action($mpid, $url) {
		$mpa = $this->model('mp\mpaccount')->byId($mpid);
		$mpproxy = $this->model('mpproxy/' . $mpa->mpsrc, $mpid);

		$rst = $mpproxy->getJssdkSignPackage(urldecode($url));

		header('Content-Type: text/javascript');
		if ($rst[0] === false) {
			die("alert('{$rst[1]}');");
		}

		die($rst[1]);
	}
}
