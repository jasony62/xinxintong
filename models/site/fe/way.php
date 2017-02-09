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
			$cookieRegUser = $this->getCookieRegUser();
			/* 有身份用户首次访问，若已经有绑定的站点用户，获取站点用户；否则，创建持久化的站点用户，并绑定关系 */
			foreach ($auth['sns'] as $snsName => $snsUser) {
				$modelSns = $this->M('sns\\' . $snsName);
				$siteSns = $modelSns->bySite($siteId);
				$cookieUser = $this->_bindSiteSnsUser($siteId, $snsName, $snsUser, $cookieUser, $cookieRegUser);
			}
			$modified = true;
		} else if ($cookieUser === false) {
			/* 无访客身份用户首次访问 */
			$modelAct = $this->M('site\user\account');
			$cookieRegUser = $this->getCookieRegUser();
			if ($cookieRegUser) {
				/* 当前为登录状态，创建持久化用户，且作为绑定的主访客账号 */
				$props = [
					'unionid' => $cookieRegUser->unionid,
					'is_reg_primary' => 'Y',
				];
				if (!empty($cookieRegUser->nickname)) {
					$props['nickname'] = $cookieRegUser->nickname;
				}
				$account = $modelAct->blank($siteId, true, $props);
			} else {
				/* 当前为未登录状态，创建非持久化的站点用户 */
				$account = $modelAct->blank($siteId, false);
			}
			$cookieUser = new \stdClass;
			$cookieUser->uid = $account->uid;
			$cookieUser->nickname = $account->nickname;
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
	private function _bindSiteSnsUser($siteId, $snsName, $snsUser, $cookieUser, $cookieRegUser = false) {
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
				// 有可能返回多条数据怎么办？
				$siteUser = $modelSiteUser->byOpenid($siteId, $snsName, $dbSnsUser->openid);
				if ($siteUser === false) {
					/* 数据库没有保存当前访客信息 */
					$propsAccount = [
						'ufrom' => $snsName,
						$snsName . '_openid' => $dbSnsUser->openid,
						'nickname' => $dbSnsUser->nickname,
						'headimgurl' => $dbSnsUser->headimgurl,
					];
					if ($cookieRegUser) {
						/* 设置为和注册账号绑定的主访客账号 */
						$propsAccount['unionid'] = $cookieRegUser->unionid;
						$propsAccount['is_reg_primary'] = 'Y';
					}
					$siteUser = $modelSiteUser->blank($siteId, true, $propsAccount);
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
						$propsAccount = [
							'uid' => $cookieUser->uid,
							'ufrom' => $snsName,
							$snsName . '_openid' => $dbSnsUser->openid,
							'nickname' => $dbSnsUser->nickname,
							'headimgurl' => $dbSnsUser->headimgurl,
						];
						if ($cookieRegUser) {
							/* 设置为和注册账号绑定的主访客账号 */
							$propsAccount['unionid'] = $cookieRegUser->unionid;
							$propsAccount['is_reg_primary'] = 'Y';
						}
						$siteUser = $modelSiteUser->blank($siteId, true, $propsAccount);
					}
				} else {
					/* 当前访客账号的信息已经在数据库中 */
					if (empty($siteUser->{$snsName . '_openid'})) {
						// 不用切换访客账号，更新站点用户关联的认证用户信息
						$modelSiteUser->update(
							'xxt_site_account',
							[$snsName . '_openid' => $dbSnsUser->openid],
							["uid" => $siteUser->uid]
						);
					} else {
						if ($dbSnsUser->openid !== $siteUser->{$snsName . '_openid'}) {
							/* 需要切换访客账号 */
							$siteUserAssocOpenid = $modelSiteUser->byOpenid($siteId, $snsName, $dbSnsUser->openid);
							if ($siteUserAssocOpenid) {
								// 切换到公众号账号关联的访客账号
								$siteUser = $siteUserAssocOpenid;
							} else {
								// 创建和公众号账号关联的新访客账号
								$propsAccount = [
									'ufrom' => $snsName,
									$snsName . '_openid' => $dbSnsUser->openid,
									'nickname' => $dbSnsUser->nickname,
									'headimgurl' => $dbSnsUser->headimgurl,
								];
								if ($cookieRegUser) {
									/* 设置为和注册账号绑定的主访客账号 */
									$propsAccount['unionid'] = $cookieRegUser->unionid;
									$propsAccount['is_reg_primary'] = 'Y';
								}
								$siteUser = $modelSiteUser->blank($siteId, true, $propsAccount);
							}
						}
					}
				}
			}
			if ($cookieRegUser) {
				/* 已经是登录状态，检查是否需要绑定 */
				if (empty($siteUser->unionid)) {
					/* 设置为和注册账号绑定的主访客账号 */
					$modelSiteUser->update(
						'xxt_site_account',
						['unionid' => $cookieRegUser->unionid, 'is_reg_primary' => 'Y'],
						"uid='{$siteUser->uid}'"
					);
				} else {
					if ($siteUser->unionid !== $cookieRegUser->unionid) {
						/* 同一个公众号用户对应多个注册账号怎么办？创建新访客账号 */
						$propsAccount = [
							'ufrom' => $snsName,
							$snsName . '_openid' => $dbSnsUser->openid,
							'nickname' => $dbSnsUser->nickname,
							'headimgurl' => $dbSnsUser->headimgurl,
							'unionid' => $cookieRegUser->unionid,
							'is_reg_primary' => 'Y',
						];
						$siteUser = $modelSiteUser->blank($siteId, true, $propsAccount);
					} else {
						// 同一个注册账号下的访客账号，不需要额外处理
					}
				}
			}
		} else {
			/* 数据库中没有公众号用户信息，建一个空的公众号用户账号 */
			$propsSns = array();
			isset($snsUser->nickname) && $propsSns['nickname'] = $snsUser->nickname;
			isset($snsUser->sex) && $propsSns['sex'] = $snsUser->sex;
			isset($snsUser->headimgurl) && $propsSns['headimgurl'] = $snsUser->headimgurl;
			isset($snsUser->country) && $propsSns['country'] = $snsUser->country;
			isset($snsUser->province) && $propsSns['province'] = $snsUser->province;
			isset($snsUser->city) && $propsSns['city'] = $snsUser->city;

			$propsAccount = array();
			isset($snsUser->nickname) && $propsAccount['nickname'] = $snsUser->nickname;
			isset($snsUser->headimgurl) && $propsAccount['headimgurl'] = $snsUser->headimgurl;
			$propsAccount['ufrom'] = $snsName;
			$propsAccount[$snsName . '_openid'] = $snsUser->openid;

			if ($cookieUser === false) {
				if ($cookieRegUser) {
					/* 设置为和注册账号绑定的主访客账号 */
					$propsAccount['unionid'] = $cookieRegUser->unionid;
					$propsAccount['is_reg_primary'] = 'Y';
				}
				$siteUser = $modelSiteUser->blank($siteId, true, $propsAccount);
				if ($snsName != 'qy') {
					/* 为什么不给企业号创建空的账号？会有什么问题？ */
					$dbSnsUser = $modelSnsUser->blank($snsSiteId, $snsUser->openid, true, $propsSns);
				}
				// 新的cookie用户
				$cookieUser = new \stdClass;
			} else {
				$siteUser = $modelSiteUser->byId($cookieUser->uid);
				if ($siteUser === false) {
					// 没有站点访客用户账号创建个新的
					if ($cookieRegUser) {
						/* 设置为和注册账号绑定的主访客账号 */
						$propsAccount['unionid'] = $cookieRegUser->unionid;
						$propsAccount['is_reg_primary'] = 'Y';
					}
					$siteUser = $modelSiteUser->blank($siteId, true, $propsAccount);
				} else {
					if (empty($siteUser->{$snsName . '_openid'})) {
						// 站点访客账号关联公众号账号信息
						$modelSiteUser->update(
							'xxt_site_account',
							[$snsName . '_openid' => $snsUser->openid],
							["uid" => $siteUser->uid]
						);
						$siteUser->{$snsName . '_openid'} = $snsUser->openid;
					} else {
						if ($snsUser->openid !== $siteUser->{$snsName . '_openid'}) {
							// 切换站点访客账号，没有站点访客用户账号创建新的
							if ($cookieRegUser) {
								/* 设置为和注册账号绑定的主访客账号 */
								$propsAccount['unionid'] = $cookieRegUser->unionid;
								$propsAccount['is_reg_primary'] = 'Y';
							}
							$siteUser = $modelSiteUser->blank($siteId, true, $propsAccount);
						}
					}
				}
				if ($snsName !== 'qy') {
					// 保存社交账号信息
					/* 为什么不给企业号创建空的账号？会有什么问题？ */
					$dbSnsUser = $modelSnsUser->blank($snsSiteId, $snsUser->openid, true, $propsSns);
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
	 * 站点用户退出登录状况
	 * 清除cookie中所有和注册账号绑定的站点访问用户
	 */
	public function quitRegUser() {
		/*清除站点*/
		$sites = $this->siteList();
		$modelAct = $this->model('site\user\account');
		foreach ($sites as $siteId) {
			$cookieUser = $this->getCookieUser($siteId);
			$account = $modelAct->byId($cookieUser->uid);
			if (!empty($account->unionid)) {
				$this->cleanCookieUser($siteId);
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
	 * 切换当前客户端的注册用户
	 */
	public function shiftRegUser($registration, $loadFromDb = true) {
		/* cookie中保留注册信息 */
		$cookieRegUser = new \stdClass;
		$cookieRegUser->unionid = $registration->unionid;
		$cookieRegUser->uname = $registration->uname;
		$cookieRegUser->nickname = $registration->nickname;
		$cookieRegUser->loginExpire = time() + (86400 * TMS_COOKIE_SITE_LOGIN_EXPIRE);
		$this->setCookieRegUser($cookieRegUser);

		/* 处理cookie中已经存在的访客用户信息 */
		$beforeCookieusers = [];
		$sites = $this->siteList();
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
					/* 站点自定义用户信息 */
					$members = $modelMem->byUser($account->siteid, $account->uid);
					!empty($members) && $cookieUser->members = new \stdClass;
					foreach ($members as $member) {
						$cookieUser->members->{$member->schema_id} = $member;
					}
					$cookieUser->sns = new \stdClass;
					/*wx用户*/
					if (!isset($modelWxFan)) {
						$modelWxFan = \TMS_App::M('sns\wx\fan');
					}
					$cookieUser->sns->wx = $modelWxFan->byUser($account->siteid, $account->uid);
					/*yx用户*/
					if (!isset($modelYxFan)) {
						$modelYxFan = \TMS_App::M('sns\yx\fan');
					}
					$cookieUser->sns->yx = $modelYxFan->byUser($account->siteid, $account->uid);
					/* 在cookie中保留访客用户信息 */
					$this->setCookieUser($account->siteid, $cookieUser);
					/* 缓存数据，方便进行后续判断 */
					$primaryAccounts[$account->siteid] = $cookieUser;
				}
			}
		}

		/* 是否保留之前存在cookieuser */
		$modelAct = $this->model('site\user\account');
		foreach ($beforeCookieusers as $siteId => $beforeCookieuser) {
			$account = $modelAct->byId($beforeCookieuser->uid);
			if (isset($primaryAccounts[$account->siteid])) {
				/* 指定站点下，已经存在主访客账号 */
				if ($account) {
					if (empty($account->unionid)) {
						/* 作为关联访客账号绑定到注册账号 */
						$modelAct->update('xxt_site_account', ['unionid' => $unionid, 'assoc_id' => $primaryAccounts[$account->siteid]->uid], ['uid' => $beforeCookieuser->uid]);
					} else {
						if ($account->unionid !== $registration->unionid) {
							/* 同一个站点，绑定了不同注册账号，从cookie中清除 */
							// cookie中对应的数据已经被数据库中的主访客账号覆盖
						} else {
							/* 同一个站点，绑定了相同注册账号，作为关联账号，从cookie中清除*/
							$modelAct->update('xxt_site_account', ['assoc_id' => $primaryAccounts[$account->siteid]->uid], ['uid' => $beforeCookieuser->uid]);
						}
					}
				} else {
					/* 同一个站点下，未保存在数据库中，作为关联账号保存*/
					$props = [
						'uid' => $beforeCookieuser->uid,
						'unionid' => $registration->unionid,
						'assoc_id' => $primaryAccounts[$account->siteid]->uid,
						'nickname' => $registration->nickname,
					];
					$modelAct->create($siteId, '', '', $props);
				}
			} else {
				/* 指定站点下，不存在主访客账号 */
				if ($account) {
					if (empty($account->unionid)) {
						// 站点下，新的主访客账号
						$modelAct->update('xxt_site_account', ['unionid' => $unionid, 'is_reg_primary' => 'Y'], ['uid' => $beforeCookieuser->uid]);
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
						'is_reg_primary' => 'Y',
						'nickname' => $registration->nickname,
					];
					$modelAct->create($siteId, '', '', $props);
				}
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