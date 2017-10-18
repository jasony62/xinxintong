<?php
namespace site\fe;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 站点前端访问控制器基类
 */
class base extends \site\base {
	/**
	 * 当前访问的站点ID
	 */
	protected $siteId;
	/**
	 * 当前访问用户
	 */
	protected $who;
	/**
	 * 对请求进行通用的处理
	 */
	public function __construct() {
		//empty($_GET['site']) && die('参数错误！');
		$siteId = empty($_GET['site']) ? 'platform' : $_GET['site'];
		$this->siteId = $siteId;
		/* 获得访问用户的信息 */
		$modelWay = $this->model('site\fe\way');
		$this->who = $modelWay->who($siteId);
	}
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 检查是否需要第三方社交帐号认证
	 * 检查条件：
	 * 0、应用是否设置了需要认证
	 * 1、站点是否绑定了第三方社交帐号认证
	 * 2、平台是否绑定了第三方社交帐号认证
	 * 3、用户客户端是否可以发起认证
	 *
	 * @param string $site
	 */
	protected function requireSnsOAuth($siteid) {
		if ($this->userAgent() === 'wx') {
			if (!isset($this->who->sns->wx)) {
				$modelWx = $this->model('sns\wx');
				if (($wxConfig = $modelWx->bySite($siteid)) && $wxConfig->joined === 'Y') {
					$this->snsOAuth($wxConfig, 'wx');
				} else if (($wxConfig = $modelWx->bySite('platform')) && $wxConfig->joined === 'Y') {
					$this->snsOAuth($wxConfig, 'wx');
				}
			}
			if (!isset($this->who->sns->qy)) {
				if ($qyConfig = $this->model('sns\qy')->bySite($siteid)) {
					if ($qyConfig->joined === 'Y') {
						$this->snsOAuth($qyConfig, 'qy');
					}
				}
			}
		} else if ($this->userAgent() === 'yx') {
			if (!isset($this->who->sns->yx)) {
				if ($yxConfig = $this->model('sns\yx')->bySite($siteid)) {
					if ($yxConfig->joined === 'Y') {
						$this->snsOAuth($yxConfig, 'yx');
					}
				}
			}
		}

		return false;
	}
	/**
	 * 检查是否当前的请求是OAuth后返回的请求
	 */
	public function afterSnsOAuth() {
		$auth = []; // 当前用户的身份信息
		if (isset($_GET['mocker'])) {
			// 指定的模拟用户
			list($snsName, $openid) = explode(',', $_GET['mocker']);
			$snsUser = new \stdclass;
			$snsUser->openid = $openid;
			$auth['sns'][$snsName] = $snsUser;
		} else {
			$snsSiteId = false;
			if ($this->myGetcookie("_{$this->siteId}_oauthpending") === 'Y') {
				$snsSiteId = $this->siteId;
			} else if ($this->myGetcookie("_platform_oauthpending") === 'Y') {
				$snsSiteId = 'platform';
			}
			if (false === $snsSiteId) {
				return false;
			}
			// oauth回调
			$this->mySetcookie("_{$snsSiteId}_oauthpending", '', time() - 3600);
			if (isset($_GET['state']) && isset($_GET['code'])) {
				$state = $_GET['state'];
				if (strpos($state, 'snsOAuth-') === 0) {
					$code = $_GET['code'];
					$snsName = explode('-', $state);
					if (count($snsName) === 2) {
						$snsName = $snsName[1];
						if ($snsUser = $this->snsOAuthUserByCode($snsSiteId, $code, $snsName)) {
							/* 企业用户仅包含openid */
							$auth['sns'][$snsName] = $snsUser;
						}
					}
				}
			}
		}

		if (!empty($auth)) {
			// 如果获得了用户的身份信息，更新保留的用户信息
			$modelWay = $this->model('site\fe\way');
			$this->who = $modelWay->who($this->siteId, $auth);
		}

		return true;
	}
	/**
	 * 获得社交帐号用户信息
	 * ？？？不需要考虑直接使用平台公众号的情况吗
	 */
	protected function &getSnsUser($siteId) {
		if ($this->userAgent() === 'wx') {
			if (isset($this->who->sns->wx)) {
				$openid = $this->who->sns->wx->openid;
				$fan = $this->model('sns\wx\fan')->byOpenid($siteId, $openid, '*', 'Y');
			} else if (isset($this->who->sns->qy)) {
				$openid = $this->who->sns->qy->openid;
				$fan = false;
			}
		} else if ($this->userAgent() === 'yx') {
			if (isset($this->who->sns->yx)) {
				$openid = $this->who->sns->yx->openid;
				$fan = $this->model('sns\yx\fan')->byOpenid($siteId, $openid, '*', 'Y');
			}
		}
		return $fan;
	}
	/**
	 * 检查当前用户是否已经登录，且在有效期内
	 */
	public function isLogined() {
		$modelWay = $this->model('site\fe\way');
		return $modelWay->isLogined($this->siteId, $this->who);
	}
	/**
	 * 进行用户认证的URL
	 */
	public function loginURL() {
		$url = '/site/fe/user/login';
		return $url;
	}
	/**
	 * 执行OAuth操作
	 *
	 * 会在cookie保留结果5分钟
	 *
	 * $site
	 * $controller OAuth的回调地址
	 * $state OAuth回调时携带的参数
	 */
	protected function snsOAuth(&$snsConfig, $snsName) {
		$ruri = 'http://' . APP_HTTP_HOST . $_SERVER['REQUEST_URI'];

		switch ($snsName) {
		case 'qy':
			$snsProxy = $this->model('sns\qy\proxy', $snsConfig);
			$oauthUrl = $snsProxy->oauthUrl($ruri, 'snsOAuth-' . $snsName);
			break;
		case 'wx':
			if ($snsConfig->can_oauth === 'Y') {
				$snsProxy = $this->model('sns\wx\proxy', $snsConfig);
				$oauthUrl = $snsProxy->oauthUrl($ruri, 'snsOAuth-' . $snsName, 'snsapi_userinfo');
			}
			break;
		case 'yx':
			if ($snsConfig->can_oauth === 'Y') {
				$snsProxy = $this->model('sns\yx\proxy', $snsConfig);
				$oauthUrl = $snsProxy->oauthUrl($ruri, 'snsOAuth-' . $snsName);
			}
			break;
		}
		if (isset($oauthUrl)) {
			/* 通过cookie判断是否是后退进入 */
			$this->mySetcookie("_{$snsConfig->siteid}_oauthpending", 'Y');
			$this->redirect($oauthUrl);
		}

		return false;
	}
	/**
	 * 通过OAuth接口获得用户信息
	 *
	 * @param string $site
	 * @param string $code
	 * @param string $snsName
	 */
	protected function snsOAuthUserByCode($site, $code, $snsName) {
		$modelSns = $this->model('sns\\' . $snsName);
		$snsConfig = $modelSns->bySite($site);
		if (($snsConfig === false || $snsConfig->joined !== 'Y') && $snsName === 'wx') {
			$snsConfig = $modelSns->bySite('platform');
		}
		if ($snsConfig === false) {
			return false;
		}
		$snsProxy = $this->model('sns\\' . $snsName . '\proxy', $snsConfig);
		$rst = $snsProxy->getOAuthUser($code);
		if ($rst[0] === false) {
			$this->model('log')->log($site, 'snsOAuthUserByCode', 'xxt oauth2 failed: ' . $rst[1], null, $_SERVER['REQUEST_URI']);
			$snsUser = false;
		} else {
			$snsUser = $rst[1];
		}

		return $snsUser;
	}
	/**
	 * 尽最大可能向用户发送消息
	 *
	 * $site
	 * $openid
	 * $message
	 */
	public function sendBySnsUser($siteId, $snsName, $snsUser, $message) {
		$rst = array(false);

		switch ($snsName) {
		case 'yx':
			if ($snsConfig = $this->model('sns\yx')->bySite($siteId)) {
				if ($snsConfig->joined === 'Y') {
					$proxy = $this->model('sns\yx\proxy', $snsConfig);
					if ($snsConfig->can_p2p === 'Y') {
						$rst = $proxy->messageSend($message, array($snsUser->openid));
					} else {
						$rst = $proxy->messageCustomSend($message, $snsUser->openid);
					}
				}
			}
			break;
		case 'wx':
			if ($snsConfig = $this->model('sns\wx')->bySite($siteId)) {
				if ($snsConfig->joined === 'Y') {
					$proxy = $this->model('sns\wx\proxy', $snsConfig);
					$rst = $proxy->messageCustomSend($message, $snsUser->openid);
				}
			}
			break;
		case 'qy':
			if ($snsConfig = $this->model('sns\wx')->bySite($siteId)) {
				if ($snsConfig->joined === 'Y') {
					$proxy = $this->model('sns\qy\proxy', $snsConfig);
					$message['touser'] = $snsUser->openid;
					$message['agentid'] = $snsConfig->agentid;
					$rst = $mpproxy->messageSend($message, $snsUser->openid);
				}
			}
			break;
		}

		return $rst;
	}

	/**
	 *
	 * 要求关注
	 *
	 * @param string $siteId
	 * @param string $openid
	 *
	 */
	protected function askFollow($siteId, $openid = false) {
		$isfollow = false;
		if ($openid !== false) {
			$isfollow = $this->model('user/fans')->isFollow($siteId, $openid);
		}
		if (!$isfollow) {
			/*$modelMpa = $this->model('mp\mpaccount');
				$fea = $modelMpa->getFeature($siteId);
				if ($fea->follow_page_id === '0') {
					$mpa = $this->model('mp\mpaccount')->byId($siteId);
					$html = '请关注公众号：' . $mpa->name;
				} else {
					$page = $this->model('code\page')->byId($fea->follow_page_id);
					$html = $page->html;
					$css = $page->css;
					$js = $page->js;
			*/
			//$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
			//header($protocol . ' 401 Unauthorized');
			//header('Cache-Control:no-cache,must-revalidate,no-store');
			//header('Pragma:no-cache');
			//header("Expires:-1");
			//\TPL::assign('follow_ele', empty($html) ? '请关注公众号' : $html);
			//\TPL::assign('follow_css', empty($css) ? '' : $css);
			//\TPL::output('follow');
			\TPL::output('/site/fe/user/follow');
			exit;
		}

		return true;
	}
	/**
	 *
	 * 要求关注
	 *
	 * @param string $siteId
	 * @param string $snsName
	 *
	 */
	protected function snsFollow($siteId, $snsName, $oMatter = null, $sceneId = null) {
		$followUrl = '/rest/site/fe/user/follow?site=' . $siteId . '&sns=' . $snsName;

		if (!empty($sceneId)) {
			$followUrl .= '&sceneid=' . $sceneId;
		} else if (!empty($oMatter)) {
			$followUrl .= '&matter=' . $oMatter->type . ',' . $oMatter->id;
		}

		$this->redirect($followUrl);
	}
	/**
	 * 返回全局的邀请关注页面
	 */
	public function askFollow_action($site, $sns) {
		$this->askFollow($site, $sns);
	}
	/**
	 * 微信jssdk包
	 *
	 * $site
	 * $url
	 */
	public function wxjssdksignpackage_action($site, $url) {
		if (($snsConfig = $this->model('sns\wx')->bySite($site)) && $snsConfig->joined === 'Y') {
			$snsProxy = $this->model('sns\wx\proxy', $snsConfig);
		} else if (($snsConfig = $this->model('sns\wx')->bySite('platform')) && $snsConfig->joined === 'Y') {
			$snsProxy = $this->model('sns\wx\proxy', $snsConfig);
		} else if ($snsConfig = $this->model('sns\qy')->bySite($site)) {
			if ($snsConfig->joined === 'Y') {
				$snsProxy = $this->model('sns\qy\proxy', $snsConfig);
			}
		}
		if (isset($snsProxy)) {
			$rst = $snsProxy->getJssdkSignPackage(urldecode($url));
			header('Content-Type: text/javascript');
			if ($rst[0] === false) {
				die("alert('{$rst[1]}');");
			}
			die($rst[1]);
		} else {
			die("signPackage=false");
		}
	}
	/**
	 * 清除用户登录信息
	 */
	public function cleanCookieUser_action($site) {
		$this->model('site\fe\way')->cleanCookieUser($site);
		return new \ResponseData('ok');
	}
	/**
	 * 二维码
	 */
	public function qrcode_action($site, $url) {
		include TMS_APP_DIR . '/lib/qrcode/qrlib.php';
		// outputs image directly into browser, as PNG stream
		//@ob_clean();
		\QRcode::png($url);
	}
}