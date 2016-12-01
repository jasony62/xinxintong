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
		$ufrom = $this->xxqUfrom($site,$id);

		$q = array(
			'e.openid,e.join_at,e.last_msg_at,f.nickname',
			'xxt_wall_enroll e,xxt_site_'.$ufrom.'fan f',
			"e.siteid='$site' and e.wid='$id' and e.close_at=0 and e.siteid=f.siteid and e.openid=f.openid",
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
		$ufrom = $this->xxqUfrom($site,$id);

		$p = array(
			'userid',
			'xxt_enroll_record',
			"siteid='$site' and state=1 and aid='$app'",
		);
		$records = $this->model()->query_objs_ss($p);
		$users = array();
		foreach ($records as $key => $value) {
			$users[] = $value->userid;
		}
		$users = array_unique($users);

		//先查询出讨论组中已有的openid
		$q = array(
			'openid',
			'xxt_wall_enroll',
			"siteid='$site' and wid = '$id'"
			);
		$openids = $this->model()->query_objs_ss($q);
		$openids2 =array();
		foreach ($openids as $key => $value) {
			$openids2[] = "'".$value->openid."'";
		}
		$stropenid = trim(implode(',', $openids2));

		//查询出此用户的openid
		$accounts = array();
		foreach ($users as $key => $uid) {
			$p2 = array(
				$ufrom.'_openid as openid,nickname',
				'xxt_site_account',
			);
			if(empty($stropenid)){
				$p2['2'] = "siteid='$site' and uid = '$uid' and ".$ufrom."_openid !=''";
			}else{
				$p2['2'] = "siteid='$site' and uid = '$uid' and ".$ufrom."_openid not in (".$stropenid.",'')";
			}
			$account = $this->model()->query_obj_ss($p2);
			$account && $accounts[] = $account;
		}

		foreach ($accounts as $key => $account) {
			$sql = "insert into xxt_wall_enroll(siteid,wid,join_at,openid) values('$site','$id',".time().",'{$account->openid}')";
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
		$ufrom = $this->xxqUfrom($site,$id);

		$q = array(
			'e.openid,f.nickname',
			'xxt_wall_enroll e,xxt_site_'.$ufrom.'fan f',
			"e.siteid='$site' and e.wid='$id' and e.close_at=0 and e.siteid=f.siteid and e.openid=f.openid",
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

	/**
	*
	*/
	public function xxqUfrom($site,$id){
		$p = array(
			'ufrom',
			'xxt_wall',
			"siteid='$site' and id='$id'",
		);
		$wall = $this->model()->query_obj_ss($p);
		$ufrom = empty($wall->ufrom)?'wx':$wall->ufrom;

		return $ufrom;
	}
}