<?php
namespace site\fe;
/**
 * who are you
 */
class way_model extends \TMS_MODEL {
	/**
	 * 返回当前用户的访客账号信息
	 */
	public function who($siteId, $auth = []) {
		$modified = false;
		/* cookie中缓存的用户信息 */
		$cookieUser = $this->getCookieUser($siteId);
		if (!empty($auth)) {
			$cookieRegUser = $this->getCookieRegUser();
			/* 有身份用户首次访问，若已经有绑定的站点用户，获取站点用户；否则，创建持久化的站点用户，并绑定关系 */
			foreach ($auth['sns'] as $snsName => $snsUser) {
				if ($cookieUser) {
					if (isset($cookieUser->sns->{$snsName}) && $cookieSnsUser = $cookieUser->sns->{$snsName}) {
						if ($cookieSnsUser->openid === $snsUser->openid) {
							continue;
						}
					}
				}
				$cookieUser = $this->_bindSiteSnsUser($siteId, $snsName, $snsUser, $cookieUser, $cookieRegUser);
			}
			$modified = true;
		} else if (empty($cookieUser)) {
			/* 无访客身份用户首次访问站点 */
			$modelSiteUser = \TMS_App::M('site\user\account');
			$cookieRegUser = $this->getCookieRegUser();
			if ($cookieRegUser) {
				/* 注册主站点访客账号，有，使用，没有，创建 */
				$siteUser = $modelSiteUser->byPrimaryUnionid($siteId, $cookieRegUser->unionid);
				if ($siteUser === false) {
					/* 当前为登录状态，创建持久化用户，且作为绑定的主访客账号 */
					$props = [
						'unionid' => $cookieRegUser->unionid,
						'is_reg_primary' => 'Y',
					];
					if (!empty($cookieRegUser->nickname)) {
						$props['nickname'] = $cookieRegUser->nickname;
					}
					$siteUser = $modelSiteUser->blank($siteId, true, $props);
				}
			} else {
				/* 未登录状态，创建非持久化的站点访客账号 */
				$siteUser = $modelSiteUser->blank($siteId, false);
			}
			$cookieUser = new \stdClass;
			$cookieUser->uid = $siteUser->uid;
			$cookieUser->nickname = $siteUser->nickname;
			$cookieUser->expire = time() + (86400 * TMS_COOKIE_SITE_USER_EXPIRE);
			$modified = true;
		} else {
			if (empty($cookieUser->loginExpire)) {
				$cookieRegUser = $this->getCookieRegUser();
				if ($cookieRegUser && isset($cookieRegUser->loginExpire)) {
					$cookieUser->loginExpire = $cookieRegUser->loginExpire;
					$modified = true;
				}
			}
		}
		/* 将用户信息保存在cookie中 */
		if ($modified) {
			$this->setCookieUser($siteId, $cookieUser);
		}

		return $cookieUser;
	}
	/**
	 * 绑定站点第三方认证用户
	 *
	 * 如果是注册登录状态，查找站点的主访客账号，并恢复账号，如果不存在就创建一个，在主访客账号上绑定公众号信息，如果绑定不成功报异常
	 * 如果不是注册状态，查找当前站点下的访客账号中是否有已经和公众号关联的主账号
	 */
	private function _bindSiteSnsUser($siteId, $snsName, $snsUser, $cookieUser, $cookieRegUser = false) {
		$modelSiteUser = \TMS_App::M('site\user\account');

		if ($cookieRegUser) {
			/* 已登录状态，切换到注册账号的主访客账号 */
			$siteUser = $modelSiteUser->byPrimaryUnionid($siteId, $cookieRegUser->unionid);
			if ($cookieUser) {
				if ($siteUser->uid !== $cookieUser->uid) {
					throw new \Exception('数据错误，注册主访客账号与当前访客账号不一致');
				}
			}
			if (empty($siteUser->{$snsName . '_openid'})) {
				$dbSnsUser = $this->_getDbSnsUser($siteId, $snsName, $snsUser);
				$modelSiteUser->bindSns($siteUser, $snsName, $dbSnsUser);
			} else if ($siteUser->{$snsName . '_openid'} !== $snsUser->openid) {
				throw new \Exception('数据错误，注册主访客账号已经绑定其他公众号用户');
			}
		} else {
			if ($cookieUser === false) {
				/* 不存在访客账号，查找公众号用户的主访客账号 */
				$siteUser = $modelSiteUser->byPrimaryOpenid($siteId, $snsName, $snsUser->openid);
				if ($siteUser === false) {
					/* 公众号用户主访客账号不存在，创建 */
					$dbSnsUser = $this->_getDbSnsUser($siteId, $snsName, $snsUser);
					$propsAccount = [
						'ufrom' => $snsName,
						$snsName . '_openid' => $dbSnsUser->openid,
						'nickname' => $dbSnsUser->nickname,
						'headimgurl' => $dbSnsUser->headimgurl,
						'is_' . $snsName . '_primary' => 'Y',
					];
					$siteUser = $modelSiteUser->blank($siteId, true, $propsAccount);
				} else {
					// 切换到公众号用户主访客账号，不需要其他处理
				}
			} else {
				/* 存在访客账号 */
				if (!isset($cookieUser->sns->{$snsName})) {
					/* 当前帐号，没有绑定过公众号用户 */
					/* 查找公众号用户的主访客账号 */
					$siteUser = $modelSiteUser->byPrimaryOpenid($siteId, $snsName, $snsUser->openid);
					if ($siteUser === false) {
						/* 不存在公众号用户主访客账号，将当前帐号更新为公众号用户主访客账号 */
						$dbSnsUser = $this->_getDbSnsUser($siteId, $snsName, $snsUser);
						$siteUser = $modelSiteUser->byId($cookieUser->uid);
						if ($siteUser === false) {
							/* 创建新账号 */
							$propsAccount = [
								'uid' => $cookieUser->uid,
								'ufrom' => $snsName,
								$snsName . '_openid' => $dbSnsUser->openid,
								'nickname' => $dbSnsUser->nickname,
								'headimgurl' => $dbSnsUser->headimgurl,
								'is_' . $snsName . '_primary' => 'Y',
							];
							$siteUser = $modelSiteUser->blank($siteId, true, $propsAccount);
						} else {
							/* 在现有账号上绑定公众号用户 */
							$modelSiteUser->bindSns($siteUser, $snsName, $dbSnsUser);
						}
					} else {
						/* 存在公众号用户主访客账号，更新现账号，切换到公众号用户主访客账号 */
						$dbSnsUser = $this->_getDbSnsUser($siteId, $snsName, $snsUser);
						$siteCurrentUser = $modelSiteUser->byId($cookieUser->uid);
						if ($siteCurrentUser === false) {
							/* 创建新账号，保留数据，绑定公众号用户 */
							$propsAccount = [
								'uid' => $cookieUser->uid,
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
					$cookieSnsUser = $cookieUser->sns->{$snsName};
					if ($cookieSnsUser->openid !== $snsUser->openid) {
						/* 切换到公众号用户主访客账号 */
						$siteUser = $modelSiteUser->byPrimaryOpenid($siteId, $snsName, $snsUser->openid);
						if ($siteUser === false) {
							$dbSnsUser = $this->_getDbSnsUser($siteId, $snsName, $snsUser);
							$propsAccount = [
								'ufrom' => $snsName,
								$snsName . '_openid' => $dbSnsUser->openid,
								'nickname' => $dbSnsUser->nickname,
								'headimgurl' => $dbSnsUser->headimgurl,
								'is_' . $snsName . '_primary' => 'Y',
							];
							$siteUser = $modelSiteUser->blank($siteId, true, $propsAccount);
						}
					} else {
						/* 检查是否一致，不一致就更新 */
					}
				}
			}
		}

		/* 更新cookie信息 */
		$cookieUser === false && $cookieUser = new \stdClass;
		$cookieUser->uid = $siteUser->uid;
		$cookieUser->expire = time() + (86400 * TMS_COOKIE_SITE_USER_EXPIRE);
		!isset($cookieUser->sns) && $cookieUser->sns = new \stdClass;
		$cookieUser->nickname = isset($snsUser->nickname) ? $snsUser->nickname : '';
		$cookieUser->sns->{$snsName} = $snsUser;

		return $cookieUser;
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
		$cookieUser = $this->getCookieUser($siteId);
		if ($cookieUser === false) {
			$siteUser = $modelSiteUser->blank($siteId, true, ['ufrom' => 'member']);
			/* 新的cookie用户 */
			$cookieUser = new \stdClass;
			$cookieUser->uid = $siteUser->uid;
		} else {
			$siteUser = $modelSiteUser->byId($cookieUser->uid);
			if ($siteUser === false) {
				$siteUser = $modelSiteUser->blank($siteId, true, ['uid' => $cookieUser->uid, 'ufrom' => 'member']);
			}
		}
		/* 更新认证用户信息 */
		if ($siteUser->uid !== $member->userid) {
			$this->update('xxt_site_member', ['userid' => $siteUser->uid], "siteid='$siteId' and id=$member->id");
		}
		/* 更新cookie信息 */
		if (empty($cookieUser->nickname)) {
			$cookieUser->nickname = isset($member->name) ? $member->name : (isset($member->mobile) ? $member->mobile : (isset($member->email) ? $member->email : ''));
			$modelSiteUser->update(
				'xxt_site_account',
				['nickname' => $cookieUser->nickname],
				["uid" => $cookieUser->uid]
			);
		}
		$cookieUser->expire = time() + (86400 * TMS_COOKIE_SITE_USER_EXPIRE);
		!isset($cookieUser->members) && $cookieUser->members = new \stdClass;
		$cookieUser->members->{$member->schema_id} = $member;

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
	 * 站点用户退出登录状况
	 * 清除cookie中所有和注册账号绑定的站点访问用户
	 */
	public function quitRegUser() {
		/*清除站点*/
		$sites = $this->siteList(true);
		$modelAct = $this->model('site\user\account');
		foreach ($sites as $siteId) {
			if ($cookieUser = $this->getCookieUser($siteId)) {
				$account = $modelAct->byId($cookieUser->uid);
				if (!empty($account->unionid)) {
					$this->cleanCookieUser($siteId);
				}
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
				$cookieUser = $this->getCookieUser($siteId);
				if ($cookieUser) {
					$account = $modelAct->byId($cookieUser->uid);
					if (empty($account->unionid)) {
						$unbounds[$siteId] = $cookieUser;
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
					foreach ($unbounds as $siteId => $cookieUser) {
						$modelReg->update('xxt_site_account', ['unionid' => $unionid], ['uid' => $cookieUser->uid]);
						$this->setCookieUser($siteId, $cookieUser);
					}
				}
				// 补充注册账号信息
				$registration = $modelReg->byId($unionid);
				$cookieRegUser = new \stdClass;
				$cookieRegUser->unionid = $unionid;
				$cookieRegUser->uname = $registration->uname;
				$cookieRegUser->nickname = $registration->nickname;
				$this->setCookieRegUser($cookieRegUser);
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
		$siteUser = $modelAct->byId($uid);
		if ($siteUser === false) {
			throw new \Exception('指定的站点访客账户不存在');
		}

		$fullUser = new \stdClass;
		$fullUser->uid = $siteUser->uid;
		$fullUser->nickname = $siteUser->nickname;

		/* 站点自定义用户信息 */
		$modelMem = $this->model('site\user\member');
		$members = $modelMem->byUser($siteUser->uid);
		!empty($members) && $fullUser->members = new \stdClass;
		foreach ($members as $member) {
			$fullUser->members->{$member->schema_id} = $member;
		}
		$fullUser->sns = new \stdClass;
		/* wx用户 */
		if (!empty($siteUser->wx_openid)) {
			$modelWxFan = \TMS_App::M('sns\wx\fan');
			$fullUser->sns->wx = $modelWxFan->byOpenid($siteUser->siteid, $siteUser->wx_openid);
		}
		/* yx用户 */
		if (!empty($siteUser->yx_openid)) {
			$modelYxFan = \TMS_App::M('sns\yx\fan');
			$fullUser->sns->yx = $modelYxFan->byOpenid($siteUser->siteid, $siteUser->yx_openid);
		}
		/* qy用户 */
		if (!empty($siteUser->qy_openid)) {
			$modelQyFan = \TMS_App::M('sns\qy\fan');
			$fullUser->sns->qy = $modelQyFan->byOpenid($account->siteid, $siteUser->qy_openid);
		}

		return $fullUser;
	}
	/**
	 * 切换当前客户端的注册用户
	 *
	 * @param object $registration
	 * @param boolean $loadFromDb 是否从数据库中加载访客账号
	 */
	public function shiftRegUser($registration, $loadFromDb = true) {
		$modelAct = $this->model('site\user\account');
		$current = time();
		$loginExpire = $current + (86400 * TMS_COOKIE_SITE_LOGIN_EXPIRE);

		/* cookie中保留注册信息 */
		$cookieRegUser = new \stdClass;
		$cookieRegUser->unionid = $registration->unionid;
		$cookieRegUser->uname = $registration->uname;
		$cookieRegUser->nickname = $registration->nickname;
		$cookieRegUser->loginExpire = $loginExpire;
		$this->setCookieRegUser($cookieRegUser);

		/* 处理cookie中已经存在的访客用户信息 */
		$beforeCookieusers = [];
		$sites = $this->siteList(true);
		foreach ($sites as $siteId) {
			$beforeCookieusers[$siteId] = $this->getCookieUser($siteId);
		}

		/* 从数据库中获得注册账号下管理的所有访客账号 */
		$primaryAccounts = []; // 和当前注册账号关联的主访客账号
		if ($loadFromDb) {
			/* 获取和注册账号关联的主访客账号 */
			$accounts = $modelAct->byUnionid($registration->unionid, ['is_reg_primary' => 'Y']);
			if (count($accounts)) {
				$modelMem = $this->model('site\user\member');
				foreach ($accounts as $account) {
					$cookieUser = new \stdClass;
					$cookieUser->uid = $account->uid;
					$cookieUser->nickname = $account->nickname;
					$cookieUser->loginExpire = $loginExpire;
					/* 站点自定义用户信息 */
					$members = $modelMem->byUser($account->uid);
					!empty($members) && $cookieUser->members = new \stdClass;
					foreach ($members as $member) {
						$cookieUser->members->{$member->schema_id} = $member;
					}
					$cookieUser->sns = new \stdClass;
					/* wx用户 */
					if (!empty($account->wx_openid)) {
						if (!isset($modelWxFan)) {
							$modelWxFan = \TMS_App::M('sns\wx\fan');
						}
						$cookieUser->sns->wx = $modelWxFan->byOpenid($account->siteid, $account->wx_openid);
					}
					/* yx用户 */
					if (!empty($account->yx_openid)) {
						if (!isset($modelYxFan)) {
							$modelYxFan = \TMS_App::M('sns\yx\fan');
						}
						$cookieUser->sns->yx = $modelYxFan->byOpenid($account->siteid, $account->yx_openid);
					}
					/* qy用户 */
					if (!empty($account->qy_openid)) {
						if (!isset($modelQyFan)) {
							$modelQyFan = \TMS_App::M('sns\qy\fan');
						}
						$cookieUser->sns->qy = $modelQyFan->byOpenid($account->siteid, $account->qy_openid);
					}
					/* 在cookie中保留访客用户信息 */
					$this->setCookieUser($account->siteid, $cookieUser);
					/* 缓存数据，方便进行后续判断 */
					$primaryAccounts[$account->siteid] = $cookieUser;
				}
			}
		}

		/* 是否保留之前存在cookieuser */
		foreach ($beforeCookieusers as $siteId => $beforeCookieuser) {
			if (!$beforeCookieuser) {
				continue;
			}
			$account = $modelAct->byId($beforeCookieuser->uid);
			if (isset($primaryAccounts[$siteId])) {
				/* 指定站点下，已经存在主访客账号 */
				if ($account) {
					if (empty($account->unionid)) {
						/* 作为关联访客账号绑定到注册账号 */
						$modelAct->update('xxt_site_account', ['unionid' => $registration->unionid], ['uid' => $beforeCookieuser->uid]);
					} else {
						if ($account->unionid !== $registration->unionid) {
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
						'unionid' => $registration->unionid,
						'nickname' => $registration->nickname,
					];
					$modelAct->blank($siteId, true, $props);
				}
			} else {
				/* 指定站点下，不存在主访客账号 */
				if ($account) {
					if (empty($account->unionid)) {
						// 站点下，新的主访客账号
						$modelAct->update('xxt_site_account', ['unionid' => $registration->unionid, 'is_reg_primary' => 'Y'], ['uid' => $beforeCookieuser->uid]);
					} else {
						if ($account->unionid !== $registration->unionid) {
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
						'unionid' => $registration->unionid,
						'nickname' => $registration->nickname,
						'is_reg_primary' => 'Y',
					];
					$modelAct->blank($siteId, true, $props);
				}
			}
		}

		return $cookieRegUser;
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