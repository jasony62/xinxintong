<?php
namespace site\fe\matter\joinwall;

include_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 加入讨论组
 */
class main extends \site\fe\base {
	public function index_action($site, $app){
		empty($site) && $this->outputError('没有指定站点ID');
		empty($app) && $this->outputError('讨论组ID为空');

		if (!$this->afterSnsOAuth()) {
			/* 检查是否需要第三方社交帐号OAuth */
			$this->_requireSnsOAuth($site);
		}

		$user = $this->who;
		$user2 = new \stdClass;
		if(isset($user->sns->wx)){
			//获取nickname
			$snsUser = $this->model('sns\wx\fan')->byOpenid($site, $user->sns->wx->openid, 'nickname,headimgurl');
			$user2->wx_openid = $user->sns->wx->openid;
			$user2->nickname = $snsUser->nickname;
			$user2->headimgurl = $snsUser->headimgurl;
		}elseif(isset($user->sns->yx)){
			$snsUser = $this->model('sns\yx\fan')->byOpenid($site, $user->sns->yx->openid, 'nickname,headimgurl');
			$user2->yx_openid = $user->sns->yx->openid;
			$user2->nickname = $snsUser->nickname;
			$user2->headimgurl = $snsUser->headimgurl;
		}elseif(isset($user->sns->qy)){
			$snsUser = $this->model('sns\qy\fan')->byOpenid($site, $user->sns->qy->openid, 'nickname,headimgurl');
			$user2->qy_openid = $user->sns->qy->openid;
			$user2->nickname = $snsUser->nickname;
			$user2->headimgurl = $snsUser->headimgurl;
		}else{
			$desc = '请从公众号进入！';
			\TPL::assign('tips', $desc);
			\TPL::output('/site/fe/matter/joinwall/index');
			exit;
		}
		$user2->userid = $user->uid;
		
		//加入讨论组
		$desc = $this->model('matter\wall')->join($site, $app, $user2, 'click');

		\TPL::assign('tips', $desc);
		\TPL::output('/site/fe/matter/joinwall/index');
		exit;

	}
	/**
	 * 检查是否需要第三方社交帐号认证
	 * 检查条件：
	 * 1、站点是否绑定了第三方社交帐号认证
	 * 2、平台是否绑定了第三方社交帐号认证
	 * 3、用户客户端是否可以发起认证
	 *
	 * @param string $site
	 */
	private function _requireSnsOAuth($siteid) {
		if ($this->userAgent() === 'wx') {
			if (!isset($this->who->sns->wx)) {
				if ($wxConfig = $this->model('sns\wx')->bySite($siteid)) {
					if ($wxConfig->joined === 'Y') {
						$this->snsOAuth($wxConfig, 'wx');
					}
				} else if ($wxConfig = $this->model('sns\wx')->bySite('platform')) {
					if ($wxConfig->joined === 'Y') {
						$this->snsOAuth($wxConfig, 'wx');
					}
				}
			}
		
			if (!isset($this->who->sns->qy)) {
				if ($qyConfig = $this->model('sns\qy')->bySite($siteid)) {
					if ($qyConfig->joined === 'Y') {
						$this->snsOAuth($qyConfig, 'qy');
					}
				}
			}
			
		} elseif ($this->userAgent() === 'yx') {
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

}