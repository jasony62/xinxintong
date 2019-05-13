<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 站点注册用户登录
 */
class login extends \site\fe\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'] = array();
		$rule_action['actions'][] = 'index';
		$rule_action['actions'][] = 'do';
		$rule_action['actions'][] = 'byRegAndWxopenid';
		$rule_action['actions'][] = 'getCaptcha';
		$rule_action['actions'][] = 'thirdList';
		$rule_action['actions'][] = 'byRegAndThird';
		$rule_action['actions'][] = 'thirdCallback';

		return $rule_action;
	}
	/**
	 * 获取支持的第三方登录应用列表
	 */
	public function thirdList_action() {
		$q = [
			'id,creator,create_at,appname,pic',
			'account_third',
			["state" => 1]
		];
		$p = ['o' => 'create_at desc'];

		$thirdApps = $this->model()->query_objs_ss($q, $p);

		return new \ResponseData($thirdApps);
	}
	/**
	 * 执行登录
	 */
	public function do_action() {
		$data = $this->getPostJson();
		if (empty($data->uname) || empty($data->password) || empty($data->pin)) {
			return new \ResponseError("登录信息不完整");
		}

		$codeSession = $_SESSION['_login_auth_code'];
		if (empty($codeSession) || strcasecmp($codeSession, $data->pin)) {
			$_SESSION['_login_auth_code'] = '';
			return new \ResponseError("验证码错误请重新输入");
		}

		$_SESSION['_login_auth_code'] = '';
		$modelWay = $this->model('site\fe\way');
		$modelReg = $this->model('site\user\registration');

		$cookieRegUser = $modelWay->getCookieRegUser();
		if ($cookieRegUser) {
			if (isset($cookieRegUser->loginExpire)) {
				return new \ResponseError("请退出当前账号再登录");
			}
			$modelWay->quitRegUser();
		}

		$oResult = $modelReg->validate($data->uname, $data->password);
		if (false === $oResult[0]) {
			return new \ResponseError($oResult[1]);
		}
		$oRegistration = $oResult[1];

		/* cookie中保留注册信息 */
		$aResult = $modelWay->shiftRegUser($oRegistration);
		if (false === $aResult[0]) {
			return new \ResponseError($aResult[1]);
		}

		/* 记录登录状态 */
		$fromip = $this->client_ip();
		$modelReg->updateLastLogin($oRegistration->unionid, $fromip);

		$oCookieUser = $modelWay->who($this->siteId);
		if ($referer = $this->myGetCookie('_user_access_referer')) {
			$oCookieUser->_loginReferer = $referer;
			$this->mySetCookie('_user_access_referer', null);
		}
		/**
		 * 支持自动登录
		 */
		if (isset($data->autologin) && $data->autologin === 'Y') {
			$expire = time() + (86400 * 365 * 10);
			$ua = $_SERVER['HTTP_USER_AGENT'];
			$token = [
				'uid' => $oRegistration->unionid,
				'email' => $oRegistration->uname,
				'password' => $oRegistration->password,
			];
			$cookiekey = md5($ua);
			$cookieToken = json_encode($token);
			$encoded = $modelWay->encrypt($cookieToken, 'ENCODE', $cookiekey);

			$this->mySetCookie('_login_auto', 'Y', $expire);
			$this->mySetCookie('_login_token', $encoded, $expire);
		}

		return new \ResponseData($oCookieUser);
	}
	/**
	 * 用指定注册账号和微信公众号openid登录
	 */
	public function byRegAndWxopenid_action() {
		$oUser = clone $this->who;
		/* 站点用户信息 */
		$modelAnt = $this->model('site\user\account');
		$oAccount = $modelAnt->byId($oUser->uid, ['fields' => 'uid,siteid,unionid,coin,headimgurl,wx_openid,is_wx_primary,is_reg_primary']);
		if (false === $oAccount) {
			return new \ObjectNotFoundError();
		}
		$userAgent = $this->userAgent(); // 客户端类型
		if (!in_array($userAgent, ['wx'])) {
			return new \ResponseError('仅支持在微信客户端下进行该操作');
		}
		if (empty($oAccount->wx_openid)) {
			return new \ResponseError('没有获得有效的用户信息，不能进行自动登录');
		}
		if (!empty($oAccount->unionid)) {
			return new \ParameterError('当前用户已经绑定注册账号，不能用指定注册账号自动登录');
		}

		$oAssignedRegUser = $this->getPostJson();

		$modelReg = $this->model('site\user\registration');
		$oRegUser = $modelReg->byId($oAssignedRegUser->unionid);
		if (false === $oRegUser) {
			return new \ObjectNotFoundError('指定的注册账号不存在');
		}

		$bFound = false;
		$aUnionids = $modelAnt->byOpenid(null, 'wx', $oAccount->wx_openid, ['is_reg_primary' => 'Y', 'fields' => 'distinct unionid']);
		foreach ($aUnionids as $oUnionid) {
			if ($oRegUser->unionid === $oUnionid->unionid) {
				$bFound = true;
				break;
			}
		}

		if (false === $bFound) {
			return new \ObjectNotFoundError('指定的注册账号没有和微信进行绑定，请通过登录操作进行绑定');
		}

		/* 更新数据 */
		$aUpdated = ['unionid' => $oRegUser->unionid];
		$oPrimaryReg = $modelAnt->byPrimaryUnionid($oAccount->siteid, $oRegUser->unionid);
		if (false === $oPrimaryReg) {
			$aUpdated['is_reg_primary'] = 'Y';
		}

		$modelAnt->update('xxt_site_account', $aUpdated, ['uid' => $oAccount->uid]);

		/* 更新cookie数据 */
		$modelWay = $this->model('site\fe\way');
		$oCookieUser = $modelWay->getCookieUser($oAccount->siteid);
		$oCookieUser->unionid = $oRegUser->unionid;
		$modelWay->setCookieUser($oAccount->siteid, $oCookieUser);

		return new \ResponseData('ok');
	}
	/**
	 * 执行第三方登录
	 */
	public function byRegAndThird_action($thirdId) {
		$modelWay = $this->model('site\fe\way');

		$cookieRegUser = $modelWay->getCookieRegUser();
		if ($cookieRegUser) {
			if (isset($cookieRegUser->loginExpire)) {
				return new \ResponseError("请退出当前账号再登录");
			}
			$modelWay->quitRegUser();
		}

		// 获取第三方应用信息
		$thirdApp = $this->model('account')->byThirdId($thirdId);
		if ($thirdApp === false || $thirdApp->state != 1) {
			return new \ObjectNotFoundError();
		}

		/* 执行第三方应用授权登录 */
		$this->_requirLoginOauth($thirdApp);

		return new \ResponseError("跳转失败");
	}
	/**
	 * 执行第三方登录后续操作
	 */
	public function thirdCallback_action($code = '', $state = '') {
		if (empty($code) || empty($state) || $state !== 'snsOAuth-third-login') {
			die('参数错误');
		}
		$thirdId = $this->myGetcookie("_thirdlogin_oauthpending");
		if (empty($thirdId)) {
			die('未获取到第三方应用标识');
		}
		// 清楚cookie
		$this->mySetcookie("_thirdlogin_oauthpending", '', time() - 3600);
		$thirdApp = $this->model('account')->byThirdId($thirdId);
		if ($thirdApp === false) {
			die('指定第三方应用不存在');
		}

		// 获得第三方应用用户信息
		$oThirdAppUser = $this->model('sns\\' . $thirdApp->app_short_name . '\proxy', $thirdApp)->getOAuthUser($code);
		if ($oThirdAppUser[0] === false) {
			die($oThirdAppUser[1]);
		}
		$oThirdAppUser = $oThirdAppUser[1];
		// 获取用户信息后，后续处理
		$result = $this->_afterThirdLoginOauth($thirdApp, $oThirdAppUser);
		if ($result[0] === false) {
			die($result[1]);
		}

		$callbackURL = $this->myGetCookie('_user_access_referer');
		if (empty($callbackURL)) {
			$callbackURL = APP_PROTOCOL . APP_HTTP_HOST . "/rest/home/home";
		} else {
			// 清楚cookie
			$this->mySetcookie("_user_access_referer", '', time() - 3600);
		}

		$this->redirect($callbackURL);
	}
	/**
	 *  跳转到第三方登陆页面
	 */
	private function _requirLoginOauth($thirdApp) {
		// $ruri = APP_PROTOCOL . APP_HTTP_HOST . '/rest/site/fe/user/login/thirdCallback';
		$ruri = 'http://' . APP_HTTP_HOST . '/rest/site/fe/user/login/thirdCallback';

		$snsProxy = $this->model('sns\\' . $thirdApp->app_short_name . '\proxy', $thirdApp);
		$oauthUrl = $snsProxy->oauthUrl($ruri, 'snsOAuth-third-login');
		
		/* 通过cookie判断是否后退进入 */
		$this->mySetcookie("_thirdlogin_oauthpending", $thirdApp->id, time() + 600);
		$this->redirect($oauthUrl);

		return false;
	}
	/**
	 * 第三方登录完成后执行后续处理
	 * 根据openid检查本站是否有账号，有账号登录此账号没有账号就自动创建
	 */
	private function _afterThirdLoginOauth($thirdApp, $oThirdAppUser) {
		$modelWay = $this->model('site\fe\way');
		$modelAcc = $this->model('account');
		$modelReg = $this->model('site\user\registration');
		// 查看此用户是否已经绑定注册账号
		$bindRregistered = false;
		$q = [
			"*",
			"account_third_user",
			["third_id" => $thirdApp->id, "openid" => $oThirdAppUser->openid, "forbidden" => 'N']
		];
		$thirdUser = $modelAcc->query_obj_ss($q);
		if ($thirdUser) {
			// 如果已经绑定账号 登录账号
			if (!empty($thirdUser->unionid)) {
				$oRegistration = $modelReg->byId($thirdUser->unionid);
				if ($oRegistration) {	
					/* cookie中保留注册信息 */
					$aResult = $modelWay->shiftRegUser($oRegistration);
					if (false === $aResult[0]) {
						return $aResult;
					}
					/* 记录登录状态 */
					$fromip = $this->client_ip();
					$modelReg->updateLastLogin($oRegistration->unionid, $fromip);

					$bindRregistered = true;
				}
			}
		}
		// 没有注册需要创建账号并绑定用户
		if ($bindRregistered === false) {
			// 是否以注册
			$oRegUser = $modelAcc->byAuthedId($oThirdAppUser->openid, $thirdApp->app_short_name, ['fields' => 'a.uid unionid,a.email uname,a.nickname,a.forbidden']);
			if ($oRegUser === false) {
				$user = $this->who;
				/* uname */	
				$uname = $thirdApp->app_short_name . '_' . uniqid();
				/* password */
				$password = tms_pwd_create_random();

				$aOptions = [];
				$aOptions['authed_from'] = $modelAcc->escape($thirdApp->app_short_name);
				$aOptions['authed_id'] = $modelAcc->escape($oThirdAppUser->openid);
				/* nickname */
				if (!empty($oThirdAppUser->nickname)) {
					$aOptions['nickname'] = $oThirdAppUser->nickname;
				} else if (isset($user->nickname)) {
					$aOptions['nickname'] = $user->nickname;
				}
				/* other options */
				$aOptions['from_ip'] = $this->client_ip();
				if (defined('THIRDLOGIN_DEFAULT_ACCOUNT_GROUP')) {
					$aOptions['group_id'] = THIRDLOGIN_DEFAULT_ACCOUNT_GROUP;
				}
				/* create registration */
				$oRegUser = $modelReg->create($this->siteId, $uname, $password, $aOptions);
				if ($oRegUser[0] === false) {
					return $oRegUser;
				}
				$oRegUser = $oRegUser[1];
			}
			if ($oRegUser->forbidden != 0) {
				return [false, '账号以停用'];
			}

			// 执行绑定，将用户插入到account_third_user表中
			// 如果已经存在则更新
			$updata = [
				"reg_time" => time(),
				"headimgurl" => isset($oThirdAppUser->headimgurl) ? $modelAcc->escape($oThirdAppUser->headimgurl) : '',
				"nickname" => isset($oThirdAppUser->nickname) ? $modelAcc->escape($oThirdAppUser->nickname) : '',
				"moble" => isset($oThirdAppUser->moble) ? $modelAcc->escape($oThirdAppUser->moble) : '',
				"email" => isset($oThirdAppUser->email) ? $modelAcc->escape($oThirdAppUser->email) : '',
				"sex" => isset($oThirdAppUser->sex) ? $modelAcc->escape($oThirdAppUser->sex) : 0,
				"city" => isset($oThirdAppUser->city) ? $modelAcc->escape($oThirdAppUser->city) : '',
				"province" => isset($oThirdAppUser->province) ? $modelAcc->escape($oThirdAppUser->province) : '',
				"country" => isset($oThirdAppUser->country) ? $modelAcc->escape($oThirdAppUser->country) : '',
				"unionid" => $oRegUser->unionid,
			];
			if ($thirdUser) {
				$modelReg->update("account_third_user", $updata, ["id" => $thirdUser->id]);
			} else {
				$updata["third_id"] = $thirdApp->id;
				$updata["openid"] = $modelAcc->escape($oThirdAppUser->openid);
				$modelReg->insert('account_third_user', $updata, false);
			}

			/* cookie中保留注册信息 */
			$aResult = $modelWay->shiftRegUser($oRegUser, false);
			if (false === $aResult[0]) {
				return $aResult;
			}
		}

		return [true];
	}
	/**
	 * 获取验证码
	 * $codelen  验证码的个数
	 * $width  验证码的宽度
	 * $height  验证码的高度
	 * $fontsize  验证码的字体大小
	 */
	public function getCaptcha_action($codelen = 4, $width = 130, $height = 50, $fontsize = 20) {
		require_once TMS_APP_DIR . '/lib/validatecode.php';

		$captcha = new \ValidateCode($codelen, $width, $height, $fontsize);
		$captcha->doImg();

		$code = $captcha->getCode();
		$_SESSION['_login_auth_code'] = $code;
	}
}