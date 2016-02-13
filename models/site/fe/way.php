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
	 * 更新cookie中的用户信息，延期90天
	 */
	public function who($siteId, $options = array()) {
		$current = time();
		/*cookie中缓存的注册用户*/
		$siteUser = $this->getCookieUser($siteId);
		if ($siteUser === false) {
			/*首次访问的用户*/
			$modelAct = \TMS_APP::M('site\user\account');
			$account = $modelAct->blank($siteId, true);
			$siteUser = new \stdClass;
			$siteUser->uid = $account->uid;
			$siteUser->nickname = '';
			$siteUser->expire = $current + (86400 * TMS_COOKIE_SITE_USER_EXPIRE);
		}

		/*第三方认证用户信息*/
		/*微信、易信公众号*/
		$csrc = $this->getClientSrc();
		if (in_array($csrc, array('wx', 'yx'))) {
			if (isset($options['mocker'])) {
				/*模拟关注用户*/
				$mpa = \TMS_APP::M('mp\mpaccount')->byId($siteId);
				isset($siteUser->third) && $siteUser->third->{$mpa->mpsrc} = null;
				$options['openid'] = $options['mocker'];
			}
			if (!empty($options['openid'])) {
				/*绑定关注用户*/
				$this->_bindMpFollower($siteId, $options['openid'], $siteUser);
			} else {
				$modelMpa = \TMS_APP::M('mp\mpaccount');
				$mpa = $modelMpa->byId($siteId);
				if (empty($siteUser->third->{$mpa->mpsrc})) {
					/*没有进行过绑定，先获得openid*/
					if ($oauthUrl = $modelMpa->oauthUrl($siteId)) {
						/*公众号支持oauth*/
						require_once TMS_APP_DIR . '/models/site/excep/RequireOAuth';
						throw new \site\fe\excep\RequireOAuth($oauthUrl);
					}
				} else {
					/*已经做过绑定，检查是否需要重新绑定*/
					$fan = $siteUser->third->{$mpa->mpsrc};
					if (empty($fan->subscribe_at) && $fan->_bindAt < (time() - TMS_COOKIE_SITE_USER_BIND_INTERVAL)) {
						$this->_bindMpFollower($siteId, $fan->openid, $siteUser);
					}
				}
			}
		}

		/*内置认证信息*/

		/*将信息保存在cookie中*/
		$this->setCookieUser($siteId, $siteUser);

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
	 * 发起请求的客户端
	 */
	protected function getClientSrc() {
		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		if (preg_match('/yixin/i', $user_agent)) {
			$csrc = 'yx';
		} elseif (preg_match('/MicroMessenger/i', $user_agent)) {
			$csrc = 'wx';
		} else {
			$csrc = false;
		}

		return $csrc;
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
	private function _bindMpFollower($siteId, $openid, &$siteUser) {
		/*指定了当前用户的openid*/
		$modelFan = \TMS_App::M('user/fans');
		if ($fan = $modelFan->byOpenid($siteId, $openid, 'fid,openid,nickname,headimgurl,subscribe_at,unsubscribe_at,userid')) {
			if ($fan->userid !== $siteUser->uid) {
				/*更新用户绑定关系*/
				$modelFan->update(
					'xxt_fans',
					array('userid' => $siteUser->uid),
					"fid='$fan->fid'"
				);
				if (empty($siteUser->uname)) {
					$siteUser->nickname = $fan->nickname;
					$modelFan->update(
						'xxt_site_user',
						array('nickname' => $fan->nickname),
						"uid='$siteUser->uid'"
					);
				}
			}
			unset($fan->userid);
		} else {
			/*如果openid不是关注用户，建一个空的关注用户*/
			$blank = $modelFan->blank($siteId, $openid, true, array('userid' => $siteUser->uid));
			$fan = new \stdClass;
			$fan->fid = $blank->fid;
			$fan->openid = $blank->openid;
		}
		$fan->_bindAt = time();

		empty($siteUser->third) && $siteUser->third = new \stdClass;
		$modelMpa = \TMS_APP::M('mp\mpaccount');
		$mpa = $modelMpa->byId($siteId);
		$siteUser->third->{$mpa->mpsrc} = &$fan;

		return $siteUser;
	}
}