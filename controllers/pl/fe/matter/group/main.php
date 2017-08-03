<?php
namespace pl\fe\matter\group;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 分组活动主控制器
 */
class main extends \pl\fe\matter\base {
	/**
	 *
	 */
	protected function getMatterType() {
		return 'group';
	}
	/**
	 * 返回视图
	 */
	public function index_action($site, $id) {
		\TPL::output('/pl/fe/matter/group/frame');
		exit;
	}
	/**
	 * 返回一个分组活动
	 */
	public function get_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$oApp = $this->model('matter\\group')->byId($app);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		/*所属项目*/
		if ($oApp->mission_id) {
			$oApp->mission = $this->model('matter\mission')->byId($oApp->mission_id, array('cascaded' => 'phase'));
		}
		/*关联应用*/
		if (!empty($oApp->source_app)) {
			$sourceApp = json_decode($oApp->source_app);
			if ($sourceApp->type === 'mschema') {
				$oApp->sourceApp = $this->model('site\user\memberschema')->byId($sourceApp->id);
			} else {
				$options = array('cascaded' => 'N', 'fields' => 'siteid,id,title');
				if ($sourceApp->type === 'wall') {
					$options = 'siteid,id,title';
				}
				$oApp->sourceApp = $this->model('matter\\' . $sourceApp->type)->byId($sourceApp->id, $options);
			}
		}

		return new \ResponseData($oApp);
	}
	/**
	 * 返回分组活动列表
	 */
	public function list_action($site = null, $mission = null, $page = 1, $size = 30) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$post = $this->getPostJson();
		$result = ['apps' => null, 'total' => 0];
		$model = $this->model();
		$q = [
			"*,'group' type",
			'xxt_group',
			"state<>0",
		];
		if (!empty($post->byTitle)) {
			$q[2] .= " and title like '%" . $model->escape($post->byTitle) . "%'";
		}
		if(!empty($post->byTags)){
			foreach($post->byTags as $tag){
				$q[2] .= " and matter_mg_tag like '%" . $model->escape($tag->id) . "%'";
			}
		}
		if (empty($mission)) {
			$site = $model->escape($site);
			$q[2] .= " and siteid='$site'";
		} else {
			$mission = $model->escape($mission);
			$q[2] .= " and mission_id='$mission'";
			//按项目阶段筛选
			if (isset($post->mission_phase_id) && !empty($post->mission_phase_id) && $post->mission_phase_id !== "ALL") {
				$mission_phase_id = $model->escape($post->mission_phase_id);
				$q[2] .= " and mission_phase_id='$mission_phase_id'";
			}
		}
		$q2['o'] = 'modify_at desc';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;
		if ($apps = $model->query_objs_ss($q, $q2)) {
			$result['apps'] = $apps;
			$q[0] = 'count(*)';
			$total = (int) $model->query_val_ss($q);
			$result['total'] = $total;
		}

		return new \ResponseData($result);
	}
	/**
	 * 创建空的分组活动
	 *
	 * @param string $site
	 * @param string $missioon
	 */
	public function create_action($site, $mission = null, $scenario = '') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\group');
		$modelApp->setOnlyWriteDbConn(true);

		$customConfig = $this->getPostJson();
		$current = time();
		$newapp = array();
		$appId = uniqid();
		$site = $this->model('site')->byId($site, array('fields' => 'id,heading_pic'));
		if (empty($mission)) {
			$newapp['summary'] = '';
			$newapp['pic'] = $site->heading_pic;
			$newapp['use_mission_header'] = 'N';
			$newapp['use_mission_footer'] = 'N';
		} else {
			$modelMis = $this->model('matter\mission');
			$mission = $modelMis->byId($mission);
			$newapp['summary'] = $modelApp->escape($mission->summary);
			$newapp['pic'] = $mission->pic;
			$newapp['mission_id'] = $mission->id;
			$newapp['use_mission_header'] = 'Y';
			$newapp['use_mission_footer'] = 'Y';
		}
		/*create app*/
		$newapp['id'] = $appId;
		$newapp['siteid'] = $site->id;
		$newapp['title'] = empty($customConfig->proto->title) ? '新分组活动' : $customConfig->proto->title;
		$newapp['scenario'] = $scenario;
		$newapp['creater'] = $user->id;
		$newapp['creater_src'] = $user->src;
		$newapp['creater_name'] = $modelApp->escape($user->name);
		$newapp['create_at'] = $current;
		$newapp['modifier'] = $user->id;
		$newapp['modifier_src'] = $user->src;
		$newapp['modifier_name'] = $modelApp->escape($user->name);
		$newapp['modify_at'] = $current;
		$modelApp->insert('xxt_group', $newapp, false);
		$app = $modelApp->byId($appId);

		/*记录操作日志*/
		$this->model('matter\log')->matterOp($site->id, $user, $app, 'C');

		/*记录和任务的关系*/
		if (isset($mission)) {
			$modelMis->addMatter($user, $site->id, $mission->id, $app);
		}

		return new \ResponseData($app);
	}
	/**
	 *
	 * 复制一个登记活动
	 *
	 * @param string $site
	 * @param string $app
	 * @param int $mission
	 *
	 */
	public function copy_action($site, $app, $mission = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$current = time();
		$modelApp = $this->model('matter\group');
		$modelApp->setOnlyWriteDbConn(true);
		$modelCode = $this->model('code\page');

		$copied = $modelApp->byId($app);
		/**
		 * 获得的基本信息
		 */
		$newaid = uniqid();
		$newapp = [];
		$newapp['siteid'] = $site;
		$newapp['id'] = $newaid;
		$newapp['creater'] = $user->id;
		$newapp['creater_src'] = $user->src;
		$newapp['creater_name'] = $modelApp->escape($user->name);
		$newapp['create_at'] = $current;
		$newapp['modifier'] = $user->id;
		$newapp['modifier_src'] = $user->src;
		$newapp['modifier_name'] = $modelApp->escape($user->name);
		$newapp['modify_at'] = $current;
		$newapp['title'] = $modelApp->escape($copied->title) . '（副本）';
		$newapp['pic'] = $copied->pic;
		$newapp['summary'] = $modelApp->escape($copied->summary);
		$newapp['scenario'] = $copied->scenario;
		$newapp['data_schemas'] = $modelApp->escape($copied->data_schemas);
		$newapp['group_rule'] = $modelApp->escape($copied->group_rule);
		if (!empty($mission)) {
			$newapp['mission_id'] = $mission;
		}

		$modelApp->insert('xxt_group', $newapp, false);
		$app = $modelApp->byId($newaid, ['cascaded' => 'N']);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($site, $user, $app, 'C');

		/* 记录和任务的关系 */
		if (isset($mission)) {
			$modelMis = $this->model('matter\mission');
			$modelMis->addMatter($user, $site, $mission, $app);
		}

		return new \ResponseData($app);
	}
	/**
	 * 更新活动的属性信息
	 *
	 * @param string $aid
	 *
	 */
	public function update_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelApp = $this->model('matter\group');
		$oMatter = $modelApp->byId($app, 'id,title,summary,pic,scenario,start_at,end_at,mission_id,mission_phase_id');
		$modelApp->setOnlyWriteDbConn(true);
		/**
		 * 处理数据
		 */
		$updated = $this->getPostJson();
		foreach ($updated as $n => $v) {
			if (in_array($n, ['title'])) {
				$updated->{$n} = $modelApp->escape($v);
			}
			$oMatter->{$n} = $v;
		}

		$updated->modifier = $oUser->id;
		$updated->modifier_src = $oUser->src;
		$updated->modifier_name = $oUser->name;
		$updated->modify_at = time();

		$rst = $modelApp->update('xxt_group', $updated, ["id" => $oMatter->id]);
		if ($rst) {
			// 更新项目中的素材信息
			if ($oMatter->mission_id) {
				$this->model('matter\mission')->updateMatter($oMatter->mission_id, $oMatter);
			}
			// 记录操作日志并更新信息
			$this->model('matter\log')->matterOp($site, $oUser, $oMatter, 'U');
		}

		return new \ResponseData($rst);
	}
	/**
	 * 删除所有分组
	 */
	public function configRule_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelGrp = $this->model('matter\group');
		$oApp = $modelGrp->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$modelRnd = $this->model('matter\group\round');
		// 清除现有分组结果
		$modelRnd->update('xxt_group_player', ['round_id' => '', 'round_title' => ''], ['aid' => $oApp->id]);
		// 清除原有的规则
		$modelRnd->delete(
			'xxt_group_round',
			["aid" => $oApp->id]
		);

		$targets = [];
		$rule = $this->getPostJson();
		if (!empty($rule->schemas)) {
			/*create targets*/
			$schemasForGroup = new \stdClass;
			foreach ($oApp->dataSchemas as $schema) {
				if (in_array($schema->id, $rule->schemas)) {
					foreach ($schema->ops as $op) {
						$target = new \stdClass;
						$target->{$schema->id} = $op->v;
						$targets[] = $target;
					}
				}
			}
		}

		$rounds = [];
		/*create round*/
		if (isset($rule->count)) {
			for ($i = 0; $i < $rule->count; $i++) {
				$prototype = [
					'title' => '分组' . ($i + 1),
					'targets' => $targets,
					'times' => $rule->times,
				];
				$round = $modelRnd->create($oApp->id, $prototype);
				$round->targets = json_decode($round->targets);
				$rounds[] = $round;
			}
		}
		// 记录规则
		$rst = $modelRnd->update(
			'xxt_group',
			['group_rule' => $modelRnd->toJson($rule)],
			["id" => $oApp->id]
		);

		return new \ResponseData($rounds);
	}
	/**
	 * 删除一个活动
	 *
	 * @param string $site
	 * @param string $app
	 */
	public function remove_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelGrp = $this->model('matter\group');
		/*在删除数据前获得数据*/
		$app = $modelGrp->byId($app, 'id,title,summary,pic,mission_id,creater');
		if ($app->creater !== $user->id) {
			return new \ResponseError('没有删除数据的权限');
		}
		/*删除和任务的关联*/
		if ($app->mission_id) {
			$this->model('matter\mission')->removeMatter($app->id, 'group');
		}
		/*check*/
		$q = [
			'count(*)',
			'xxt_group_player',
			["aid" => $app->id],
		];
		if ((int) $modelGrp->query_val_ss($q) > 0) {
			$rst = $modelGrp->update(
				'xxt_group',
				['state' => 0],
				["id" => $app->id]
			);
			/*记录操作日志*/
			$this->model('matter\log')->matterOp($site, $user, $app, 'Recycle');
		} else {
			$modelGrp->delete(
				'xxt_group_round',
				["aid" => $app->id]
			);
			$rst = $modelGrp->delete(
				'xxt_group',
				["id" => $app->id]
			);
			/*记录操作日志*/
			$this->model('matter\log')->matterOp($site, $user, $app, 'D');
		}

		return new \ResponseData($rst);
	}
	/**
	 * 恢复被删除的分组活动
	 */
	public function restore_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model('matter\group');
		if (false === ($app = $model->byId($id, 'id,title,summary,pic,mission_id'))) {
			return new \ResponseError('数据已经被彻底删除，无法恢复');
		}
		if ($app->mission_id) {
			$modelMis = $this->model('matter\mission');
			$modelMis->addMatter($user, $site, $app->mission_id, $app);
		}

		/* 恢复数据 */
		$rst = $model->update(
			'xxt_group',
			['state' => 1],
			["id" => $app->id]
		);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($site, $user, $app, 'Restore');

		return new \ResponseData($rst);
	}
	/**
	 * 进行分组
	 */
	public function execute_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelGrp = $this->model('matter\group');
		/* 执行分组 */
		$winners = $modelGrp->execute($app);
		if ($winners[0] === false) {
			return new \ResponseError($winners[1]);
		}

		return new \ResponseData($winners[1]);
	}
}