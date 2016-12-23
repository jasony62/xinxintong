<?php
namespace pl\fe\matter\wall;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 信息墙
 */
class users extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/wall/frame');
		exit;
	}
	/**
	 * 获得墙内的所有用户
	 */
	public function list_action($id, $site) {
		$q = array(
			'openid,join_at,last_msg_at,ufrom,userid,nickname',
			'xxt_wall_enroll',
			"siteid='$site' and wid='$id' and close_at=0",
		);
		$users = $this->model()->query_objs_ss($q);

		return new \ResponseData($users);
	}
	/**
	 * 从登记活动导入用户
	 *
	 * @param string $wall
	 * @param string $app
	 */
	public function import_action($id, $app, $site, $type) {
		//查询出此讨论组中已有的openid
		$q = array(
			'openid',
			'xxt_wall_enroll',
			"siteid='$site' and wid = '$id'"
			);
		$wallOpenids = $this->model()->query_vals_ss($q);
		//查询此站点中其它讨论组的openid
		$q2 = array(
			'openid',
			'xxt_wall_enroll',
			"siteid='$site' and wid != '$id'"
			);
		$otherWallOpenids = $this->model()->query_vals_ss($q2);
		//查询出登记活动中的所有userid
		$p = array(
			"distinct userid",
			'xxt_'.$type.'_record',
			"siteid='$site' and state=1 and aid='$app' and userid != ''",
		);
		$userids = $this->model()->query_vals_ss($p);
		//查询出用户所对应的openid并加入讨论组
		$join_at = time();
		$num = 0;
		foreach ($userids as $key => $uid) {
			$p2 = array(
				'ufrom,yx_openid,wx_openid,qy_openid',
				'xxt_site_account',
				"siteid='$site' and uid = '$uid' and ufrom != ''",
			);
			
			$account = $this->model()->query_obj_ss($p2);
			if($account === false){
				continue;
			}
			switch ($account->ufrom) {
				case 'wx':
					$openid = $account->wx_openid;
					$dbUser = $this->model('sns\wx\fan')->byOpenid($site, $openid, 'headimgurl,nickname');
					break;
				case 'yx':
					$openid = $account->yx_openid;
					$dbUser = $this->model('sns\yx\fan')->byOpenid($site, $openid, 'headimgurl,nickname');
					break;
				case 'qy':
					$openid = $account->qy_openid;
					$dbUser = $this->model('sns\qy\fan')->byOpenid($site, $openid, 'headimgurl,nickname');
					break;				
			}
			$headimgurl = $dbUser->headimgurl;
			$nickname = $dbUser->nickname;
			//如果用户已在讨论组中不插入
			if(in_array($openid, $wallOpenids) || $openid=='' ){
				continue;
			}
			//退出其它讨论组
			if(in_array($openid, $otherWallOpenids)){
				$this->model()->update(
					'xxt_wall_enroll',
					array('close_at' => time()),
					"siteid='$site' and openid='$openid' and wid != '$id' "
				);
			}
			
			$sql['siteid'] = $site;
			$sql['wid'] = $id;
			$sql['join_at'] = $join_at;
			$sql['openid'] = $openid;
			$sql['ufrom'] = $account->ufrom;
			$sql['nickname'] = $nickname;
			$sql['userid'] = $uid;
			$sql['headimgurl'] = $headimgurl;
			$sql['matter_type'] = $type;
			$sql['matter_id'] = $app;
			$this->model()->insert('xxt_wall_enroll',$sql,false);
			$num++;
		}

		return new \ResponseData($num);
	}
	/**
	 * 用户导出到登记活动
	 *
	 * @param string $wall
	 * @param string $app
	 */
	public function export_action($id, $app, $onlySpeaker = 'N',$site) {

		$q = array(
			'ufrom,userid,openid,nickname',
			'xxt_wall_enroll',
			"siteid='$site' and wid='$id' and e.close_at=0 ",
		);
		if ($onlySpeaker === 'Y') {
			$q[2] .= ' and e.last_msg_at<>0';
		}

		$users = $this->model()->query_objs_ss($q);

		return new \ResponseData(count($users));
	}
	/**
	 * 将所有用户退出信息墙
	 */
	public function quit_action($id) {
		/**
		 * 清除所有加入的人
		 */
		$rst = $this->model()->delete('xxt_wall_enroll', "wid='$id'");

		return new \ResponseData($rst);
	}

}