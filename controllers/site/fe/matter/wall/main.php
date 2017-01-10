<?php
namespace site\fe\matter\wall;

include_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 加入讨论组
 */
class main extends \site\fe\base {
	public function index_action($site, $app){
		if (!$this->afterSnsOAuth()) {
			/* 检查是否需要第三方社交帐号OAuth */
			$this->_requireSnsOAuth($site);
		}
		$wall = $this->model('matter\wall')->byId($app, 'title');
		if(!$wall){
			$this->outputError('信息墙不存在');
			exit;
		}

		\TPL::assign('title', $wall->title);
		\TPL::output('/site/fe/matter/wall/index');
		exit;

	}
	public function detail_action($site, $app){
		$wall = $this->model('matter\wall')->byId($app, 'title');
		if(!$wall){
			$this->outputError('信息墙不存在');
			exit;
		}

		\TPL::assign('title', $wall->title);
		\TPL::output('/site/fe/matter/wall/main');
		exit;

	}
	/**
	*讨论组列表
	*/
	public function wallList_action($site, $app = null){
		$user = $this->who;

		if(!empty($app)){
			$q = array(
				'id,title,active',
				'xxt_wall',
				"siteid = '{$site}' and id = '{$app}'"
				);
			$walls = $this->model()->query_objs_ss($q);
		}else{
			$p = array(
					'w.id,w.title,e.join_at,e.close_at,w.active',
					'xxt_wall_enroll e,xxt_wall w',
				);
			if(isset($user->sns->wx)){
				$openid = $user->sns->wx->openid;
				$p[2] = "e.siteid = '{$site}' and e.wx_openid = '{$openid}' and e.wid = w.id";
			}elseif(isset($user->sns->yx)){
				$openid = $user->sns->yx->openid;
				$p[2] = "e.siteid = '{$site}' and e.yx_openid = '{$openid}' and e.wid = w.id";
			}elseif(isset($user->sns->qy)){
				$openid = $user->sns->qy->openid;
				$p[2] = "e.siteid = '{$site}' and e.qy_openid = '{$openid}' and e.wid = w.id";
			}else{
				return new \ResponseData(false);
			}

			$walls = $this->model()->query_objs_ss($p);
		}

		return new \ResponseData($walls);

	}
	/**
	 * 用户加入讨论组
	 */
	public function join_action($site, $app){
		$user = $this->who;
		if(!isset($user->sns)){
			return new \ResponseData(false);
		}
		$user2 = new \stdClass;
		if(isset($user->sns->wx)){
			//获取nickname
			$snsUser = $this->model('sns\wx\fan')->byOpenid($site, $user->sns->wx->openid, 'nickname,headimgurl');
			$user2->wx_openid = $user->sns->wx->openid;
			$user2->nickname = $snsUser->nickname;
			$user2->headimgurl = $snsUser->headimgurl;
		}
		if(isset($user->sns->yx)){
			$snsUser = $this->model('sns\yx\fan')->byOpenid($site, $user->sns->yx->openid, 'nickname,headimgurl');
			$user2->yx_openid = $user->sns->yx->openid;
			$user2->nickname = $snsUser->nickname;
			$user2->headimgurl = $snsUser->headimgurl;
		}
		if(isset($user->sns->qy)){
			$snsUser = $this->model('sns\qy\fan')->byOpenid($site, $user->sns->qy->openid, 'nickname,headimgurl');
			$user2->qy_openid = $user->sns->qy->openid;
			$user2->nickname = $snsUser->nickname;
			$user2->headimgurl = $snsUser->headimgurl;
		}
		$user2->userid = $user->uid;
		
		//加入讨论组
		$reply = $this->model('matter\wall')->join($site, $app, $user2, 'click');

		return new \ResponseData($reply);
	}
	/**
	 * 用户退出讨论组
	 */
	public function quit_action($site, $app){
		$user = $this->who;
		if(isset($user->sns->wx)){
			$openid = $user->sns->wx->openid;
			$where = " and wx_openid = '{$openid}'";
		}elseif(isset($user->sns->yx)){
			$openid = $user->sns->yx->openid;
			$where = " and yx_openid = '{$openid}'";
		}elseif(isset($user->sns->qy)){
			$openid = $user->sns->qy->openid;
			$where = " and qy_openid = '{$openid}'";
		}

		$this->model()->update(
			'xxt_wall_enroll',
			array('close_at'=>time()),
			"wid = '{$app}' ".$where
			);

		$wall = $this->model('matter\wall')->byId($app, 'quit_reply');
		$reply = empty($wall->quit_reply) ? '您已退出信息墙' : $wall->quit_reply;
		return new \ResponseData($reply);
	}
	/**
	*详细页信息
	*/
	public function get_action($site, $app){
		$user = $this->who;
		$p = array(
			'id,title,summary,active',
			'xxt_wall',
			"id = '{$app}'"
			);
		$wall = $this->model()->query_obj_ss($p);
		if($wall){
			if($wall->active === 'N'){
				$this->outputError('信息墙已停用');
				exit;
			}
		}

		$q = array(
			'join_at,close_at',
			'xxt_wall_enroll',
			"wid = '{$app}'"
			);
		if(isset($user->sns->wx)){
			$openid = $user->sns->wx->openid;
			$q[2] .= " and wx_openid = '{$openid}'";
		}elseif(isset($user->sns->yx)){
			$openid = $user->sns->yx->openid;
			$q[2] .= " and yx_openid = '{$openid}'";
		}elseif(isset($user->sns->qy)){
			$openid = $user->sns->qy->openid;
			$q[2] .= " and qy_openid = '{$openid}'";
		}
		$wallUser = $this->model()->query_obj_ss($q);
		if($wallUser){
			if($wallUser->close_at === '0'){
				$wallUser->state = 'Y';
			}else{
				$wallUser->state = 'N';
			}
		}else{
			$wallUser = new \stdClass;
			$wallUser->state = 'N';
		}

		$data = array();
		$data['data'] = $wall;
		$data['wallUser'] = $wallUser;
		$data['user'] = $user;
		return new \ResponseData($data);

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