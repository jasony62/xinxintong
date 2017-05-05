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
		\TPL::output('/pl/fe/matter/mission/frame');
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
	 * @param int $id
	 */
	public function get_action($id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		/* 检查权限 */
		$modelAcl = $this->model('matter\mission\acl');
		if (false === ($acl = $modelAcl->byCoworker($id, $user->id))) {
			return new \ResponseError('项目不存在');
		}
		$mission = $this->model('matter\mission')->byId($id, ['cascaded' => 'header_page_name,footer_page_name']);
		/* 关联的用户名单活动 */
		if ($mission->user_app_id) {
			if ($mission->user_app_type === 'enroll') {
				$mission->userApp = $this->model('matter\enroll')->byId($mission->user_app_id, ['cascaded' => 'N']);
			} else if ($mission->user_app_type === 'signin') {
				$mission->userApp = $this->model('matter\signin')->byId($mission->user_app_id, ['cascaded' => 'N']);
			}
		}

		return new \ResponseData($mission);
	}
	/**
	 * 指定团队下当前用户可访问任务列表
	 *
	 * @param int $page
	 * @param int $size
	 */
	public function list_action($site, $page = 1, $size = 20) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$filter = $this->getPostJson();
		$modelMis = $this->model('matter\mission');
		$options = [
			'limit' => (object) ['page' => $page, 'size' => $size],
		];
		if (!empty($filter->byTitle)) {
			$options['byTitle'] = $modelMis->escape($filter->byTitle);
		}
		$site = $modelMis->escape($site);
		$result = $modelMis->bySite($site, $options);

		return new \ResponseData($result);
	}
	/**
	 * 当前用户可访问任务列表
	 *
	 * @param int $page
	 * @param int $size
	 */
	public function listByUser_action($page = 1, $size = 20) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$filter = $this->getPostJson();
		$modelMis = $this->model('matter\mission');
		$options = [
			'limit' => (object) ['page' => $page, 'size' => $size],
		];
		if (!empty($filter->bySite)) {
			$options['bySite'] = $modelMis->escape($filter->bySite);
		}
		if (!empty($filter->byTitle)) {
			$options['byTitle'] = $modelMis->escape($filter->byTitle);
		}
		
		$result = $modelMis->byAcl($user, $options);

		return new \ResponseData($result);
	}
	/**
	 * 当前用户参与的所有项目所属的团队列表
	 */
	public function listSite_action() {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelMis = $this->model('matter\mission');
		$result = $modelMis->siteByAcl($user);

		return new \ResponseData($result);
	}
	/**
	 * 新建任务
	 *
	 * @param string $site site'id
	 */
	public function create_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$current = time();
		$modelSite = $this->model('site');
		$modelMis = $this->model('matter\mission');
		$modelMis->setOnlyWriteDbConn(true);

		$site = $modelSite->byId($site, ['fields' => 'id,heading_pic']);

		$mission = new \stdClass;
		/*create empty mission*/
		$mission->siteid = $site->id;
		$mission->title = $modelSite->escape($user->name) . '的项目';
		$mission->summary = '';
		$mission->pic = $site->heading_pic;
		$mission->creater = $user->id;
		$mission->creater_src = $user->src;
		$mission->creater_name = $modelSite->escape($user->name);
		$mission->create_at = $current;
		$mission->modifier = $user->id;
		$mission->modifier_src = $user->src;
		$mission->modifier_name = $modelSite->escape($user->name);
		$mission->modify_at = $current;
		$mission->state = 1;
		$mission->id = $modelMis->insert('xxt_mission', $mission, true);

		/*记录操作日志*/
		$mission = $modelMis->byId($mission->id);
		$this->model('matter\log')->matterOp($site->id, $user, $mission, 'C');
		/**
		 * 建立缺省的ACL
		 * @todo 是否应该挪到消息队列中实现
		 */
		$modelAcl = $this->model('matter\mission\acl');
		/*任务的创建人加入ACL*/
		$coworker = new \stdClass;
		$coworker->id = $user->id;
		$coworker->label = $user->name;
		$modelAcl->add($user, $mission, $coworker, 'O');
		/*站点的系统管理员加入ACL*/
		$modelAcl->addSiteAdmin($site->id, $user, null, $mission);

		/*返回结果*/

		return new \ResponseData($mission);
	}
	/**
	 *
	 * 删除项目
	 * 只有任务的创建人和项目所在团队的管理员才能删除任务，任务合作者删除任务时，只是将自己从acl列表中移除
	 *
	 * @param string $site site'id
	 * @param int $id mission'id
	 */
	public function remove_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelMis = $this->model('matter\mission');
		$mission = $modelMis->byId($id, 'id,siteid,title,summary,pic,creater');

		$modelAcl = $this->model('matter\mission\acl');
		$acl = $modelAcl->byCoworker($mission->id, $user->id);

		if (in_array($acl->coworker_role, ['O', 'A'])) {
			/* 当前用户是项目的创建者或者团队管理员 */
			$q = [
				'count(*)',
				'xxt_mission_matter',
				"mission_id='$id'",
			];
			$cnt = (int) $modelMis->query_val_ss($q);

			if ($cnt > 0) {
				/* 如果已经素材，就只打标记 */
				$rst = $modelMis->update('xxt_mission_acl', ['state' => 0], ["mission_id" => $id]);
				$rst = $modelMis->update('xxt_mission', ['state' => 0], ["id" => $id]);
				$this->model('matter\log')->matterOp($mission->siteid, $user, $mission, 'Recycle');
			} else {
				/* 清空任务的ACL */
				$modelAcl->removeMission($mission);
				/* 删除数据 */
				$modelMis->delete('xxt_mission_phase', ["mission_id" => $id]);
				$rst = $modelMis->delete('xxt_mission_acl', ["mission_id" => $id]);
				$rst = $modelMis->delete('xxt_mission', ["id" => $id]);
				$this->model('matter\log')->matterOp($mission->siteid, $user, $mission, 'D');
			}
		} else {
			/* 从访问列表中移除当前用户 */
			$coworker = new \stdClass;
			$coworker->id = $user->id;
			$modelAcl->removeCoworker($mission, $coworker);
			/* 更新用户的操作日志 */
			$this->model('matter\log')->matterOp($mission->siteid, $user, $mission, 'Quit');
		}

		return new \ResponseData('ok');
	}
	/**
	 * 恢复被删除的项目
	 */
	public function restore_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model('matter\mission');
		if (false === ($mission = $model->byId($id, 'id,title,summary,pic'))) {
			return new \ResponseError('数据已经被彻底删除，无法恢复');
		}

		/* 恢复数据 */
		$rst = $model->update(
			'xxt_mission',
			['state' => 1],
			["id" => $mission->id]
		);
		$rst = $model->update(
			'xxt_mission_acl',
			['state' => 1],
			["mission_id" => $mission->id]
		);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($site, $user, $mission, 'Restore');

		return new \ResponseData($rst);
	}
}