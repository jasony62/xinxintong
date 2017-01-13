<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记活动控制器
 */
class receiver extends \pl\fe\matter\base {
	/**
	 * 事件通知接收人
	 *
	 * @param string site
	 * @param string $app
	 */
	public function list_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelRev = $this->model('matter\enroll\receiver');

		$receivers = $modelRev->byApp($site, $app);

		return new \ResponseData($receivers);
	}
	/**
	 * 检查加入的接收人
	 */
	public function afterJoin_action($site, $app, $timestamp = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		if (empty($timestamp)) {
			return new \ResponseData([]);
		}

		$modelRev = $this->model('matter\enroll\receiver');

		$receivers = $modelRev->afterJoin($site, $app, $timestamp);

		return new \ResponseData($receivers);
	}
	/**
	 * 删除接收消息的人
	 *
	 * @param string site
	 * @param string $app
	 * @param string $receiver
	 */
	public function remove_action($site, $app, $receiver) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		/* 记录操作日志 */
		$enroll = $this->model('matter\enroll')->byId($app, array('cascaded' => 'Y'));	
		$enroll->type = 'enroll';
		$this->model('matter\log')->matterOp($site, $user, $enroll, 'D');

		$rst = $this->model()->delete(
			'xxt_enroll_receiver',
			"siteid='$site' and aid='$app' and userid='$receiver'"
		);
		
		return new \ResponseData($rst);
	}
	/**
	 * 添加自定义用户作为登记活动事件接收人
	 *
	 * @param string $site
	 * @param string $app
	 *
	 */
	public function add_action($site, $app) {
		if (false === ($u=$this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\enroll');
		$modelRev = $this->model('matter\enroll\receiver');
		$enroll = $modelApp->byId($app, array('cascaded' => 'Y'));	

		$users=$this->getPostJson();

		foreach ($users as $user) {				
			$uid=$user->uid;
			$nickname=$user->nickname;

			if(empty($modelRev->query_obj_ss(['*','xxt_enroll_receiver',"siteid='$site' and aid='$app' and userid='$uid'"]))){
				$account=$this->model('site\user\account')->byId($uid);	
				$arr=array();
				if(!empty($account->wx_openid)){
					$arr['wx_openid']=$account->wx_openid;
				} 
				if(!empty($account->yx_openid)){
					$arr['yx_openid']=$account->yx_openid;
				}
				if(!empty($account->qy_openid)){				
					$arr['qy_openid']=$account->qy_openid;
				}

				$rst[]=$modelRev->insert(
					'xxt_enroll_receiver',
					[
						'siteid' => $site,
						'aid' => $enroll->id,
						'join_at' => time(),
						'userid' => $uid,
						'nickname' => empty($nickname) ? '未知姓名' : $modelRev->escape($nickname),
						'sns_user'=>json_encode($arr),
					],
					false
				);

				$token=$this->model('q\urltoken');

				if(empty($token->query_obj_ss(["*",'xxt_short_url_token',"code='enro'"]))){
					$token->add('enro','',60*60*24*10);
				}
			}else{
				$rst[]=true;
			}
		}
		/* 记录操作日志 */
		$enroll->type = 'enroll';
		$this->model('matter\log')->matterOp($site, $u, $enroll, 'C');

		return new \ResponseData($rst);
	}
	/**
	 * 获取企业号关注用户
	 */
	public function qyMem_action($site, $page, $size)
	{
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
 		
 		$data=$this->getPostJson();
 		$keyword=isset($data) ? $data->keyword : '';

		$rst=$this->model("sns\\qy\\fan")->getMem($site,$keyword,$page,$size);

		return new \ResponseData($rst);
	}
}