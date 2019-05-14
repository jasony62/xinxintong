<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 站点用户
 */
class main extends \site\fe\base {
	/**
	 * 进入用户个人中心
	 */
	public function index_action($site) {
		/* 检查是否需要第三方社交帐号OAuth */
		if (!$this->afterSnsOAuth()) {
			$this->requireSnsOAuth($site);
		}

		$user = $this->who;
		if (isset($user->unionid)) {
			$oAccount = $this->model('account')->byId($user->unionid, ['cascaded' => ['group']]);
			if (isset($oAccount->group->view_name) && $oAccount->group->view_name !== TMS_APP_VIEW_NAME) {
				\TPL::output('/site/fe/user/main', ['customViewName' => $oAccount->group->view_name]);
				exit;
			}
		}

		\TPL::output('/site/fe/user/main');
		exit;
	}
	/**
	 * 进入登录和注册页
	 *
	 * @param string $originUrl  来源页面地址
	 * @param string $urlEncryptKey   如果来源地址加密，需传入解密算子
	 */
	public function access_action($originUrl = null, $urlEncryptKey = null) {
		/* 整理cookie中的数据，便于后续处理 */
		$modelWay = $this->model('site\fe\way');
		$modelWay->resetAllCookieUser();

		/* 保存页面来源 */
		if (!empty($originUrl)) {
			if (!empty($urlEncryptKey)) {
				$referer = $this->model()->encrypt($originUrl, 'DECODE', $urlEncryptKey);
			} else {
				$referer = $originUrl;
			}
		} else if (isset($_SERVER['HTTP_REFERER'])) {
			$referer = $_SERVER['HTTP_REFERER'];
		}
		if (!empty($referer) && !in_array($referer, array('/'))) {
			if (false === strpos($referer, '/fe/user')) {
				$this->mySetCookie('_user_access_referer', $referer);
			}
		}

		\TPL::output('/site/fe/user/access');
		exit;
	}
	/**
	 * 当前用户信息
	 */
	public function get_action() {
		$oUser = clone $this->who;
		/* 站点用户信息 */
		$modelAnt = $this->model('site\user\account');
		$oAccount = $modelAnt->byId($oUser->uid, ['fields' => 'uid,siteid,unionid,coin,headimgurl,wx_openid,is_wx_primary,is_reg_primary']);
		if (false === $oAccount) {
			return new \ResponseData($oUser);
		}

		$modelReg = $this->model('site\user\registration');
		$oUser->coin = $oAccount->coin;
		$oUser->headimgurl = $oAccount->headimgurl;
		$oUser->is_wx_primary = $oAccount->is_wx_primary;
		$oUser->is_reg_primary = $oAccount->is_reg_primary;
		if (!empty($oUser->unionid)) {
			$oReg = $modelReg->byId($oUser->unionid);
			if ($oReg) {
				$oUser->uname = $oReg->uname;
			}
		}
		/**
		 * 和微信openid绑定的注册账号
		 */
		$userAgent = $this->userAgent(); // 客户端类型
		if (in_array($userAgent, ['wx']) && !empty($oAccount->wx_openid)) {
			$regUsers = $this->_getRegAntsByWxopenid($oAccount);
			if (count($regUsers)) {
				/* 已经存在绑定了主注册账号的团队账号 */
				$oUser->siteRegistersByWx = $regUsers;
			} else {
				/* 同站点下没有绑定了主注册账号的团队账号，获得用户在平台的注册账号 */
				$aUnionids = $modelAnt->byOpenid(null, 'wx', $oAccount->wx_openid, ['is_reg_primary' => 'Y', 'fields' => 'distinct unionid']);
				if (count($aUnionids)) {
					$oUser->plRegistersByWx = [];
					foreach ($aUnionids as $oUnionid) {
						$oReg = $modelReg->byId($oUnionid->unionid, ['fields' => 'uid unionid,email uname,nickname']);
						$oUser->plRegistersByWx[] = $oReg;
					}
				}
			}
		}

		return new \ResponseData($oUser);
	}
	/**
	 * 修改用户昵称
	 */
	public function changeNickname_action() {
		$data = $this->getPostJson();
		if (empty($data->nickname)) {
			return new \ResponseError('新昵称不能为空');
		}

		$user = $this->who;

		/* 更新注册用户信息 */
		$modelWay = $this->model('site\fe\way');
		$cookieRegUser = $modelWay->getCookieRegUser();
		if ($cookieRegUser) {
			$rst = $modelWay->update(
				'account',
				['nickname' => $modelWay->escape($data->nickname)],
				['uid' => $cookieRegUser->unionid]
			);
			$cookieRegUser->nickname = $data->nickname;
			$modelWay->setCookieRegUser($cookieRegUser);
		}

		/* 更新站点用户信息 */
		$modelUsr = $this->model('site\user\account');
		if ($oAccount = $modelUsr->byId($user->uid)) {
			$modelUsr->changeNickname($this->siteId, $oAccount->uid, $modelUsr->escape($data->nickname));
		}
		$cookieUser = $modelWay->getCookieUser($this->siteId);
		$cookieUser->nickname = $data->nickname;
		$modelWay->setCookieUser($this->siteId, $cookieUser);

		return new \ResponseData('ok');
	}
	/**
	 * 修改用户头像信息
	 */
	public function changeHeadImg_action($site) {
		$data = $this->getPostJson();
		if (empty($data->imgSrc)) {
			return new \ResponseError('头像地址不能为空');
		}

		$user = $this->who;
		$avatar = new \stdClass;
		$avatar->imgSrc = $data->imgSrc;
		$avatar->imgType = 'avatar';
		$avatar->creatorId = $user->uid;

		$store = $this->model('fs/user', $site, 'avatar');
		$rst = $store->storeImg($avatar);
		if (false === $rst[0]) {
			return new \ResponseError($rst[1]);
		}
		$headImgUrl = $rst[1];
		/* 更新站点用户信息 */
		$modelUsr = $this->model('site\user\account');
		if ($account = $modelUsr->byId($user->uid)) {
			$modelUsr->changeHeadImgUrl($site, $account->uid, $headImgUrl);
		}

		return new \ResponseData('ok');
	}
	/**
	 * 修改用户口令
	 * 只有注册用户才能修改
	 */
	public function changePwd_action() {
		$data = $this->getPostJson();
		if (empty($data->password)) {
			return new \ResponseError('新口令不能为空');
		}

		$user = $this->who;

		$modelUsr = $this->model('site\user\account');
		if ($oAccount = $modelUsr->byId($user->uid)) {
			$modelReg = $this->model('site\user\registration');
			if ($registration = $modelReg->byId($oAccount->unionid)) {
				// 校验密码安全
				$rst = tms_pwd_check($data->password, ['account' => $registration->uname], true);
				if ($rst[0] === false) {
					return new \ResponseError($rst[1]);
				}

				$rst = $modelReg->changePwd($registration->uname, $data->password, $registration->salt);
				return new \ResponseData($rst);
			}
		}

		return new \ResponseError('你不是注册用户，无法修改口令');
	}
	/**
	 * 切换当前注册用户
	 */
	public function shiftRegUser_action() {
		$userAgent = $this->userAgent(); // 客户端类型
		if (!in_array($userAgent, ['wx'])) {
			return new \ResponseError('仅在微信中支持切换用户');
		}
		$oPosted = $this->getPostJson();
		if (empty($oPosted->uname)) {
			return new \ParameterError();
		}
		$uname = $oPosted->uname;

		$oUser = clone $this->who;
		$modelAnt = $this->model('site\user\account');
		$modelReg = $this->model('site\user\registration');

		$oCurrentAnt = $modelAnt->byId($oUser->uid, ['fields' => 'uid,siteid,unionid,coin,headimgurl,wx_openid,is_wx_primary,is_reg_primary']);
		if (false === $oCurrentAnt) {
			return new \ObjectNotFoundError('当前用户不存在');
		}
		/**
		 * 和微信openid绑定的注册账号
		 */
		$otherRegAnts = $this->_getRegAntsByWxopenid($oCurrentAnt);
		if (0 === count($otherRegAnts)) {
			return new \ObjectNotFoundError('不存在可以切换的用户');
		}
		$oTargetRegAnt = null;
		foreach ($otherRegAnts as $oRegUser) {
			if ($oRegUser->uname === $uname) {
				$oTargetRegAnt = $oRegUser;
				break;
			}
		}
		if (empty($oTargetRegAnt)) {
			return new \ObjectNotFoundError('指定的切换账号不存在');
		}
		/* 将要切换的账号作为微信中的默认账号，解决利用微信openid自动登录的问题 */
		if (isset($oTargetRegAnt->is_wx_primary) && $oTargetRegAnt->is_wx_primary !== 'Y') {
			$modelAnt->update(
				'xxt_site_account',
				['is_wx_primary' => 'N'],
				['siteid' => $oCurrentAnt->siteid, 'wx_openid' => $oCurrentAnt->wx_openid, 'is_wx_primary' => 'Y']);
			$modelAnt->update(
				'xxt_site_account',
				['is_wx_primary' => 'Y'],
				['uid' => $oTargetRegAnt->uid]);
		}

		/* 记录登录状态 */
		$fromip = $this->client_ip();
		$modelReg->updateLastLogin($oTargetRegAnt->unionid, $fromip);

		/* cookie中保留注册信息 */
		$modelWay = $this->model('site\fe\way');
		$aResult = $modelWay->shiftRegUser($oTargetRegAnt);
		if (false === $aResult[0]) {
			return new \ResponseError($aResult[1]);
		}

		return new \ResponseData($oTargetRegAnt);
	}
	/**
	 * 用户访问过的所有站点
	 */
	public function siteList_action() {
		$modelWay = $this->model('site\fe\way');
		$sites = $modelWay->siteList();

		return new \ResponseData($sites);
	}
	/**
	 * 根据微信的openid获得当前用户的注册用户
	 */
	private function _getRegAntsByWxopenid($oAccount) {
		$accounts = [];
		if (!empty($oAccount->wx_openid)) {
			$modelAnt = $this->model('site\user\account');
			$regAnts = $modelAnt->byOpenid($oAccount->siteid, 'wx', $oAccount->wx_openid, ['fields' => 'uid,nickname,unionid,is_wx_primary,is_reg_primary', 'is_reg_primary' => 'Y', 'has_unionid' => true]);
			if (count($regAnts)) {
				$modelReg = $this->model('site\user\registration');
				foreach ($regAnts as $oRegAnt) {
					$oReg = $modelReg->byId($oRegAnt->unionid);
					if ($oReg) {
						$oRegAnt->uname = $oReg->uname;
					}
					$accounts[] = $oRegAnt;
				}
			}
		}

		return $accounts;
	}
}