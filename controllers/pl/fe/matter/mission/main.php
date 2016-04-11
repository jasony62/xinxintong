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
		$user = $this->accountUser();
		if (false === $user) {
			return new \ResponseTimeout();
		}
		$mission = $this->model('mission')->byId($id);

		return new \ResponseData($mission);
	}
	/**
	 * 任务列表
	 */
	public function list_action($site) {
		$user = $this->accountUser();
		if (false === $user) {
			return new \ResponseTimeout();
		}
		$modelMis = $this->model('mission');
		$result = $modelMis->bySite($site);

		return new \ResponseData($result);
	}
	/**
	 * 新建任务
	 */
	public function create_action($site) {
		$user = $this->accountUser();
		if (false === $user) {
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

		return new \ResponseData($matter);
	}
}