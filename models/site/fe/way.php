<?php
namespace site\fe;
/**
 * who are you
 */
class way_model extends \TMS_MODEL {
	/**
	 * 返回当前访问用户的信息
	 */
	public function who($siteId, $auth = array()) {
		/* cookie中缓存的用户信息 */
		$cookieUser = $this->getCookieUser($siteId);
		if (!empty($auth)) {
			/* 有身份用户首次访问，若已经有绑定的站点用户，获取站点用户；否则，创建持久化的站点用户，并绑定关系 */
			foreach ($auth['sns'] as $snsName => $snsUser) {
				if ($snsName === 'qy') {
					continue;
				}
				$modelSns = \TMS_App::M('sns\\' . $snsName);
				$siteSns = $modelSns->bySite($siteId);
				if (isset($siteSns->by_platform) && $siteSns->by_platform === 'Y') {
					$cookieUser = $this->_bindPlatformSnsUser($siteId, $snsName, $snsUser, $cookieUser);
				} else {
					$cookieUser = $this->_bindSiteSnsUser($siteId, $snsName, $snsUser, $cookieUser);
				}
			}
		} elseif ($cookieUser === false) {
			/* 无身份用户首次访问，创建非持久化的站点用户 */
			$modelAct = \TMS_APP::M('site\user\account');
			$account = $modelAct->blank($siteId, false);
			$cookieUser = new \stdClass;
			$cookieUser->uid = $account->uid;
			$cookieUser->nickname = '新用户';
			$cookieUser->expire = time() + (86400 * TMS_COOKIE_SITE_USER_EXPIRE);
		}
		/* 将用户信息保存在cookie中 */
		$this->setCookieUser($siteId, $cookieUser);

		return $cookieUser;
	}
	/**
	 * 绑定站点第三方认证用户
	 */
	private function _bindSiteSnsUser($siteId, $snsName, $snsUser, $cookieUser) {
		$modelSiteUser = \TMS_App::M('site\user\account');
		$modelSnsUser = \TMS_App::M('sns\\' . $snsName . '\fan');
		// 已经存在的第三方认证用户
		$authedUser = $modelSnsUser->byOpenid($siteId, $snsUser->openid, 'userid,openid,nickname,headimgurl,sex,country,province,city');

		if ($authedUser) {
			if ($cookieUser === false) {
				if (!empty($authedUser->userid)) {
					/* 已经绑定过站点用户 */
					$siteUser = $modelSiteUser->byId($authedUser->userid);
				} else {
					/* 创建新的站点用户 */
					$siteUser = $modelSiteUser->blank($siteId, true, array('ufrom' => $snsName));
					/* 更新第三方认证用户和站点用户的关联关系 */
					$modelSnsUser->modifyByOpenid($siteId, $snsUser->openid, array('userid' => $siteUser->uid));
				}
				/* 新的cookie用户 */
				$cookieUser = new \stdClass;
			} else {
				$siteUser = $modelSiteUser->byId($cookieUser->uid);
				if ($siteUser === false) {
					if (!empty($authedUser->userid)) {
						/* 已经绑定过站点用户 */
						$siteUser = $modelSiteUser->byId($authedUser->userid);
					} else {
						/* 创建新的站点用户 */
						$siteUser = $modelSiteUser->blank($siteId, true, array('ufrom' => $snsName));
						/* 更新第三方认证用户和站点用户的关联关系 */
						$modelSnsUser->modifyByOpenid($siteId, $snsUser->openid, array('userid' => $siteUser->uid));
					}
				} else if ($authedUser->userid !== $siteUser->uid) {
					/* 更新认证用户关联的站点用户信息 */
					if (!empty($authedUser->userid)) {
						$modelSiteUser->update(
							'xxt_site_account',
							array('assoc_uid' => $siteUser->uid),
							"uid='{$authedUser->userid}'"
						);
					} else {

					}
					/* 更新第三方认证用户和站点用户的关联关系 */
					$modelSnsUser->modifyByOpenid($siteId, $snsUser->openid, array('userid' => $siteUser->uid));
				}
			}
			/* 清空不必要的数据，减小cookie尺寸 */
			unset($authedUser->userid);
		} else {
			/* 不是关注用户，建一个空的关注用户 */
			$options = array();
			isset($snsUser->nickname) && $options['nickname'] = $snsUser->nickname;
			isset($snsUser->sex) && $options['sex'] = $snsUser->sex;
			isset($snsUser->headimgurl) && $options['headimgurl'] = $snsUser->headimgurl;
			isset($snsUser->country) && $options['country'] = $snsUser->country;
			isset($snsUser->province) && $options['province'] = $snsUser->province;
			isset($snsUser->city) && $options['city'] = $snsUser->city;
			if ($cookieUser === false) {
				$siteUser = $modelSiteUser->blank($siteId, true, array('ufrom' => $snsName));
				$options['userid'] = $siteUser->uid;
				$authedUser = $modelSnsUser->blank($siteId, $snsUser->openid, true, $options);
				/* 新的cookie用户 */
				$cookieUser = new \stdClass;
			} else {
				$siteUser = $modelSiteUser->byId($cookieUser->uid);
				if ($siteUser === false) {
					/* 没有站点用户创建个新的 */
					$siteUser = $modelSiteUser->blank($siteId, true, array('ufrom' => $snsName));
				}
				/* 创建新的第三方认证用户 */
				$options['userid'] = $siteUser->uid;
				$authedUser = $modelSnsUser->blank($siteId, $snsUser->openid, true, $options);
				/* 清空不必要的数据，减小cookie尺寸 */
				unset($authedUser->userid);
				unset($authedUser->siteid);
				unset($authedUser->subscribe_at);
				unset($authedUser->sync_at);
			}
		}
		/* 更新cookie信息 */
		$cookieUser->uid = $siteUser->uid;
		$cookieUser->nickname = $authedUser->nickname;
		$cookieUser->expire = time() + (86400 * TMS_COOKIE_SITE_USER_EXPIRE);
		!isset($cookieUser->sns) && $cookieUser->sns = new \stdClass;
		$cookieUser->sns->{$snsName} = $authedUser;

		return $cookieUser;
	}
	/**
	 * 绑定平台第三方认证用户
	 */
	private function _bindPlatformSnsUser($siteId, $snsName, $snsUser, $cookieUser) {
		$modelSiteUser = \TMS_App::M('site\user\account');
		$modelSnsUser = \TMS_App::M('site\sns\pl\\' . $snsName . 'fan');
		// 已经存在的第三方认证用户
		$authedUser = $modelSnsUser->byOpenid($snsUser->openid, 'userid,nickname,headimgurl,sex,country,province,city');

		if ($authedUser) {
			if ($cookieUser === false) {
				if (!empty($authedUser->userid)) {
					/* 已经绑定过站点用户 */
					$siteUser = $modelSiteUser->byId($authedUser->userid);
				} else {
					/* 创建新的站点用户 */
					$siteUser = $modelSiteUser->blank($siteId, true, array('ufrom' => $snsName));
				}
				/* 新的cookie用户 */
				$cookieUser = new \stdClass;
			} else {
				$siteUser = $modelSiteUser->byId($cookieUser->uid);
				if ($siteUser === false) {
					if (!empty($authedUser->userid)) {
						/* 已经绑定过站点用户 */
						$siteUser = $modelSiteUser->byId($authedUser->userid);
					} else {
						/* 创建新的站点用户 */
						$siteUser = $modelSiteUser->blank($siteId, true, array('ufrom' => $snsName));
					}
				} else if ($authedUser->userid !== $siteUser->uid) {
					/* 更新认证用户关联的站点用户信息 */
					$modelSiteUser->update(
						'xxt_site_account',
						array('assoc_uid' => $siteUser->uid),
						"uid='{$authedUser->userid}'"
					);
					$modelSnsUser->update(
						$siteId,
						$snsUser->openid,
						array('userid' => $siteUser->uid)
					);
					$siteUser = $modelSiteUser->byId($authedUser->userid);
				}
			}
			/* 清空不必要的数据，减小cookie尺寸 */
			unset($authedUser->userid);
		} else {
			/* 不是关注用户，建一个空的关注用户 */
			$options = array();
			if ($snsName === 'wx') {
				isset($snsUser->nickname) && $options['nickname'] = $snsUser->nickname;
				isset($snsUser->sex) && $options['sex'] = $snsUser->sex;
				isset($snsUser->headimgurl) && $options['headimgurl'] = $snsUser->headimgurl;
				isset($snsUser->country) && $options['country'] = $snsUser->country;
				isset($snsUser->province) && $options['province'] = $snsUser->province;
				isset($snsUser->city) && $options['city'] = $snsUser->city;
			}
			if ($cookieUser === false) {
				$siteUser = $modelSiteUser->blank($siteId, true, array('ufrom' => $snsName));
				$options['userid'] = $siteUser->uid;
				$authedUser = $modelSnsUser->blank($snsUser->openid, true, $options);
				/* 新的cookie用户 */
				$cookieUser = new \stdClass;
			} else {
				$siteUser = $modelSiteUser->byId($cookieUser->uid);
				if ($siteUser === false) {
					/* 没有站点用户创建个新的 */
					$siteUser = $modelSiteUser->blank($siteId, true, array('ufrom' => $snsName));
				}
				/* 创建新的第三方认证用户 */
				$options['userid'] = $siteUser->uid;
				$authedUser = $modelSnsUser->blank($snsUser->openid, true, $options);
				/* 清空不必要的数据，减小cookie尺寸 */
				unset($authedUser->userid);
				unset($authedUser->siteid);
				unset($authedUser->subscribe_at);
				unset($authedUser->sync_at);
			}
		}
		/* 更新cookie信息 */
		$cookieUser->uid = $siteUser->uid;
		$cookieUser->nickname = $authedUser->nickname;
		$cookieUser->expire = time() + (86400 * TMS_COOKIE_SITE_USER_EXPIRE);
		!isset($cookieUser->sns) && $cookieUser->sns = new \stdClass;
		$cookieUser->sns->{$snsName} = $authedUser;

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
	 * 清除用户登录信息
	 */
	public function cleanCookieUser($siteId) {
		$this->mySetcookie("_site_{$siteId}_fe_user", '', time() - 3600);
		return true;
	}
}