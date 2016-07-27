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
		$this->view_action('/pl/fe/matter/wall/detail');
	}
	/**
	 * 获得墙内的所有用户
	 */
	public function list_action($wall, $siteid) {
		$q = array(
			'e.openid,e.join_at,e.last_msg_at,f.nickname',
			'xxt_wall_enroll e,xxt_fans f',
			"e.siteid='$siteid' and e.wid='$wall' and e.close_at=0 and e.siteid=f.mpid and e.openid=f.openid",
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
	public function import_action($wall, $app, $siteid) {
		$sql = 'insert into xxt_wall_enroll';
		$sql .= '(siteid,wid,join_at,openid)';
		$sql .= " select distinct";
		$sql .= " '$siteid','$wall'," . time();
		$sql .= ',openid';
		$sql .= " from xxt_enroll_record";
		$sql .= " where aid='$app' and state=1";

		$this->model()->insert($sql);

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
	public function export_action($wall, $app, $onlySpeaker = 'N',$siteid) {
		$q = array(
			'e.openid,f.nickname',
			'xxt_wall_enroll e,xxt_fans f',
			"e.siteid='$siteid' and e.wid='$wall' and e.close_at=0 and e.siteid=f.mpid and e.openid=f.openid",
		);
		if ($onlySpeaker === 'Y') {
			$q[2] .= ' and e.last_msg_at<>0';
		}
		$users = $this->model()->query_objs_ss($q);
		if (count($users)) {
			$objApp = new \stdClass;
			$objApp->id = $app;
			$modelRec = $this->model('matter\enroll\record');
			foreach ($users as $user) {
				$user->vid = '';
				$modelRec->add($siteid, $objApp, $user);
			}
		}

		return new \ResponseData(count($users));
	}
	/**
	 * 将所有用户退出信息墙
	 */
	public function quit_action($wall) {
		/**
		 * 清除所有加入的人
		 */
		$rst = $this->model()->delete('xxt_wall_enroll', "wid='$wall'");

		return new \ResponseData($rst);
	}
}