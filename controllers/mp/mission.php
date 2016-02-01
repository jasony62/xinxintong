<?php
namespace mp;

require_once dirname(__FILE__) . "/mp_controller.php";
/**
 *
 */
class mission extends mp_controller {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';

		return $rule_action;
	}
	/**
	 *
	 */
	public function index_action() {
		$this->view_action('/mp/main');
	}
	/**
	 *
	 */
	public function setting_action() {
		$this->view_action('/mp/mission/detail');
	}
	/**
	 *
	 */
	public function matter_action() {
		$this->view_action('/mp/mission/detail');
	}
	/**
	 *
	 */
	public function get_action($id) {
		$mission = $this->model('mission')->byId($id);

		return new \ResponseData($mission);
	}
	/**
	 * 新建任务
	 */
	public function create_action() {
		$user = $this->accountUser();
		$current = time();
		$mpa = $this->model('mp\mpaccount')->getFeature($this->mpid, 'heading_pic');

		$mission = array();
		/*create empty mission*/
		$mission['mpid'] = $this->mpid;
		$mission['title'] = '新任务';
		$mission['summary'] = '';
		$mission['pic'] = $mpa->heading_pic;
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
		$this->model('log')->matterOp($this->mpid, $user, $matter, 'C');

		return new \ResponseData($matter);
	}
	/**
	 * 更新任务设置
	 */
	public function update_action($id) {
		$user = $this->accountUser();
		$model = $this->model();
		/*data*/
		$nv = (array) $this->getPostJson();
		foreach ($nv as $n => $v) {
			if (in_array($n, array('title', 'summary'))) {
				$nv[$n] = $model->escape(urldecode($v));
			}
		}
		/*modifier*/
		$nv['modifier'] = $user->id;
		$nv['modifier_src'] = $user->src;
		$nv['modifier_name'] = $user->name;
		$nv['modify_at'] = time();
		/*update*/
		$rst = $this->model()->update('xxt_mission', $nv, "id='$id'");
		/*记录操作日志*/
		if ($rst) {
			$mission = $this->model('mission')->byId($id, 'id,title,summary,pic');
			$mission->type = 'mission';
			$this->model('log')->matterOp($this->mpid, $user, $mission, 'U');
		}

		return new \ResponseData($rst);
	}
	/**
	 * 删除任务
	 */
	public function remove_action($id) {
		$rsp = null;

		return new \ResponseData($rsp);
	}
	/**
	 * 任务列表
	 */
	public function list_action() {
		$modelMis = $this->model('mission');
		$result = $modelMis->byMpid($this->mpid);

		return new \ResponseData($result);
	}
	/**
	 * 活的任务下的素材
	 *
	 * @param int $id
	 */
	public function mattersList_action($id) {
		$matters = $this->model('mission')->mattersById($this->mpid, $id);

		return new \ResponseData($matters);
	}
}