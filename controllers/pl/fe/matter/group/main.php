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
		$app = $this->model('matter\\group')->byId($app);
		/*所属项目*/
		if ($app->mission_id) {
			$app->mission = $this->model('matter\mission')->byId($app->mission_id, array('cascaded' => 'phase'));
		}
		/*关联应用*/
		if (!empty($app->source_app)) {
			$sourceApp = json_decode($app->source_app);
			$options = array('cascaded' => 'N', 'fields' => 'id,title');
			$app->sourceApp = $this->model('matter\\' . $sourceApp->type)->byId($sourceApp->id, $options);
		}

		return new \ResponseData($app);
	}
	/**
	 * 返回分组活动列表
	 */
	public function list_action($site, $page = 1, $size = 30, $mission = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$result = ['apps' => null, 'total' => 0];
		$model = $this->model();
		$q = [
			'*',
			'xxt_group',
			"state<>0",
		];
		if (empty($mission)) {
			$q[2] .= " and siteid='$site'";
		} else {
			$q[2] .= " and mission_id='$mission'";
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
			$modelMis = $this->model('mission');
			$mission = $modelMis->byId($mission);
			$newapp['summary'] = $mission->summary;
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
		$newapp['creater_name'] = $user->name;
		$newapp['create_at'] = $current;
		$newapp['modifier'] = $user->id;
		$newapp['modifier_src'] = $user->src;
		$newapp['modifier_name'] = $user->name;
		$newapp['modify_at'] = $current;
		$this->model()->insert('xxt_group', $newapp, false);
		$app = $this->model('matter\group')->byId($appId);
		/*记录操作日志*/
		$app->type = 'group';
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
		$newapp['creater_name'] = $user->name;
		$newapp['create_at'] = $current;
		$newapp['modifier'] = $user->id;
		$newapp['modifier_src'] = $user->src;
		$newapp['modifier_name'] = $user->name;
		$newapp['modify_at'] = $current;
		$newapp['title'] = $copied->title . '（副本）';
		$newapp['pic'] = $copied->pic;
		$newapp['summary'] = $copied->summary;
		$newapp['scenario'] = $copied->scenario;
		$newapp['data_schemas'] = $copied->data_schemas;
		$newapp['group_rule'] = $copied->group_rule;
		if (!empty($mission)) {
			$newapp['mission_id'] = $mission;
		}

		$this->model()->insert('xxt_group', $newapp, false);

		$app = $modelApp->byId($newaid, ['cascaded' => 'N']);

		/* 记录操作日志 */
		$app->type = 'group';
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
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$model = $this->model();
		/**
		 * 处理数据
		 */
		$nv = (array) $this->getPostJson();
		$nv['modifier'] = $user->id;
		$nv['modifier_src'] = $user->src;
		$nv['modifier_name'] = $user->name;
		$nv['modify_at'] = time();

		$rst = $model->update('xxt_group', $nv, ["id" => $app]);
		/*记录操作日志*/
		if ($rst) {
			$app = $this->model('matter\group')->byId($app, 'id,title,summary,pic');
			$app->type = 'group';
			$this->model('matter\log')->matterOp($site, $user, $app, 'U');
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

		$rounds = array();
		$rule = $this->getPostJson();
		$modelRnd = $this->model('matter\group\round');

		// 清除现有分组结果
		$modelRnd->update('xxt_group_player', ['round_id' => '', 'round_title' => ''], ['aid' => $app]);

		// 清除原有的规则
		$modelRnd->delete(
			'xxt_group_round',
			["aid" => $app]
		);
		/*create targets*/
		$targets = array();
		if (isset($rule->schema)) {
			foreach ($rule->schema->ops as $op) {
				$target = new \stdClass;
				$target->{$rule->schema->id} = $op->v;
				$targets[] = $target;
			}
		}
		/*create round*/
		if (isset($rule->count)) {
			for ($i = 0; $i < $rule->count; $i++) {
				$prototype = array(
					'title' => '分组' . ($i + 1),
					'targets' => $targets,
					'times' => $rule->times,
				);
				$round = $modelRnd->create($app, $prototype);
				$round->targets = json_decode($round->targets);
				$rounds[] = $round;
			}
		}
		// 记录规则
		$rst = $modelRnd->update(
			'xxt_group',
			['group_rule' => $modelRnd->toJson($rule)],
			["id" => $app]
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
		$model = $this->model();
		/*在删除数据前获得数据*/
		$app = $this->model('matter\group')->byId($app, 'id,title,summary,pic');
		/*删除和任务的关联*/
		$this->model('mission')->removeMatter($site, $app->id, 'group');
		/*check*/
		$q = array(
			'count(*)',
			'xxt_group_player',
			["aid" => $app->id],
		);
		if ((int) $model->query_val_ss($q) > 0) {
			$rst = $model->update(
				'xxt_group',
				['state' => 0],
				["id" => $app->id]
			);
		} else {
			$model->delete(
				'xxt_group_round',
				["aid" => $app->id]
			);
			$rst = $model->delete(
				'xxt_group',
				["id" => $app->id]
			);
		}
		/*记录操作日志*/
		$app->type = 'group';
		$this->model('log')->matterOp($site, $user, $app, 'D');

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