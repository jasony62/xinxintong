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
	 * 删除轮次
	 *
	 * @param string site
	 * @param string $app
	 * @param string $receiver
	 */
	public function remove_action($site, $app, $receiver) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

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
	 * @param string $id
	 *
	 */
	public function add_action($site, $id) {
		$modelApp = $this->model('matter\enroll');
		$modelRev = $this->model('matter\enroll\receiver');

		$app = $modelApp->byId($id, array('cascaded' => 'Y'));
	
		$data=$this->getPostJson();
		$uid=$data->uid;
		$nickname=$data->nickname;

		$account=$this->model('site\user\account')->byId($uid);	
		if(!empty($account->wx_openid)){
			$arr['wx_openid']=$account->wx_openid;
		} 
		if(!empty($account->yx_openid)){
			$arr['yx_openid']=$account->yx_openid;
		}
		if(!empty($account->qy_openid)){				
			$arr['qy_openid']=$account->qy_openid;
		}

	
		$rst=$modelRev->insert(
			'xxt_enroll_receiver',
			[
				'siteid' => $site,
				'aid' => $app->id,
				'join_at' => time(),
				'userid' => $uid,
				'nickname' => empty($nickname) ? '未知姓名' : $modelRev->escape($nickname),
				'sns_user'=>json_encode($arr),
			],
			false
		);

		return new \ResponseData($rst);
	}
	/**
	 * 获取企业号关注用户
	 */
	public function qyMem_action($site,$page,$size)
	{
		$rst=$this->model("sns\qy")->getMem($site,$page,$site);
		return new \ResponseData($rst);
	}
}