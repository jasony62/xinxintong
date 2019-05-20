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
		$rule_action['actions'][] = 'checkPwdStrength';
		$rule_action['actions'][] = 'byRegAndWxopenid';
		$rule_action['actions'][] = 'getCaptcha';

		return $rule_action;
	}
	/**
	 * 执行登录
	 */
	public function do_action() {
		$data = $this->getPostJson(false);
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

		$data->uname = $modelReg->escape($data->uname);
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
	 * 判断密码强度
	 */
	public function checkPwdStrength_action($account, $password) {
		$rst = tms_pwd_check($password, ['account' => $account], true);
		
		$data = new \stdClass;
		$data->strength = $rst[0];
		($rst[0] === false) && $data->msg = '您的密码存在风险！请尽快修改（' . $rst[1] . '）';
		return new \ResponseData($data);
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