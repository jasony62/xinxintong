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
			'*',
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
	public function import_action($id, $app, $site) {
		//先查询出讨论组中已有的openid
		$q = array(
			'openid',
			'xxt_wall_enroll',
			"siteid='$site' and wid = '$id'"
			);
		$wall_openids = $this->model()->query_objs_ss($q);
		$wall_openids2 =array();
		foreach ($wall_openids as $key => $wall_openid) {
			$wall_openids2[] = $wall_openid->openid;
		}

		//查询出登记活动中的所有userid
		$p = array(
			'userid',
			'xxt_enroll_record',
			"siteid='$site' and state=1 and aid='$app' and userid != ''",
		);
		$users = $this->model()->query_objs_ss($p);
		$userids = array();
		foreach ($users as $key => $user) {
			$userids[] = $user->userid;
		}
		$userids = array_unique($userids);
		//查询出用户所对应的openid
		$openids = array();
		foreach ($userids as $key => $uid) {
			$p2 = array(
				'uid,ufrom,yx_openid,wx_openid,qy_openid,nickname',
				'xxt_site_account',
				"siteid='$site' and uid = '$uid' and ufrom != ''",
			);
			
			$account = $this->model()->query_obj_ss($p2);
			$account && $openids[] = $account;
		}
		//将用户导入讨论组
		$join_at = time();
		foreach ($openids as $key => $openid2) {
			switch ($openid2->ufrom) {
				case 'wx':
					$openid = $openid2->wx_openid;
					break;
				case 'yx':
					$openid = $openid2->yx_openid;
					break;
				case 'qy':
					$openid = $openid2->qy_openid;
					break;				
			}
			//如果用户已在讨论组中不插入
			if(in_array($openid, $wall_openids2) || $openid=='' ){
				continue;
			}
			$sql = 'insert into xxt_wall_enroll';
			$sql .= '(siteid,wid,join_at,openid,ufrom,nickname,userid)';
			$sql .= "values('{$site}','{$id}',$join_at,'{$openid}','{$openid2->ufrom}','{$openid2->nickname}','{$openid2->uid}')";
			$this->model()->insert($sql);
		}
			
		global $mysqli_w;
		$rows = $mysqli_w->affected_rows;

		return new \ResponseData($rows);
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