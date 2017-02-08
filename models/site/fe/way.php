<?php
namespace site\fe;
/**
 * who are you
 */
class way_model extends \TMS_MODEL {
	/**
	 * 返回当前访问用户的信息
	 */
	public function who($siteId, $auth = []) {
		$modified = false;
		/* cookie中缓存的用户信息 */
		$cookieUser = $this->getCookieUser($siteId);
		if (!empty($auth)) {
			/* 有身份用户首次访问，若已经有绑定的站点用户，获取站点用户；否则，创建持久化的站点用户，并绑定关系 */
			foreach ($auth['sns'] as $snsName => $snsUser) {
				$modelSns = $this->M('sns\\' . $snsName);
				$siteSns = $modelSns->bySite($siteId);
				$cookieUser = $this->_bindSiteSnsUser($siteId, $snsName, $snsUser, $cookieUser);
			}
			$modified = true;
		} elseif ($cookieUser === false) {
			/* 无身份用户首次访问，创建非持久化的站点用户 */
			$modelAct = $this->M('site\user\account');
			$account = $modelAct->blank($siteId, false, ['nickname' => '新用户']);
			$cookieUser = new \stdClass;
			$cookieUser->uid = $account->uid;
			$cookieUser->nickname = '新用户';
			$cookieUser->expire = time() + (86400 * TMS_COOKIE_SITE_USER_EXPIRE);
			$modified = true;
		}
		/* 将用户信息保存在cookie中 */
		if ($modified) {
			$this->setCookieUser($siteId, $cookieUser);
		}

		return $cookieUser;
	}
	/**
	 * 绑定站点第三方认证用户
	 */
	private function _bindSiteSnsUser($siteId, $snsName, $snsUser, $cookieUser) {
		//
		$modelSns = \TMS_APP::M('sns\\' . $snsName);
		$snsConfig = $modelSns->bySite($siteId);
		if ($snsConfig === false || $snsConfig->joined !== 'Y') {
			$snsSiteId = 'platform';
		} else {
			$snsSiteId = $siteId;
		}
		//
		$modelSiteUser = \TMS_App::M('site\user\account');
		$modelSnsUser = \TMS_App::M('sns\\' . $snsName . '\fan');

		// 当前用户的社交账号信息（已经保存过信息）
		$dbSnsUser = $modelSnsUser->byOpenid($snsSiteId, $snsUser->openid, 'openid,nickname,headimgurl,sex,country,province,city');

		if ($dbSnsUser) {
			if ($cookieUser === false) {
				// 当前关注用户是否已经对应的站点用户？如果不存在就创建新的站点用户
				$siteUser = $modelSiteUser->byOpenid($siteId, $snsName, $dbSnsUser->openid);
				if ($siteUser === false) {
					$siteUser = $modelSiteUser->blank($siteId, true, ['ufrom' => $snsName, $snsName . '_openid' => $dbSnsUser->openid, 'nickname' => $dbSnsUser->nickname, 'headimgurl' => $dbSnsUser->headimgurl]);
				}
				// 新的cookie用户
				$cookieUser = new \stdClass;
			} else {
				// 当前站点用户是否是一个已经持久化的用户，如果不是就创建一个持久化的站点用户
				$siteUser = $modelSiteUser->byId($cookieUser->uid);
				if ($siteUser === false) {
					// 当前关注用户是否已经对应的站点用户？如果不存在就创建新的站点用户
					$siteUser = $modelSiteUser->byOpenid($siteId, $snsName, $dbSnsUser->openid);
					if ($siteUser === false) {
						$siteUser = $modelSiteUser->blank($siteId, true, ['uid' => $cookieUser->uid, 'ufrom' => $snsName, $snsName . '_openid' => $dbSnsUser->openid, 'nickname' => $dbSnsUser->nickname, 'headimgurl' => $dbSnsUser->headimgurl]);
					}
				} else if ($dbSnsUser->openid !== $siteUser->{$snsName . '_openid'}) {
					$siteUserBefore = $modelSiteUser->byOpenid($siteId, $snsName, $dbSnsUser->openid);
					if ($siteUserBefore) {
						// 记录站点用户关联的站点用户
						$modelSiteUser->update(
							'xxt_site_account',
							['assoc_id' => $siteUserBefore->uid],
							"uid='{$siteUser->uid}'"
						);
						$siteUser = $siteUserBefore;
					} else {
						// 更新站点用户关联的认证用户信息
						$modelSiteUser->update(
							'xxt_site_account',
							[$snsName . '_openid' => $dbSnsUser->openid],
							"uid='{$siteUser->uid}'"
						);
					}
				}
			}
		} else {
			// 不是关注用户，建一个空的关注用户
			$options = array();
			isset($snsUser->nickname) && $options['nickname'] = $snsUser->nickname;
			isset($snsUser->sex) && $options['sex'] = $snsUser->sex;
			isset($snsUser->headimgurl) && $options['headimgurl'] = $snsUser->headimgurl;
			isset($snsUser->country) && $options['country'] = $snsUser->country;
			isset($snsUser->province) && $options['province'] = $snsUser->province;
			isset($snsUser->city) && $options['city'] = $snsUser->city;

			$optionsSite = array();
			isset($snsUser->nickname) && $optionsSite['nickname'] = $snsUser->nickname;
			isset($snsUser->headimgurl) && $optionsSite['headimgurl'] = $snsUser->headimgurl;
			$optionsSite['ufrom'] = $snsName;
			$optionsSite[$snsName . '_openid'] = $snsUser->openid;

			if ($cookieUser === false) {
				$siteUser = $modelSiteUser->blank($siteId, true, $optionsSite);
				if ($snsName != 'qy') {
					$dbSnsUser = $modelSnsUser->blank($snsSiteId, $snsUser->openid, true, $options);
				}
				// 新的cookie用户
				$cookieUser = new \stdClass;
			} else {
				$siteUser = $modelSiteUser->byId($cookieUser->uid);
				if ($siteUser === false) {
					// 没有站点用户创建个新的
					$siteUser = $modelSiteUser->blank($siteId, true, $optionsSite);
				}
				if ($snsName !== 'qy') {
					// 保存社交账号信息
					$dbSnsUser = $modelSnsUser->blank($snsSiteId, $snsUser->openid, true, $options);
				}
				// 清空不必要的数据，减小cookie尺寸
				if ($dbSnsUser) {
					unset($dbSnsUser->siteid);
					unset($dbSnsUser->subscribe_at);
					unset($dbSnsUser->sync_at);
				}
			}
		}

		// 更新cookie信息
		$cookieUser->_ver = 1;
		$cookieUser->uid = $siteUser->uid;
		$cookieUser->expire = time() + (86400 * TMS_COOKIE_SITE_USER_EXPIRE);
		!isset($cookieUser->sns) && $cookieUser->sns = new \stdClass;
		if ($dbSnsUser === false) {
			$cookieUser->nickname = isset($snsUser->nickname) ? $snsUser->nickname : '';
			$cookieUser->sns->{$snsName} = $snsUser;
		} else {
			$cookieUser->nickname = $dbSnsUser->nickname;
			$cookieUser->sns->{$snsName} = $dbSnsUser;
		}

		return $cookieUser;
	}

	/**
	 * 绑定自定义用户
	 */
	public function &bindMember($siteId, $member) {
		$modelSiteUser = \TMS_App::M('site\user\account');
		/* cookie中缓存的用户信息 */
		$cookieUser = $this->getCookieUser($siteId);
		if ($cookieUser === false) {
			$siteUser = $modelSiteUser->blank($siteId, true, array('ufrom' => 'member'));
			/* 新的cookie用户 */
			$cookieUser = new \stdClass;
		} else {
			$siteUser = $modelSiteUser->byId($cookieUser->uid);
			if ($siteUser === false) {
				$siteUser = $modelSiteUser->blank($siteId, true, array('uid' => $cookieUser->uid, 'ufrom' => 'member'));
			}
		}
		/* 更新认证用户信息 */
		if ($siteUser->uid !== $member->userid) {
			$this->update('xxt_site_member', array('userid' => $siteUser->uid), "siteid='$siteId' and id=$member->id");
		}
		/* 更新cookie信息 */
		$cookieUser->uid = $siteUser->uid;
		if (empty($cookieUser->nickname)) {
			$cookieUser->nickname = isset($member->name) ? $member->name : (isset($member->mobile) ? $member->mobile : (isset($member->email) ? $member->email : ''));
			$modelSiteUser->update(
				'xxt_site_account',
				array('nickname' => $cookieUser->nickname),
				"uid='{$cookieUser->uid}'"
			);
		}
		$cookieUser->expire = time() + (86400 * TMS_COOKIE_SITE_USER_EXPIRE);
		!isset($cookieUser->members) && $cookieUser->members = new \stdClass;
		$cookieUser->members->{$member->schema_id} = $member;
		/* 是否存在可关联的sns */
		if (!empty($member->userid)) {
			if (empty($cookieUser->sns->wx)) {
				if ($wxFan = $modelWxFan = \TMS_App::M('sns\wx\fan')->byUser($siteId, $member->userid)) {
					$cookieUser->sns->wx = $wxFan;
				}
			}
			if (empty($cookieUser->sns->yx)) {
				if ($yxFan = $modelYxFan = \TMS_App::M('sns\yx\fan')->byUser($siteId, $member->userid)) {
					$cookieUser->sns->yx = $yxFan;
				}
			}
		}
		/* 将用户信息保存在cookie中 */
		$this->setCookieUser($siteId, $cookieUser);

		return $cookieUser;
	}
	/**
	 * 检查指定的用户是否已经登录
	 */
	public function isLogined($siteId, $who) {
		/*如果已经超过有效期，认证不通过*/
		if (empty($who->loginExpire) || $who->loginExpire < time()) {
			$this->cleanCookieUser($siteId);
			return false;
		}
		/*通过cookie返回登录状态*/
		$this->setCookieLogin($siteId, $who);

		return true;
	}
	/**
	 * 设置 COOKIE
	 *
	 * @param string $name
	 * @param string $value
	 * @param int $expire
	 * @param string $path
	 * @param string $domain
	 * @param string $secure
	 */
	protected function mySetCookie($name, $value = '', $expire = null, $path = '/', $domain = null, $secure = false) {
		if (!$domain and G_COOKIE_DOMAIN) {
			$domain = G_COOKIE_DOMAIN;
		}

		return setcookie(G_COOKIE_PREFIX . $name, $value, $expire, $path, $domain, $secure);
	}
	/**
	 * 获取cookie的值
	 */
	protected function myGetCookie($name) {
		$cookiename = G_COOKIE_PREFIX . $name;
		if (isset($_COOKIE[$cookiename])) {
			return $_COOKIE[$cookiename];
		}

		return false;
	}
	/**
	 *
	 */
	protected function getCookieKey($seed) {
		return md5($seed);
	}
	/**
	 * 将当前用户的身份保留的在cookie中
	 */
	public function setCookieUser($siteId, $user) {
		$cookiekey = $this->getCookieKey($siteId);
		$cookieUser = $user;
		$cookieUser = json_encode($cookieUser);
		$encoded = $this->encrypt($cookieUser, 'ENCODE', $cookiekey);
		$expireAt = time() + (86400 * TMS_COOKIE_SITE_USER_EXPIRE);
		$this->mySetCookie("_site_{$siteId}_fe_user", $encoded, $expireAt);

		return true;
	}
	/**
	 * 从cookie中获取当前用户信息
	 */
	public function getCookieUser($siteId) {
		$cookiekey = $this->getCookieKey($siteId);
		$encoded = $this->myGetCookie("_site_{$siteId}_fe_user");
		if (empty($encoded)) {
			return false;
		}
		$cookieUser = $this->encrypt($encoded, 'DECODE', $cookiekey);
		$cookieUser = json_decode($cookieUser);

		return $cookieUser;
	}
	/**
	 * 站点注册用户信息
	 */
	public function getCookieRegUser() {
		$cookiekey = $this->getCookieKey('platform');
		$encoded = $this->myGetCookie("_site_user_login");
		if (empty($encoded)) {
			return false;
		}
		$cookieUser = $this->encrypt($encoded, 'DECODE', $cookiekey);
		$cookieUser = json_decode($cookieUser);

		return $cookieUser;
	}
	/**
	 * 站点注册用户信息
	 */
	public function setCookieRegUser($user) {
		$cookiekey = $this->getCookieKey('platform');
		$cookieUser = $user;
		$cookieUser = json_encode($cookieUser);
		$encoded = $this->encrypt($cookieUser, 'ENCODE', $cookiekey);
		$expireAt = time() + (86400 * TMS_COOKIE_SITE_USER_EXPIRE);
		$this->mySetCookie("_site_user_login", $encoded, $expireAt);

		return true;
	}
	/**
	 * 清除用户登录信息
	 */
	public function cleanCookieUser($siteId) {
		$this->mySetcookie("_site_{$siteId}_fe_user", '', time() - 3600);
		return true;
	}
	/**
	 * 更新cookie记录的所有用户的信息
	 */
	public function resetAllCookieUser() {
		if (!$this->getCookieRegUser()) {
			$unbounds = [];
			$unionid = false;
			$modelAct = $this->model('site\user\account');
			$sites = $this->siteList();
			foreach ($sites as $siteId) {
				$cookieUser = $this->getCookieUser($siteId);
				$account = $modelAct->byId($cookieUser->uid);
				if (empty($account->unionid)) {
					$unbounds[$siteId] = $cookieUser;
				} else {
					if ($unionid === false) {
						$unionid = $account->unionid;
					} else {
						// 清除不是同一注册用户下的数据
						$this->cleanCookieUser($siteId);
					}
				}
			}
			if ($unionid) {
				if (count($unbounds)) {
					// 补充站点访客信息
					foreach ($unbounds as $siteId => $cookieUser) {
						$modelReg->update('xxt_site_account', ['unionid' => $unionid], ['uid' => $cookieUser->uid]);
						$modelWay->setCookieUser($siteId, $cookieUser);
					}
				}
				// 补充注册账号信息
				$modelReg = $this->model('site\user\registration');
				$registration = $modelReg->byId($unionid);
				$cookieRegUser = new \stdClass;
				$cookieRegUser->unionid = $registration->unionid;
				$cookieRegUser->uname = $registration->uname;
				$cookieRegUser->nickname = $registration->nickname;
				$this->setCookieRegUser($cookieRegUser);
			}
		}
	}
	/**
	 * 切换当前客户端的注册用户
	 */
	public function changeRegUser($registration) {
		/* cookie中保留注册信息 */
		$cookieRegUser = new \stdClass;
		$cookieRegUser->unionid = $registration->unionid;
		$cookieRegUser->uname = $registration->uname;
		$cookieRegUser->nickname = $registration->nickname;
		$this->setCookieRegUser($cookieRegUser);

		/* 更新cookie中已有的用户信息 */
		$sites = $this->model('site\fe\way')->siteList();
		$modelAct = $this->model('site\user\account');
		foreach ($sites as $siteId) {
			$cookieUser = $this->getCookieUser($siteId);
			$account = $modelAct->byId($cookieUser->uid);
			if ($account) {
				$modelAct->update('xxt_site_account', ['unionid' => $unionid], ['uid' => $cookieUser->uid]);
			} else {
				$account = $modelAct->create($siteId, '', '', ['uid' => $cookieUser->uid, 'unionid' => $registration->unionid, 'from_ip' => $this->client_ip()]);
			}
		}

		return $cookieRegUser;
	}
	/**
	 * 获得当前用户在平台对应的所有站点和站点访客用户信息
	 */
	public function &siteList() {
		$sites = [];
		foreach ($_COOKIE as $key => $val) {
			if (preg_match('/xxt_site_(.*?)_fe_user/', $key, $matches)) {
				$sites[] = $matches[1];
			}
		}

		return $sites;
	}
}