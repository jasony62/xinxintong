<?php
namespace site\fe;
/**
 * who are you
 */
class way_model extends \TMS_MODEL {
	/**
	 * 返回当前用户的访客账号信息
	 */
	public function who($siteId, $aSnsAuth = []) {
		$modified = false;
		/* cookie中缓存的用户信息 */
		$oCookieUser = $this->getCookieUser($siteId);
		$oCookieRegUser = $this->getCookieRegUser();
		if (!empty($aSnsAuth)) {
			/* 有身份用户首次访问，若已经有绑定的站点用户，获取站点用户；否则，创建持久化的站点用户，并绑定关系 */
			foreach ($aSnsAuth['sns'] as $snsName => $snsUser) {
				if ($oCookieUser) {
					if (isset($oCookieUser->sns->{$snsName}) && $cookieSnsUser = $oCookieUser->sns->{$snsName}) {
						if ($cookieSnsUser->openid === $snsUser->openid) {
							continue;
						}
					}
				}
				$oCookieUser = $this->_bindSiteSnsUser($siteId, $snsName, $snsUser, $oCookieUser, $oCookieRegUser);
			}
			$modified = true;
		} else if (empty($oCookieUser)) {
			/* 无访客身份用户首次访问站点 */
			$modelSiteUser = $this->model('site\user\account');
			if ($oCookieRegUser) {
				/* 注册主站点访客账号，有，使用，没有，创建 */
				$oSiteUser = $modelSiteUser->byPrimaryUnionid($siteId, $oCookieRegUser->unionid);
				if ($oSiteUser === false) {
					/* 当前为登录状态，创建持久化用户，且作为绑定的主访客账号 */
					$props = [
						'unionid' => $oCookieRegUser->unionid,
						'is_reg_primary' => 'Y',
					];
					if (!empty($oCookieRegUser->nickname)) {
						$props['nickname'] = $oCookieRegUser->nickname;
					}
					$oSiteUser = $modelSiteUser->blank($siteId, true, $props);
				}
			} else {
				/* 未登录状态，创建非持久化的站点访客账号 */
				$oSiteUser = $modelSiteUser->blank($siteId, false);
			}
			$oCookieUser = new \stdClass;
			$oCookieUser->uid = $oSiteUser->uid;
			$oCookieUser->nickname = $oSiteUser->nickname;
			$oCookieUser->expire = time() + (86400 * TMS_COOKIE_SITE_USER_EXPIRE);
			$modified = true;
		} else {
			if (empty($oCookieUser->loginExpire)) {
				if ($oCookieRegUser && isset($oCookieRegUser->loginExpire)) {
					$oCookieUser->loginExpire = $oCookieRegUser->loginExpire;
					$modified = true;
				}
			}
		}
		if ($oCookieRegUser && isset($oCookieRegUser->loginExpire)) {
			$oCookieUser->unionid = $oCookieRegUser->unionid;
			$oCookieUser->nickname = $oCookieRegUser->nickname;
			$oCookieUser->loginExpire = $oCookieRegUser->loginExpire;
		}
		/* 将用户信息保存在cookie中 */
		if ($modified) {
			$this->setCookieUser($siteId, $oCookieUser);
		}

		return $oCookieUser;
	}
	/**
	 * 绑定站点第三方认证用户
	 *
	 * 如果是注册登录状态，查找站点的主访客账号，并恢复账号，如果不存在就创建一个，在主访客账号上绑定公众号信息，如果绑定不成功报异常
	 * 如果不是注册状态，查找当前站点下的访客账号中是否有已经和公众号关联的主账号
	 */
	private function _bindSiteSnsUser($siteId, $snsName, $snsUser, $oCookieUser, $oCookieRegUser = false) {
		$modelSiteUser = $this->model('site\user\account');

		if ($oCookieRegUser) {
			/* 已登录状态，切换到注册账号的主访客账号 */
			$oSiteUser = $modelSiteUser->byPrimaryUnionid($siteId, $oCookieRegUser->unionid);
			if ($oCookieUser) {
				if ($oSiteUser->uid !== $oCookieUser->uid) {
					throw new \Exception('数据错误，注册主访客账号与当前访客账号不一致');
				}
			}
			if (empty($oSiteUser->{$snsName . '_openid'})) {
				$dbSnsUser = $this->_getDbSnsUser($siteId, $snsName, $snsUser);
				$modelSiteUser->bindSns($oSiteUser, $snsName, $dbSnsUser);
			} else if ($oSiteUser->{$snsName . '_openid'} !== $snsUser->openid) {
				/* 用户先用一个微信号做了访问，注册了账号；切换了微信号，用之前的账号做了登录 */
				throw new \Exception('数据错误，注册主访客账号已经绑定其他公众号用户');
			}
		} else {
			if ($oCookieUser === false) {
				/* 不存在访客账号，查找公众号用户的主访客账号 */
				$oSiteUser = $modelSiteUser->byPrimaryOpenid($siteId, $snsName, $snsUser->openid);
				if ($oSiteUser === false) {
					/* 公众号用户主访客账号不存在，创建 */
					/* 如果openid已经绑定过注册账号，是否要自动关联注册账号呢？ */
					$dbSnsUser = $this->_getDbSnsUser($siteId, $snsName, $snsUser);
					$propsAccount = [
						'ufrom' => $snsName,
						$snsName . '_openid' => $dbSnsUser->openid,
						'nickname' => $dbSnsUser->nickname,
						'headimgurl' => $dbSnsUser->headimgurl,
						'is_' . $snsName . '_primary' => 'Y',
					];
					$oSiteUser = $modelSiteUser->blank($siteId, true, $propsAccount);
				} else {
					// 什么也不做
				}
			} else {
				/* 存在访客账号 */
				if (!isset($oCookieUser->sns->{$snsName})) {
					/* 当前帐号，没有绑定过公众号用户 */
					/* 查找公众号用户的主访客账号 */
					$oSiteUser = $modelSiteUser->byPrimaryOpenid($siteId, $snsName, $snsUser->openid);
					if ($oSiteUser === false) {
						/* 不存在公众号用户主访客账号，将当前帐号更新为公众号用户主访客账号 */
						$dbSnsUser = $this->_getDbSnsUser($siteId, $snsName, $snsUser);
						$oSiteUser = $modelSiteUser->byId($oCookieUser->uid);
						if ($oSiteUser === false) {
							/* 创建新账号 */
							$propsAccount = [
								'uid' => $oCookieUser->uid,
								'ufrom' => $snsName,
								$snsName . '_openid' => $dbSnsUser->openid,
								'nickname' => $dbSnsUser->nickname,
								'headimgurl' => $dbSnsUser->headimgurl,
								'is_' . $snsName . '_primary' => 'Y',
							];
							$oSiteUser = $modelSiteUser->blank($siteId, true, $propsAccount);
						} else {
							/* 在现有账号上绑定公众号用户 */
							$modelSiteUser->bindSns($oSiteUser, $snsName, $dbSnsUser);
						}
					} else {
						/* 访客账号切换成已经绑定过openid的主站点用户 */
						/* 存在公众号用户主访客账号，更新现账号，切换到公众号用户主访客账号 */
						$dbSnsUser = $this->_getDbSnsUser($siteId, $snsName, $snsUser);
						$siteCurrentUser = $modelSiteUser->byId($oCookieUser->uid);
						if ($siteCurrentUser === false) {
							/* 创建新账号，保留数据，绑定公众号用户 */
							$propsAccount = [
								'uid' => $oCookieUser->uid,
								'ufrom' => $snsName,
								$snsName . '_openid' => $dbSnsUser->openid,
								'nickname' => $dbSnsUser->nickname,
								'headimgurl' => $dbSnsUser->headimgurl,
							];
							$modelSiteUser->blank($siteId, true, $propsAccount);
						} else {
							/* 在现有账号上绑定公众号用户 */
							$modelSiteUser->bindSns($siteCurrentUser, $snsName, $dbSnsUser);
						}
					}
				} else {
					/* 当前帐号已经绑定过公众号用户 */
					$cookieSnsUser = $oCookieUser->sns->{$snsName};
					if ($cookieSnsUser->openid !== $snsUser->openid) {
						/* 切换到公众号用户主访客账号 */
						$oSiteUser = $modelSiteUser->byPrimaryOpenid($siteId, $snsName, $snsUser->openid);
						if ($oSiteUser === false) {
							$dbSnsUser = $this->_getDbSnsUser($siteId, $snsName, $snsUser);
							$propsAccount = [
								'ufrom' => $snsName,
								$snsName . '_openid' => $dbSnsUser->openid,
								'nickname' => $dbSnsUser->nickname,
								'headimgurl' => $dbSnsUser->headimgurl,
								'is_' . $snsName . '_primary' => 'Y',
							];
							$oSiteUser = $modelSiteUser->blank($siteId, true, $propsAccount);
						}
					} else {
						/* 检查是否一致，不一致就更新 */
					}
				}
			}
		}
		/**
		 * 如果指定和openid绑定的主站点用户有注册账号，而且它不是主注册账号，而且主注册账号绑定了相同的openid，切换到注册账号上
		 * 用户在微信上就不用登录了
		 */
		if (!empty($oSiteUser->unionid)) {
			if (isset($oSiteUser->is_reg_primary) && $oSiteUser->is_reg_primary !== 'Y') {
				$oSiteRegUser = $modelSiteUser->byPrimaryUnionid($siteId, $oSiteUser->unionid);
				if ($oSiteRegUser && isset($oSiteRegUser->{$snsName . '_openid'})) {
					if ($oSiteRegUser->{$snsName . '_openid'} === $snsUser->openid) {
						/* 将主注册用户设置为主公众号用户 */
						if (isset($oSiteRegUser->{'is_' . $snsName . '_primary'}) && $oSiteRegUser->{'is_' . $snsName . '_primary'} !== 'Y') {
							$modelSiteUser->setAsSnsPrimary($oSiteRegUser, $snsName);
						}
						$oSiteUser = $oSiteRegUser;
					}
				}
			}
		}
		/* 更新cookie信息 */
		$oCookieUser === false && $oCookieUser = new \stdClass;
		$oCookieUser->uid = $oSiteUser->uid;
		$oCookieUser->expire = time() + (86400 * TMS_COOKIE_SITE_USER_EXPIRE);
		!isset($oCookieUser->sns) && $oCookieUser->sns = new \stdClass;
		$oCookieUser->nickname = isset($snsUser->nickname) ? $snsUser->nickname : '';
		$oCookieUser->sns->{$snsName} = $snsUser;

		return $oCookieUser;
	}
	/**
	 * 获取或者新建公众号用户信息
	 */
	private function &_getDbSnsUser($siteId, $snsName, &$snsUser) {
		$modelSnsUser = \TMS_App::M('sns\\' . $snsName . '\fan');
		$modelSns = \TMS_APP::M('sns\\' . $snsName);

		$snsConfig = $modelSns->bySite($siteId);
		if ($snsConfig === false || $snsConfig->joined !== 'Y') {
			$snsSiteId = 'platform';
		} else {
			$snsSiteId = $siteId;
		}

		$dbSnsUser = $modelSnsUser->byOpenid($snsSiteId, $snsUser->openid, 'openid,nickname,headimgurl');
		if ($dbSnsUser === false) {
			$propsSns = [];
			$propsSns['nickname'] = isset($snsUser->nickname) ? $snsUser->nickname : '';
			$propsSns['headimgurl'] = isset($snsUser->headimgurl) ? $snsUser->headimgurl : '';
			$propsSns['sex'] = isset($snsUser->sex) ? $snsUser->sex : '';
			$propsSns['city'] = isset($snsUser->city) ? $snsUser->city : '';
			isset($snsUser->country) && $propsSns['country'] = $snsUser->country;
			isset($snsUser->province) && $propsSns['province'] = $snsUser->province;
			$dbSnsUser = $modelSnsUser->blank($snsSiteId, $snsUser->openid, true, $propsSns);
		} else {
			$snsUser->nickname = isset($dbSnsUser->nickname) ? $dbSnsUser->nickname : '';
		}

		return $dbSnsUser;
	}
	/**
	 * 绑定自定义用户
	 *
	 * @param string $siteId
	 * @param object $member
	 *
	 * @return object cookieUser
	 */
	public function &bindMember($siteId, $member) {
		$modelSiteUser = \TMS_App::M('site\user\account');

		/* cookie中缓存的用户信息 */
		$oCookieUser = $this->getCookieUser($siteId);
		if ($oCookieUser === false) {
			$oSiteUser = $modelSiteUser->blank($siteId, true, ['ufrom' => 'member']);
			/* 新的cookie用户 */
			$oCookieUser = new \stdClass;
			$oCookieUser->uid = $oSiteUser->uid;
		} else {
			$oSiteUser = $modelSiteUser->byId($oCookieUser->uid);
			if ($oSiteUser === false) {
				$oSiteUser = $modelSiteUser->blank($siteId, true, ['uid' => $oCookieUser->uid, 'ufrom' => 'member']);
			}
		}
		/* 更新认证用户信息 */
		if ($oSiteUser->uid !== $member->userid) {
			$this->update('xxt_site_member', ['userid' => $oSiteUser->uid], "siteid='$siteId' and id=$member->id");
		}
		/* 更新cookie信息 */
		if (empty($oCookieUser->nickname)) {
			$oCookieUser->nickname = isset($member->name) ? $member->name : (isset($member->mobile) ? $member->mobile : (isset($member->email) ? $member->email : ''));
			$modelSiteUser->update(
				'xxt_site_account',
				['nickname' => $oCookieUser->nickname],
				["uid" => $oCookieUser->uid]
			);
		}
		$oCookieUser->expire = time() + (86400 * TMS_COOKIE_SITE_USER_EXPIRE);
		!isset($oCookieUser->members) && $oCookieUser->members = new \stdClass;
		$oCookieUser->members->{$member->schema_id} = $member;

		/* 将用户信息保存在cookie中 */
		$this->setCookieUser($siteId, $oCookieUser);

		return $oCookieUser;
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
		$_COOKIE[G_COOKIE_PREFIX . $name] = $value;

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
		$oCookieUser = $user;
		$oCookieUser = json_encode($oCookieUser);
		$encoded = $this->encrypt($oCookieUser, 'ENCODE', $cookiekey);
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
		$oCookieUser = $this->encrypt($encoded, 'DECODE', $cookiekey);
		$oCookieUser = json_decode($oCookieUser);

		return $oCookieUser;
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
		$oCookieUser = $this->encrypt($encoded, 'DECODE', $cookiekey);
		$oCookieUser = json_decode($oCookieUser);

		return $oCookieUser;
	}
	/**
	 * 站点注册用户信息
	 */
	public function setCookieRegUser($user) {
		$cookiekey = $this->getCookieKey('platform');
		$oCookieUser = $user;
		$oCookieUser = json_encode($oCookieUser);
		$encoded = $this->encrypt($oCookieUser, 'ENCODE', $cookiekey);
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
	 * 站点用户退出登录状况
	 * 清除cookie中所有和注册账号绑定的站点访问用户
	 */
	public function quitRegUser() {
		/*清除站点*/
		$sites = $this->siteList(true);
		$modelAct = $this->model('site\user\account');
		foreach ($sites as $siteId) {
			if ($oCookieUser = $this->getCookieUser($siteId)) {
				//$account = $modelAct->byId($oCookieUser->uid);
				//if (!empty($account->unionid)) {
				$this->cleanCookieUser($siteId);
				//}
			}
		}

		/* 清除注册账号信息 */
		$this->mySetcookie("_site_user_login", '', time() - 3600);

		return true;
	}
	/**
	 * 更新cookie记录的所有用户的信息
	 */
	public function resetAllCookieUser() {
		if (!$this->getCookieRegUser()) {
			/* 设置注册账号 */
			/* 更新访客账号和注册账号的关联 */
			$unbounds = [];
			$unionid = false;
			$modelAct = $this->model('site\user\account');
			$sites = $this->siteList(true);
			foreach ($sites as $siteId) {
				$oCookieUser = $this->getCookieUser($siteId);
				if ($oCookieUser) {
					$account = $modelAct->byId($oCookieUser->uid);
					if (empty($account->unionid)) {
						$unbounds[$siteId] = $oCookieUser;
					} else {
						if ($unionid === false) {
							$unionid = $account->unionid;
						} else {
							if ($account->unionid !== $unionid) {
								// 清除不是同一注册用户下的数据
								$this->cleanCookieUser($siteId);
							}
						}
					}
				}
			}
			if ($unionid) {
				$modelReg = $this->model('site\user\registration');
				if (count($unbounds)) {
					// 补充站点访客信息
					foreach ($unbounds as $siteId => $oCookieUser) {
						$modelReg->update('xxt_site_account', ['unionid' => $unionid], ['uid' => $oCookieUser->uid]);
						$this->setCookieUser($siteId, $oCookieUser);
					}
				}
				// 补充注册账号信息
				$registration = $modelReg->byId($unionid);
				$oCookieRegUser = new \stdClass;
				$oCookieRegUser->unionid = $unionid;
				$oCookieRegUser->uname = $registration->uname;
				$oCookieRegUser->nickname = $registration->nickname;
				$this->setCookieRegUser($oCookieRegUser);
			}
		}
	}
	/**
	 * 获得完整的访客用户信息，包括：公众号用户信息，自定义用户信息
	 *
	 * @param string $uid
	 *
	 * @return object
	 */
	public function &buildFullUser($uid) {
		$modelAct = $this->model('site\user\account');
		$oSiteUser = $modelAct->byId($uid);
		if ($oSiteUser === false) {
			throw new \Exception('指定的站点访客账户不存在');
		}

		$fullUser = new \stdClass;
		$fullUser->uid = $oSiteUser->uid;
		$fullUser->nickname = $oSiteUser->nickname;

		/* 站点自定义用户信息 */
		$modelMem = $this->model('site\user\member');
		$members = $modelMem->byUser($oSiteUser->uid);
		!empty($members) && $fullUser->members = new \stdClass;
		foreach ($members as $member) {
			$fullUser->members->{$member->schema_id} = $member;
		}
		$fullUser->sns = new \stdClass;
		/* wx用户 */
		if (!empty($oSiteUser->wx_openid)) {
			$modelWxFan = \TMS_App::M('sns\wx\fan');
			$fullUser->sns->wx = $modelWxFan->byOpenid($oSiteUser->siteid, $oSiteUser->wx_openid);
		}
		/* yx用户 */
		if (!empty($oSiteUser->yx_openid)) {
			$modelYxFan = \TMS_App::M('sns\yx\fan');
			$fullUser->sns->yx = $modelYxFan->byOpenid($oSiteUser->siteid, $oSiteUser->yx_openid);
		}
		/* qy用户 */
		if (!empty($oSiteUser->qy_openid)) {
			$modelQyFan = \TMS_App::M('sns\qy\fan');
			$fullUser->sns->qy = $modelQyFan->byOpenid($account->siteid, $oSiteUser->qy_openid);
		}

		return $fullUser;
	}
	/**
	 * 检查是否可以切换当前客户端的注册用户
	 *
	 * 如果登录账号已经绑定过wx_openid，但是和当前用户的wx_openid不一致，不允许用户登录
	 *
	 * @param object $oRegUser
	 *
	 */
	public function canShiftRegUser($oRegUser) {
		$modelAct = $this->model('site\user\account');
		/* 处理cookie中已经存在的访客用户信息 */
		$sites = $this->siteList(true);
		foreach ($sites as $siteId) {
			$oRegPrimary = $modelAct->byPrimaryUnionid($siteId, $oRegUser->unionid);
			if ($oRegPrimary) {
				$oBeforeCookieuser = $this->getCookieUser($siteId);
				if ($oBeforeCookieuser) {
					$oCookieAccount = $modelAct->byId($oBeforeCookieuser->uid);
					/* 指定站点下，已经存在主访客账号 */
					if ($oCookieAccount) {
						if ($oCookieAccount->wx_openid !== $oRegPrimary->wx_openid) {
							return [false, '1个注册账号，只能够和1个微信号绑定'];
						}
					}
				}
			}
		}

		return [true];
	}
	/**
	 * 切换当前客户端的注册用户
	 *
	 * @param object $oRegUser
	 * @param boolean $loadFromDb 是否从数据库中加载访客账号
	 */
	public function shiftRegUser($oRegUser, $loadFromDb = true) {
		$aTestResult = $this->canShiftRegUser($oRegUser);
		if ($aTestResult[0] === false) {
			throw new \Exception($aTestResult[1]);
		}

		$modelAct = $this->model('site\user\account');
		$current = time();
		$loginExpire = $current + (86400 * TMS_COOKIE_SITE_LOGIN_EXPIRE);

		/* cookie中保留注册信息 */
		$oCookieRegUser = new \stdClass;
		$oCookieRegUser->unionid = $oRegUser->unionid;
		$oCookieRegUser->uname = $oRegUser->uname;
		$oCookieRegUser->nickname = $oRegUser->nickname;
		$oCookieRegUser->loginExpire = $loginExpire;
		$this->setCookieRegUser($oCookieRegUser);

		/* 处理cookie中已经存在的访客用户信息 */
		$beforeCookieusers = [];
		$sites = $this->siteList(true);
		foreach ($sites as $siteId) {
			$beforeCookieusers[$siteId] = $this->getCookieUser($siteId);
			$this->cleanCookieUser($siteId);
		}

		/* 从数据库中获得注册账号下管理的所有访客账号 */
		$primaryAccounts = []; // 和当前注册账号关联的主访客账号
		if ($loadFromDb) {
			/* 获取和注册账号关联的主访客账号 */
			$accounts = $modelAct->byUnionid($oRegUser->unionid, ['is_reg_primary' => 'Y']);
			if (count($accounts)) {
				$modelMem = $this->model('site\user\member');
				foreach ($accounts as $account) {
					$oCookieUser = new \stdClass;
					$oCookieUser->uid = $account->uid;
					$oCookieUser->nickname = $account->nickname;
					$oCookieUser->loginExpire = $loginExpire;
					/* 站点自定义用户信息 */
					$members = $modelMem->byUser($account->uid, ['fields' => 'id,schema_id,name,mobile,email']);
					!empty($members) && $oCookieUser->members = new \stdClass;
					foreach ($members as $member) {
						$schemaId = $member->schema_id;
						unset($member->schema_id);
						$oCookieUser->members->{$schemaId} = $member;
					}
					$oCookieUser->sns = new \stdClass;
					/* wx用户 */
					if (!empty($account->wx_openid)) {
						if (!isset($modelWxFan)) {
							$modelWxFan = \TMS_App::M('sns\wx\fan');
						}
						$oCookieUser->sns->wx = $modelWxFan->byOpenid($account->siteid, $account->wx_openid, 'openid,nickname,headimgurl');
					}
					/* yx用户 */
					if (!empty($account->yx_openid)) {
						if (!isset($modelYxFan)) {
							$modelYxFan = \TMS_App::M('sns\yx\fan');
						}
						$oCookieUser->sns->yx = $modelYxFan->byOpenid($account->siteid, $account->yx_openid, 'openid,nickname,headimgurl');
					}
					/* qy用户 */
					if (!empty($account->qy_openid)) {
						if (!isset($modelQyFan)) {
							$modelQyFan = \TMS_App::M('sns\qy\fan');
						}
						$oCookieUser->sns->qy = $modelQyFan->byOpenid($account->siteid, $account->qy_openid, 'openid,nickname,headimgurl');
					}
					/* 缓存数据，方便进行后续判断 */
					$primaryAccounts[$account->siteid] = $oCookieUser;
				}
			}
		}

		/* 是否保留之前存在cookieuser */
		foreach ($beforeCookieusers as $siteId => $beforeCookieuser) {
			if (!$beforeCookieuser) {
				continue;
			}
			$oCookieAccount = $modelAct->byId($beforeCookieuser->uid);
			if (isset($primaryAccounts[$siteId])) {
				/* 指定站点下，已经存在主访客账号 */
				if ($oCookieAccount) {
					if (empty($oCookieAccount->unionid)) {
						/* 如果有wx_openid，但是和主注册账号的wx_opendi不一致怎么办？ */
						/* 作为关联访客账号绑定到注册账号 */
						$modelAct->update('xxt_site_account', ['unionid' => $oRegUser->unionid], ['uid' => $beforeCookieuser->uid]);
					} else {
						if ($oCookieAccount->unionid !== $oRegUser->unionid) {
							/* 同一个站点，绑定了不同注册账号，从cookie中清除 */
							// cookie中对应的数据已经被数据库中的主访客账号覆盖
						} else {
							/* 同一个站点，绑定了相同注册账号，作为关联账号，从cookie中清除*/
						}
					}
				} else {
					/* 同一个站点下，未保存在数据库中，作为关联账号保存*/
					$props = [
						'uid' => $beforeCookieuser->uid,
						'unionid' => $oRegUser->unionid,
						'nickname' => $oRegUser->nickname,
					];
					$modelAct->blank($siteId, true, $props);
				}
			} else {
				/* 指定站点下，不存在主访客账号 */
				if ($oCookieAccount) {
					if (empty($oCookieAccount->unionid)) {
						// 站点下，新的主访客账号
						$modelAct->update('xxt_site_account', ['unionid' => $oRegUser->unionid, 'is_reg_primary' => 'Y'], ['uid' => $beforeCookieuser->uid]);
					} else {
						if ($oCookieAccount->unionid !== $oRegUser->unionid) {
							/* 其他注册账号下的站点，当前注册账号没有访问过，从cookie中清除 */
							$this->cleanCookieUser($siteId);
						} else {
							/* 是注册账号下的访客账号，但是不是主账号，设置为主账号 */
							$modelAct->update('xxt_site_account', ['is_reg_primary' => 'Y'], ['uid' => $beforeCookieuser->uid]);
						}
					}
				} else {
					/* 新的主访客账号 */
					$props = [
						'uid' => $beforeCookieuser->uid,
						'unionid' => $oRegUser->unionid,
						'nickname' => $oRegUser->nickname,
						'is_reg_primary' => 'Y',
					];
					$modelAct->blank($siteId, true, $props);
				}
			}
		}

		return $oCookieRegUser;
	}
	/**
	 * 获得当前用户在平台对应的所有站点和站点访客用户信息
	 */
	public function &siteList($onlyId = false) {
		$sites = [];
		foreach ($_COOKIE as $key => $val) {
			if (preg_match('/xxt_site_(.*?)_fe_user/', $key, $matches)) {
				$siteId = $matches[1];
				if ($onlyId === true) {
					$sites[] = $siteId;
				} else {
					if (!isset($modelSite)) {
						$modelSite = $this->model('site');
					}
					if ($site = $modelSite->byId($siteId, ['fields' => 'id,name'])) {
						$sites[] = $site;
					}
				}
			}
		}

		return $sites;
	}
}