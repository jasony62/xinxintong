<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 站点用户
 */
class main extends \site\fe\base {
	/**
	 * 进入用户主页
	 */
	public function index_action() {
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
		$modelReg = $this->model('site\user\registration');
		if ($oAccount = $modelAnt->byId($oUser->uid, ['fields' => 'siteid,unionid,coin,headimgurl,wx_openid,is_wx_primary,is_reg_primary'])) {
			$oUser->coin = $oAccount->coin;
			$oUser->headimgurl = $oAccount->headimgurl;
			$oUser->is_wx_primary = $oAccount->is_wx_primary;
			$oUser->is_reg_primary = $oAccount->is_reg_primary;
		}
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
		//if (in_array($userAgent, ['wx'])) {
		$otherRegisters = $this->_getOtherRegistersByWxopenid($oAccount);
		if (count($otherRegisters)) {
			$oUser->registersByWx = $otherRegisters;
		}
		//}

		return new \ResponseData($oUser);
	}
	/**
	 * 修改用户昵称
	 * 只有注册过用户才能修改？？？
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
				['nickname' => $data->nickname],
				['uid' => $cookieRegUser->unionid]
			);
			$cookieRegUser->nickname = $data->nickname;
			$modelWay->setCookieRegUser($cookieRegUser);
		}

		/* 更新站点用户信息 */
		$modelUsr = $this->model('site\user\account');
		if ($oAccount = $modelUsr->byId($user->uid)) {
			$modelUsr->changeNickname($this->siteId, $oAccount->uid, $data->nickname);
		}
		$cookieUser = $modelWay->getCookieUser($this->siteId);
		$cookieUser->nickname = $data->nickname;
		$modelWay->setCookieUser($this->siteId, $cookieUser);

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
		// $userAgent = $this->userAgent(); // 客户端类型
		// if (!in_array($userAgent, ['wx'])) {
		// 	return new \ResponseError('仅在微信中支持切换用户');
		// }
		$oPosted = $this->getPostJson();
		if (empty($oPosted->uname)) {
			return new \ParameterError();
		}
		$uname = $oPosted->uname;

		$oUser = clone $this->who;
		$modelAnt = $this->model('site\user\account');
		$modelReg = $this->model('site\user\registration');

		$oAccount = $modelAnt->byId($oUser->uid, ['fields' => 'siteid,unionid,coin,headimgurl,wx_openid,is_wx_primary,is_reg_primary']);
		if (false === $oAccount) {
			return new \ObjectNotFoundError('当前用户不存在');
		}
		/**
		 * 和微信openid绑定的注册账号
		 */
		$otherRegisters = $this->_getOtherRegistersByWxopenid($oAccount);
		if (0 === count($otherRegisters)) {
			return new \ObjectNotFoundError('不存在可以切换的用户');
		}
		$oTargetRegUser = null;
		foreach ($otherRegisters as $oRegUser) {
			if ($oRegUser->uname === $uname) {
				$oTargetRegUser = $oRegUser;
				break;
			}
		}
		if (empty($oTargetRegUser)) {
			return new \ObjectNotFoundError('指定的切换账号不存在');
		}

		/* 记录登录状态 */
		$fromip = $this->client_ip();
		$modelReg->updateLastLogin($oTargetRegUser->unionid, $fromip);

		/* cookie中保留注册信息 */
		$modelWay = $this->model('site\fe\way');
		$modelWay->shiftRegUser($oTargetRegUser);

		$oUser = $this->get_action();

		return $oUser;
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
	private function _getOtherRegistersByWxopenid($oAccount) {
		$registers = [];
		if (!empty($oAccount->wx_openid)) {
			$modelAnt = $this->model('site\user\account');
			$modelReg = $this->model('site\user\registration');
			$others = $modelAnt->byOpenid($oAccount->siteid, 'wx', $oAccount->wx_openid, ['fields' => 'uid,nickname,unionid,is_wx_primary,is_reg_primary', 'is_reg_primary' => null, 'has_unionid' => true]);
			if (count($others) > 1) {
				foreach ($others as $oOther) {
					//if ($oOther->uid === $oUser->uid || $oOther->unionid === $oAccount->unionid)) {
					//	continue;
					//}
					$oReg = $modelReg->byId($oOther->unionid);
					if ($oReg) {
						$oOther->uname = $oReg->uname;
					}
					$registers[] = $oOther;
				}
			}
		}

		return $registers;
	}
}