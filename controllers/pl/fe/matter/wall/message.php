<?php
namespace pl\fe\matter\wall;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 信息墙
 */
class message extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/wall/frame');
		exit;
	}
	/**
	 * 获得所有消息
	 */
	public function list_action($id, $page = 1, $size = 30, $contain = null, $site) {
		$contain = isset($contain) ? explode(',', $contain) : array();
		$messages = $this->model('matter\wall')->messages($site, $id, $page, $size, $contain);

		return new \ResponseData($messages);
	}
	/**
	 * 获得未审核的消息
	 */
	public function pendingList_action($id, $last = 0, $site) {
		$messages = $this->model('matter\wall')->pendingMessages($site, $id, $last);

		return new \ResponseData(array($messages, time()));
	}
	/*
	* 获取素材分享者列表
	* $startTime 分享开始时间
	*/
	public function listPlayer_action($site, $app, $startTime, $startId = null) {
		$modelWall = $this->model('matter\wall')->setOnlyWriteDbConn(true);
		if (($oApp = $modelWall->byId($app, ['fields' => 'scenario_config,interact_matter'])) === false) {
			return new \ObjectNotFoundError();
		}
		if(empty($oApp->interact_matter)){
			return new \ResponseError('未指定互动素材');
		}
		
		$users = $this->model('matter\wall')->listPlayer($startTime, $startId, $oApp);

		return new \ResponseData($users);
	}
	/**
	 * 批准消息上墙
	 *
	 * 如果需要推送消息，将上墙信息推送给墙内所有用户
	 *
	 * $wid 信息墙ID
	 * $id 消息ID
	 *
	 */
	public function approve_action($wall, $id, $site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model('matter\wall');
		/**
		 * 批准消息
		 */
		$v = $model->approve($site, $wall, $id, $this);
		/**
		 * 是否需要推送消息
		 */
		$wall = $model->byId($wall, 'id,quit_cmd,skip_approve,push_others,quit_reply,user_url');

		if ('Y' === $wall->push_others) {
			$approvedMsg = $model->messageById($wall->id, $id);

			$openid = $approvedMsg->openid;

			switch ($approvedMsg->data_type) {
			case 'text':
				$msg = array(
					'type' => $approvedMsg->data_type,
					'data' => $approvedMsg->data,
				);
				break;
			case 'image':
				$msg = array(
					'type' => $approvedMsg->data_type,
					'data' => array($approvedMsg->data_media_id, $approvedMsg->data),
				);
				break;
			}

			//获得此用户的来源和昵称用于推送消息
			$q = array(
				'nickname,wx_openid,yx_openid,qy_openid',
				'xxt_wall_enroll',
				"wid = '{$wall->id}' and (wx_openid='{$openid}' or yx_openid='{$openid}' or qy_openid='{$openid}')",
				);
			$user2 = $this->model()->query_obj_ss($q);
			$msg['from_nickname'] = $user2->nickname;
			if($user2->wx_openid === $openid){
				$msg['src'] = 'wx';
			}elseif($user2->yx_openid === $openid){
				$msg['src'] = 'yx';
			}elseif($user2->qy_openid === $openid){
				$msg['src'] = 'qy';
			}
			
			$model->push_others($site, $openid, $msg, $wall, $wall->id);
		}

		//记录操作日志
		$matter = $this->model('matter\wall')->byId($wall->id, 'id,title,summary,pic');
		$matter->type = 'wall';
		$this->model('matter\log')->matterOp($site, $user, $matter, 'approve');

		return new \ResponseData($v);
	}
	/**
	 * 拒绝消息上墙
	 *
	 * $wid
	 * $id
	 *
	 */
	public function reject_action($wall, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$v = $this->model('matter\wall')->reject($wall, $id);

		//记录操作日志
		$matter = $this->model('matter\wall')->byId($wall, 'siteid,id,title,summary,pic');
		$matter->type = 'wall';
		$this->model('matter\log')->matterOp($matter->siteid, $user, $matter, 'reject');

		return new \ResponseData($v);
	}
	/**
	 * 清空信息墙的所有数据
	 */
	public function reset_action($id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		/**
		*解除关联活动
		*/
		$this->model()->update(
				'xxt_wall',
				array('data_schemas' => '','source_app' => ''),
				"id='{$id}'"
			);
		/**
		 * 清除所有加入的人
		 */
		$this->model()->delete('xxt_wall_enroll', "wid='$id'");
		/**
		 * 清除所有留言
		 */
		$rst = $this->model()->delete('xxt_wall_log', "wid='$id'");

		//记录操作日志
		$matter = $this->model('matter\wall')->byId($id, 'siteid,id,title,summary,pic');
		$matter->type = 'wall';
		$this->model('matter\log')->matterOp($matter->siteid, $user, $matter, 'reset');

		return new \ResponseData($rst);
	}
	
}