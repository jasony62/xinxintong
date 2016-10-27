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
	public function invite_action() {
		\TPL::output('/pl/fe/matter/mission/invite');
		exit;
	}
	/**
	 * 获得指定的任务
	 *
	 * @param string $site
	 * @param int $id
	 */
	public function get_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		/* 检查权限 */
		$modelAcl = $this->model('matter\mission\acl');
		if (false === ($acl = $modelAcl->byCoworker($id, $user->id))) {
			return new \ResponseError('任务不存在');
		}
		$mission = $this->model('matter\mission')->byId($id, ['cascaded' => 'header_page_name,footer_page_name']);

		return new \ResponseData($mission);
	}
	/**
	 * 当前用户可访问任务列表
	 *
	 * @param string $site
	 */
	public function list_action($site, $page = 1, $size = 20) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelMis = $this->model('matter\mission');
		$options = [
			'limit' => (object) ['page' => $page, 'size' => $size],
		];
		$result = $modelMis->byAcl($user, $options);

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
		$site = $this->model('site')->byId($site, ['fields' => 'id,heading_pic']);

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
		$mission['state'] = 1;
		$mission['id'] = $this->model()->insert('xxt_mission', $mission, true);
		/*记录操作日志*/
		$matter = (object) $mission;
		$matter->type = 'mission';
		$this->model('log')->matterOp($site->id, $user, $matter, 'C');
		/**
		 * 建立缺省的ACL
		 * @todo 是否应该挪到消息队列中实现
		 */
		$modelAcl = $this->model('matter\mission\acl');
		/*任务的创建人加入ACL*/
		$coworker = new \stdClass;
		$coworker->id = $user->id;
		$coworker->label = $user->name;
		$modelAcl->add($user, $matter, $coworker, 'O');
		/*站点的系统管理员加入ACL*/
		$modelAcl->addSiteAdmin($site->id, $user, null, $matter);

		/*返回结果*/
		$mission = $this->model('matter\mission')->byId($mission['id']);

		return new \ResponseData($matter);
	}
	/**
	 * 删除任务
	 * 只有任务的创建人才能删除任务，任务合作者删除任务时，只是将自己从acl列表中移除
	 */
	public function remove_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelMis = $this->model('matter\mission');
		$mission = $modelMis->byId($id, 'id,title,summary,pic,creater');

		$modelAcl = $this->model('matter\mission\acl');
		$acl = $modelAcl->byCoworker($mission->id, $user->id);

		if (in_array($acl->coworker_role, array('O', 'A'))) {
			/* 清空任务的ACL */
			$modelAcl->removeMission($mission);
			/* 记录操作日志 */
			$mission->type = 'mission';
			$this->model('log')->matterOp($site, $user, $mission, 'D');
			/* 删除数据 */
			$q = [
				'count(*)',
				'xxt_mission_matter',
				"mission_id='$id'",
			];
			$cnt = (int) $modelMis->query_val_ss($q);

			if ($cnt > 0) {
				/* 如果已经素材，就只打标记 */
				$rst = $modelMis->update('xxt_mission', ['state' => 2], ["id" => $id]);
			} else {
				/* 清除数据 */
				$modelMis->delete('xxt_mission_phase', ["mission_id" => $id]);
				$rst = $modelMis->delete('xxt_mission', ["id" => $id]);
			}
		} else {
			/* 从访问列表中移除当前用户 */
			$coworker = new \stdClass;
			$coworker->id = $user->id;
			$modelAcl->removeCoworker($mission, $coworker);
		}

		return new \ResponseData($rst);
	}
}