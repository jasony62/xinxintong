<?php
namespace pl\fe\matter\mission;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 项目控制器
 */
class main extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/mission/main');
		exit;
	}
	/**
	 *
	 */
	public function get_action($id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$mission = $this->model('matter\mission')->byId($id);

		return new \ResponseData($mission);
	}
	/**
	 * 任务列表
	 */
	public function list_action($site, $page = 1, $size = 20) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelMis = $this->model('matter\mission');
		$options = array(
			'limit' => (object) array('page' => $page, 'size' => $size),
		);
		$result = $modelMis->bySite($site, $options);

		return new \ResponseData($result);
	}
	/**
	 * 新建任务
	 */
	public function create_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$current = time();
		$site = $this->model('site')->byId($site, array('fields' => 'id,heading_pic'));

		$mission = array();
		/*create empty mission*/
		$mission['siteid'] = $site->id;
		$mission['title'] = '新项目';
		$mission['summary'] = '';
		$mission['pic'] = $site->heading_pic;
		$mission['creater'] = $user->id;
		$mission['creater_src'] = $user->src;
		$mission['creater_name'] = $user->name;
		$mission['create_at'] = $current;
		$mission['modifier'] = $user->id;
		$mission['modifier_src'] = $user->src;
		$mission['modifier_name'] = $user->name;
		$mission['modify_at'] = $current;
		$mission['id'] = $this->model()->insert('xxt_mission', $mission, true);
		/*记录操作日志*/
		$matter = (object) $mission;
		$matter->type = 'mission';
		$this->model('log')->matterOp($site->id, $user, $matter, 'C');
		/*返回结果*/
		$mission = $this->model('matter\mission')->byId($mission['id']);

		return new \ResponseData($matter);
	}
	/**
	 * 删除任务
	 */
	public function remove_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelMis = $this->model('matter\mission');

		$q = array(
			'count(*)',
			'xxt_mission_matter',
			"siteid='$site' and mission_id='$id'",
		);
		$cnt = (int) $modelMis->query_val_ss($q);

		if ($cnt > 0) {
			/* 如果已经素材，就只打标记 */
			$rst = $modelMis->update('xxt_mission', ['state' => 2], "siteid='$site' and id='$id'");
			/* 记录操作日志 */
			if ($rst) {
				$mission = $modelMis->byId($id, 'id,title,summary,pic');
				$mission->type = 'mission';
				$this->model('log')->matterOp($site, $user, $mission, 'D');
			}
		} else {
			/* 清除数据 */
			$modelMis->delete('xxt_mission_phase', "siteid='$site' and mission_id='$id'");
			$rst = $modelMis->delete('xxt_mission', "siteid='$site' and id='$id'");
		}

		return new \ResponseData($rst);
	}
}