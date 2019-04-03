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

		return $rule_action;
	}
	/**
	 * 获取支持的第三方登录应用列表
	 */
	public function thirdList_action() {
		$q = [
			'id,creator,create_at,appname,pic',
			'xxt_account_third',
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
		$thirdApp = $this->model('sns\dev189')->byId($thirdId);
		if ($thirdApp === false || $thirdApp->state != 1) {
			return new \ObjectNotFoundError();
		}

		/* 执行第三方应用授权登录 */
		$this->requirLoginOauth($thirdApp);

		return new \ResponseError("跳转失败");
	}
	/**
	 * 执行第三方登录后续操作
	 */
	public function thirdCallback_action($code = '', $state = '') {
		if (empty($code) || empty($state)) {
			die('为获取到授权参数');
		}
		$stateArr = explode('-', $state);
		$thirdId = end($stateArr);
		$thirdApp = $this->model('sns\dev189')->byId($thirdId);
		if ($thirdApp === false) {
			die('未找到指定第三方应用');
		}

		// 获得第三方应用用户信息
		$oThirdAppUser = $this->_userInfoByCode($code, $state);
		if ($oThirdAppUser[0] === false) {
			die($oThirdAppUser[1]);
		}
		// 获取用户信息后，后续处理
		$result = $this->_afterThirdLoginOauth($thirdApp, $oThirdAppUser);
		if ($result[0] === false) {
			die($result[1]);
		}
		// 获取用户的cookie
		$oCookieUser = $modelWay->who($this->siteId);
		if ($referer = $this->myGetCookie('_user_access_referer')) {
			$oCookieUser->_loginReferer = $referer;
			$this->mySetCookie('_user_access_referer', null);
		}

		return new \ResponseData($oCookieUser);
	}
	/**
	 *  跳转到第三方登陆页面
	 */
	protected function requirLoginOauth($devConfig) {
		$ruri = APP_PROTOCOL . APP_HTTP_HOST . '/rest/site/fe/user/login/thirdCallback';

		$snsProxy = $this->model('sns\dev189\proxy', $devConfig);
		$oauthUrl = $snsProxy->oauthUrl($ruri, 'snsOAuth-dev-login-' . $devConfig->id);
		if (isset($oauthUrl)) {
			$this->redirect($oauthUrl);
		}

		return false;
	}
	/**
	 * 通过回调code获取第三方用户信息
	 */
	private function _userInfoByCode($code, $state) {
		// oauth回调
		if (!empty($state) && !empty($code)) {
			if (strpos($state, 'snsOAuth-dev-login') === 0) {
				$oThirdAppUser = $this->model('sns\dev189\proxy')->userInfoByCode($code);
				return $oThirdAppUser;
			} else {
				return [false, '非登录授权'];
			}
		} else {
			return [false, '获取code失败'];
		}
	}
	/**
	 * 第三方登录完成后执行后续处理
	 * 根据openid检查本站是否有账号，有账号登录此账号没有账号就自动创建
	 */
	private function _afterThirdLoginOauth($thirdApp, $oThirdAppUser) {
		$modelWay = $this->model('site\fe\way');
		$modelAcc = $this->model('account');
		$modelReg = $this->model('site\user\registration');
		// 查看此用户是否已经注册过
		$registered = false;
		$q = [
			"*",
			"xxt_account_third_user",
			["third_id" => $thirdApp->id, "openid" => $oThirdAppUser->custId, "forbidden" => 'N']
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

					$registered = true;
				}
			}
		}
		// 没有注册需要创建账号并绑定用户
		if ($registered === false) {
			$user = $this->who;
			/* uname */
			$uname = $user->uid;
			/* password */
			$password = '123456';

			$aOptions = [];
			/* nickname */
			if (!empty($oThirdAppUser->nickname)) {
				$aOptions['nickname'] = $oThirdAppUser->custName;
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

			// 将用户插入到xxt_account_third_user表中
			// 如果已经存在则更新
			$updata = [
				"reg_time" => time(),
				"headimgurl" => isset($oThirdAppUser->headimgurl) ? $oThirdAppUser->headimgurl : '',
				"nickname" => isset($oThirdAppUser->custName) ? $oThirdAppUser->custName : '',
				"sex" => isset($oThirdAppUser->sex) ? $oThirdAppUser->sex : 0,
				"city" => isset($oThirdAppUser->city) ? $oThirdAppUser->city : '',
				"province" => isset($oThirdAppUser->province) ? $oThirdAppUser->province : '',
				"country" => isset($oThirdAppUser->country) ? $oThirdAppUser->country : '',
				"unionid" => $oRegUser->unionid,
			];
			if ($thirdUser) {
				$modelReg->update("xxt_account_third_user", $updata, ["id" => $thirdUser->id]);
			} else {
				$updata["thirdApp"] = $thirdApp->id;
				$updata["openid"] = $oThirdAppUser->custId;
				$modelReg->insert('xxt_account_third_user', $updata, false);
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