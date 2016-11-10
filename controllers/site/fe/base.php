<?php
namespace site\fe;
/**
 * 站点前端访问控制器基类
 */
class base extends \TMS_CONTROLLER {
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
		empty($_GET['site']) && die('参数错误！');
		$siteId = $_GET['site'];
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
		$ruri = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

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
		if ($snsConfig === false || $snsConfig->joined !== 'Y') {
			$snsConfig = $modelSns->bySite('platform');
		}
		$snsProxy = $this->model('sns\\' . $snsName . '\proxy', $snsConfig);
		$rst = $snsProxy->getOAuthUser($code);
		if ($rst[0] === false) {
			$this->model('log')->log($site, 'snsOAuthUserByCode', 'xxt oauth2 failed: ' . $rst[1]);
			$user = false;
		} else {
			$user = $rst[1];
		}

		return $user;
	}
	/**
	 *
	 */
	protected function &snsUserByMember($siteId, $memberId, $snsName, $fields = '*', $followed = 'Y') {
		$snsUser = false;
		$member = $this->model('site\user\member')->byId($memberId);
		switch ($snsName) {
		case 'wx':
			$snsUser = $this->model('sns\wx\fan')->byUser($siteId, $member->userid, $fields, $followed);
			break;
		case 'qy':
			//$snsUser = $this->model('sns\qy\fan')->byUser($siteId, $member->userid, $fields, $followed);
			$snsUser = false;
			break;
		case 'yx':
			$snsUser = $this->model('sns\yx\fan')->byUser($siteId, $member->userid, $fields, $followed);
			break;
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
	 * 客户端应用名称
	 */
	protected function &userAgent() {
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$user_agent = $_SERVER['HTTP_USER_AGENT'];
			if (preg_match('/yixin/i', $user_agent)) {
				$ca = 'yx';
			} elseif (preg_match('/MicroMessenger/i', $user_agent)) {
				$ca = 'wx';
			} else {
				$ca = false;
			}
		} else {
			$ca = false;
		}

		return $ca;
	}
	/**
	 *
	 */
	protected function outputError($err, $title = '程序错误') {
		\TPL::assign('title', $title);
		\TPL::assign('body', $err);
		\TPL::output('error');
		exit;
	}
	/**
	 *
	 */
	protected function outputInfo($info, $title = '提示') {
		\TPL::assign('title', $title);
		\TPL::assign('body', $info);
		\TPL::output('info');
		exit;
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
			$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
			header($protocol . ' 401 Unauthorized');
			header('Cache-Control:no-cache,must-revalidate,no-store');
			header('Pragma:no-cache');
			header("Expires:-1");
			\TPL::assign('follow_ele', empty($html) ? '请关注公众号' : $html);
			\TPL::assign('follow_css', empty($css) ? '' : $css);
			\TPL::output('follow');
			exit;
		}

		return true;
	}
	/**
	 *
	 * 要求关注
	 *
	 * @param string $siteId
	 * @param string $openid
	 *
	 */
	protected function snsFollow($siteId, $snsName) {
		$modelSns = $this->model('sns\\' . $snsName);
		$sns = $modelSns->bySite($siteId, 'joined,follow_page_id');
		if ($sns === false || $sns->joined === 'N') {
			$sns = $modelSns->bySite('platform', 'joined,follow_page_id');
		}

		if ($sns->follow_page_id === '0') {
			$site = $this->model('site')->byId($siteId);
			$html = '请关注公众号：' . $site->name;
		} else {
			$page = $this->model('code\page')->lastPublishedByName($siteId, $sns->follow_page_name);
			$html = $page->html;
			$css = $page->css;
			//$js = $page->js;
		}
		$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
		header($protocol . ' 401 Unauthorized');
		header('Cache-Control:no-cache,must-revalidate,no-store');
		header('Pragma:no-cache');
		header("Expires:-1");
		\TPL::assign('follow_ele', empty($html) ? '请关注公众号' : $html);
		\TPL::assign('follow_css', empty($css) ? '' : $css);
		\TPL::output('follow');
		exit;
	}
	/**
	 * 返回全局的邀请关注页面
	 */
	public function askFollow_action($site) {
		$this->askFollow($site);
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
	/**
	 * 创建一个企业号的粉丝用户
	 * 同步的创建会员用户
	 *
	 * $user 企业号用户的详细信息
	 */
	protected function createQyFan($site, $user, $schemaId, $timestamp = null, $mapDeptR2L = null,$who) {

		$create_at = time();
		empty($timestamp) && $timestamp = $create_at;

		$fan = array();
		$fan['siteid'] = $site;
		$fan['openid'] = $user->userid;
		$fan['nickname'] = $user->name;
		// $fan['verified'] = 'Y';
		//$fan['create_at'] = $create_at;
		$fan['sync_at'] = $timestamp;
		isset($user->mobile) && $fan['mobile'] = $user->mobile;
		isset($user->email) && $fan['email'] = $user->email;
		isset($user->weixinid) && $fan['weixinid'] = $user->weixinid;
		$extattr = array();
		if (isset($user->extattr) && !empty($user->extattr->attrs)) {
			foreach ($user->extattr->attrs as $ea) {
				$extattr[urlencode($ea->name)] = urlencode($ea->value);
			}

		}
		/**
		 * 处理岗位信息
		 */
		if (!empty($user->position)) {
			$extattr['position'] = urlencode($user->position);
		}

		$fan['extattr'] = urldecode(json_encode($extattr));
		/**
		 * 建立成员和部门之间的关系
		 */
		$udepts = array();
		foreach ($user->department as $ud) {
			if (empty($mapDeptR2L)) {
				$q = array(
					'fullpath',
					'xxt_site_member_department',
					"siteid='$site' and extattr like '%\"id\":$ud,%'",
				);
				$fullpath = $this->model()->query_val_ss($q);
				$udepts[] = explode(',', $fullpath);
			} else {
				isset($mapDeptR2L[$ud]) && $udepts[] = explode(',', $mapDeptR2L[$ud]['path']);
			}

		}

		$fan['depts'] = json_encode($udepts);

		$model = $this->model();
		/**
		 * 为了兼容服务号和订阅号的操作，生成和成员用户对应的粉丝用户
		 */
		if ($old = $this->model('sns\qy\fan')->byOpenid($site, $user->userid)) {
			isset($user->avatar) && $fan['headimgurl'] = $user->avatar;
			if ($user->status == 1 && $old->subscribe_at == 0) {
				$fan['subscribe_at'] = $timestamp;
			} else if ($user->status == 1 && $old->unsubscribe_at != 0) {
				$fan['unsubscribe_at'] = 0;
			} else if ($user->status == 4 && $old->unsubscribe_at == 0) {
				$fan['unsubscribe_at'] = $timestamp;
			}
			$model->update(
				'xxt_site_qyfan',
				$fan,
				"siteid='$site' and openid='{$user->userid}'"
			);
			$sync_id = $old->id;
		} else {
			isset($user->avatar) && $fan['headimgurl'] = $user->avatar;
			$user->status == 1 && $fan['subscribe_at'] = $timestamp;
			$sync_id = $model->insert('xxt_site_qyfan', $fan, true);
		}


		//记录同步日志
		$data = json_encode($fan);
		$log = [];
		$log['siteid'] = $site;
		$log['sync_type'] = '部门用户';
		$log['sync_table'] = 'xxt_site_qyfan';
		$log['sync_data'] = $data;
		$log['sync_at'] = $timestamp;
		$log['sync_id'] = $sync_id;
		$this->model('log')->syncLog($site,$who,$log,'syncFromQy');

		return true;
	}
	/**
	 * 更新企业号用户信息
	 */
	protected function updateQyFan($site, $luser, $user, $schemaId, $timestamp = null, $mapDeptR2L = null,$who) {
		$model = $this->model();
		empty($timestamp) && $timestamp = time();

		$fan = array();
		$fan['sync_at'] = $timestamp;
		isset($user->mobile) && $fan['mobile'] = $user->mobile;
		isset($user->email) && $fan['email'] = $user->email;
		$extattr = array();
		if (isset($user->extattr) && !empty($user->extattr->attrs)) {
			foreach ($user->extattr->attrs as $ea) {
				$extattr[urlencode($ea->name)] = urlencode($ea->value);
			}
		}
		$fan['tags'] = ''; // 先将成员的标签清空，标签同步的阶段会重新更新
		/**
		 * 处理岗位信息
		 */
		if (!empty($user->position)) {
			$extattr['position'] = urlencode($user->position);
		}
		$fan['extattr'] = urldecode(json_encode($extattr));
		/**
		 * 建立成员和部门之间的关系
		 */
		$udepts = array();
		foreach ($user->department as $ud) {
			if (empty($mapDeptR2L)) {
				$q = array(
					'fullpath',
					'xxt_site_member_department',
					"siteid='$site' and extattr like '%\"id\":$ud,%'",
				);
				$fullpath = $model->query_val_ss($q);
				$udepts[] = explode(',', $fullpath);
			} else {
				isset($mapDeptR2L[$ud]) && $udepts[] = explode(',', $mapDeptR2L[$ud]['path']);
			}
		}
		$fan['depts'] = json_encode($udepts);
		/**
		 * 成员用户对应的粉丝用户
		 */
		if ($old = $this->model('sns\qy\fan')->byOpenid($site, $user->userid)) {
			$fan['nickname'] = $user->name;
			isset($user->avatar) && $fan['headimgurl'] = $user->avatar;
			if ($user->status == 1 && $old->subscribe_at == 0) {
				$fan['subscribe_at'] = $timestamp;
			} else if ($user->status == 1 && $old->unsubscribe_at != 0) {
				$fan['unsubscribe_at'] = 0;
			} else if ($user->status == 4 && $old->unsubscribe_at == 0) {
				$fan['unsubscribe_at'] = $timestamp;
			}
			$model->update(
				'xxt_site_qyfan',
				$fan,
				"siteid='$site' and openid='{$user->userid}'"
			);
			$sync_id = $old->id;
		} else {
			$fan['siteid'] = $site;
			$fan['openid'] = $user->userid;
			$fan['nickname'] = $user->name;
			isset($user->avatar) && $fan['headimgurl'] = $user->avatar;
			$user->status == 1 && $fan['subscribe_at'] = $timestamp;
			$sync_id = $model->insert('xxt_site_qyfan', $fan, true);
		}

		//记录同步日志
		$data = json_encode($fan);
		$log = [];
		$log['siteid'] = $site;
		$log['sync_type'] = '部门用户';
		$log['sync_table'] = 'xxt_site_qyfan';
		$log['sync_data'] = $data;
		$log['sync_at'] = $timestamp;
		$log['sync_id'] = $sync_id;
		$this->model('log')->syncLog($site,$who,$log,'syncFromQy');

		return true;
	}
}