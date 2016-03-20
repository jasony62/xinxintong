<?php
namespace site\fe;
/**
 * who are you
 */
class way_model extends \TMS_MODEL {
	/**
	 * 返回当前访问用户的信息
	 *
	 * 1、微信、易信客户端，且站点绑定了微信或易信公众，且支持OAuth接口，那么先获得openid
	 * 如果用户不是关注用户，创建一个空的关注用户
	 * 如果用户是首次访问，创建一个空的注册用户
	 * 建立关注用户和注册用户的管理
	 * 2、浏览器
	 * 如果用户是首次访问，创建一个空的注册用户
	 *
	 */
	public function who($siteId, $auth = array()) {
		$requireUpdate = false;
		$current = time();
		/* cookie中缓存的用户信息 */
		$siteUser = $this->getCookieUser($siteId);
		if ($siteUser === false) {
			$modelAct = \TMS_APP::M('site\user\account');
			if (!empty($auth)) {
				/* 如果是一个可以确认身份的用户访问，创建一个空用户 */
				$account = $modelAct->blank($siteId, true);
				$requireUpdate = true;
			} else {
				$account = $modelAct->blank($siteId, false);
			}
			$siteUser = new \stdClass;
			$siteUser->uid = $account->uid;
			$siteUser->nickname = '';
			$siteUser->expire = $current + (86400 * TMS_COOKIE_SITE_USER_EXPIRE);
		}
		/* 第三方认证用户信息 */
		if (isset($auth['sns'])) {
			empty($siteUser->sns) && $siteUser->sns = new \stdClass;
			foreach ($auth['sns'] as $key => $value) {
				$siteUser->sns->{$key} = $value;
				$this->_bindSnsUser($siteId, $siteUser, $key, $value);
			}
			$requireUpdate = true;
		}
		/*将信息保存在cookie中*/
		if ($requireUpdate) {
			$this->setCookieUser($siteId, $siteUser);
		}

		return $siteUser;
	}
	/**
	 * 检查指定的用户是否已经登录
	 */
	public function isLogined($siteId, $who) {
		/*如果已经超过有效期，认证不通过*/
		if (empty($who->loginExpire) || $who->loginExpire < time()) {
			$this->mySetcookie("_site_{$siteId}_fe_login", '', time() - 3600);
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
	 * 设置用户登录信息
	 */
	public function setCookieLogin($siteId, $who) {
		/*通过cookie返回登录状态*/
		$login = new \stdClass;
		$login->nickname = empty($who->nickname) ? '匿名' : $who->nickname;
		$json = $this->toJson($login);
		$this->mySetcookie("_site_{$siteId}_fe_login", $json);

		return true;
	}
	/**
	 * 清除用户登录信息
	 */
	public function cleanCookieLogin($siteId) {
		$this->mySetcookie("_site_{$siteId}_fe_login", '', time() - 3600);
		return true;
	}
	/**
	 * 绑定公众号关注用户
	 */
	private function _bindSnsUser($siteId, &$siteUser, $snsName, $snsUser) {
		/* 指定了当前用户的openid */
		$modelFan = \TMS_App::M('site\sns\\' . $snsName . 'fan');
		if ($fan = $modelFan->byOpenid($siteId, $snsUser->openid, 'userid,nickname,headimgurl,sex,country,province,city')) {
			if ($fan->userid !== $siteUser->uid) {
				/*更新用户绑定关系*/
				$modelFan->update(
					'xxt_site_wxfan',
					array('userid' => $siteUser->uid),
					"siteid='$siteId' and openid='{$snsUser->openid}'"
				);
			}
			unset($fan->userid);
		} else {
			/* 如果openid不是关注用户，建一个空的关注用户 */
			$options = array('userid' => $siteUser->uid);
			isset($siteUser->headimgurl) && $options['headimgurl'] = $siteUser->headimgurl;
			isset($siteUser->sex) && $options['sex'] = $siteUser->sex;
			isset($siteUser->country) && $options['country'] = $siteUser->country;
			isset($siteUser->province) && $options['province'] = $siteUser->province;
			isset($siteUser->city) && $options['city'] = $siteUser->city;
			$fan = $modelFan->blank($siteId, $snsUser->openid, true, $options);
			unset($fan->userid);
			unset($fan->siteid);
			unset($fan->subscribe_at);
			unset($fan->sync_at);
		}
		/* 更新站点用户信息 */
		$modelUser = \TMS_App::M('site\user\account');
		if ($user = $modelUser->byId($siteUser->uid)) {
			if (empty($siteUser->uname)) {
				$siteUser->nickname = $fan->nickname;
				$modelFan->update(
					'xxt_site_account',
					array('nickname' => $fan->nickname),
					"uid='$siteUser->uid'"
				);
			}
		} else {
			$uname = $siteUser->uname;
			$modelUser->create($siteId, $uname, '', array('uid' => $siteUser->uid));
			$siteUser->nickname = $nickname;
		}

		$siteUser->sns->{$snsName} = $fan;

		return $siteUser;
	}
}