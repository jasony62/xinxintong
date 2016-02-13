<?php
namespace site\fe;

require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 站点访问控制器基类
 */
class base extends \member_base {
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
		/*进行oauth返回*/
		if (isset($_GET['code']) && ($this->myGetcookie("_{$siteId}_oauthpending") === 'Y')) {
			$code = $_GET['code'];
			$this->mySetcookie("_{$mpid}_oauthpending", '', time() - 3600);
			$openid = $this->getOAuthUserByCode($siteId, $code);
		}
		/*获得访问用户的信息*/
		$modelWay = $this->model('site\fe\way');
		try {
			$options = array();
			isset($openid) && $options['openid'] = $openid;
			isset($_GET['mocker']) && $options['mocker'] = $_GET['mocker'];
			$this->who = $modelWay->who($siteId, $options);
		} catch (site\fe\excep\RequireOAuth $e) {
			/*跳转到OAuth*/
			$oauthUrl = $e->getMessage();
			$this->mySetcookie("_{$siteId}_oauthpending", 'Y');
			$this->redirect($oauthUrl);
		}
	}
	/**
	 * 检查当前用户是否已经登录，且在有效期内
	 */
	public function authenticated() {
		$modelWay = $this->model('site\fe\way');
		return $modelWay->isLogined($this->siteId, $this->who);
	}
	/**
	 * 进行用户认证的URL
	 */
	public function authenticateURL() {
		$url = '/site/fe/user/login';
		return $url;
	}
}