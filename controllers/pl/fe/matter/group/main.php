<?php
namespace pl\fe\matter\group;

require_once dirname(dirname(__FILE__)) . '/main_base.php';
/*
 * 分组活动主控制器
 */
class main extends \pl\fe\matter\main_base {
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
	public function get_action($app = null, $id = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$app = isset($app) ? $app : $id;
		if (empty($app)) {
			return new \ParameterError();
		}
		$oApp = $this->model('matter\group')->byId($app);
		if (false === $oApp && $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		/*所属项目*/
		if ($oApp->mission_id) {
			$oApp->mission = $this->model('matter\mission')->byId($oApp->mission_id);
		}
		/*关联应用*/
		if (!empty($oApp->source_app)) {
			$sourceApp = json_decode($oApp->source_app);
			if ($sourceApp->type === 'mschema') {
				$oApp->sourceApp = $this->model('site\user\memberschema')->byId($sourceApp->id);
				$oApp->sourceApp->type = 'mschema';
			} else {
				$options = ['cascaded' => 'N', 'fields' => 'siteid,id,title'];
				if (in_array($sourceApp->type, ['enroll', 'signin'])) {
					$options['fields'] .= ',assigned_nickname';
				}
				$oApp->sourceApp = $this->model('matter\\' . $sourceApp->type)->byId($sourceApp->id, $options);
			}
		}

		return new \ResponseData($oApp);
	}
	/**
	 * 返回分组活动列表
	 */
	public function list_action($site = null, $mission = null, $page = 1, $size = 30, $cascaded = 'N') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$oPosted = $this->getPostJson();
		$modelGrp = $this->model('matter\group');
		$q = [
			"*",
			'xxt_group g',
			"state<>0",
		];
		if (!empty($oPosted->byTitle)) {
			$q[2] .= " and title like '%" . $modelGrp->escape($oPosted->byTitle) . "%'";
		}
		if (!empty($oPosted->byTags)) {
			foreach ($oPosted->byTags as $tag) {
				$q[2] .= " and matter_mg_tag like '%" . $modelGrp->escape($tag->id) . "%'";
			}
		}
		if (empty($mission)) {
			$site = $modelGrp->escape($site);
			$q[2] .= " and siteid='$site'";
		} else {
			$mission = $modelGrp->escape($mission);
			$q[2] .= " and mission_id='$mission'";
		}
		if (isset($oPosted->byStar) && $oPosted->byStar === 'Y') {
			$q[2] .= " and exists(select 1 from xxt_account_topmatter t where t.matter_type='group' and t.matter_id=g.id and userid='{$oUser->id}')";
		}

		$q2['o'] = 'modify_at desc';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;

		$result = ['apps' => null, 'total' => 0];

		if ($apps = $modelGrp->query_objs_ss($q, $q2)) {
			$modelGrpRnd = $this->model('matter\group\round');
			foreach ($apps as &$oApp) {
				$oApp->type = 'group';
				if ($cascaded === 'Y') {
					$rounds = $modelGrpRnd->byApp($oApp->id);
					$oApp->rounds = $rounds;
				}
			}
			$result['apps'] = $apps;
		}
		if ($page == 1) {
			$result['total'] = count($apps) > 0 ? count($apps) : 0;
		} else {
			$q[0] = 'count(*)';
			$total = (int) $modelGrp->query_val_ss($q);
			$result['total'] = $total;
		}

		return new \ResponseData($result);
	}
	/**
	 * 创建分组活动
	 *
	 * @param string $site
	 * @param string $missioon
	 * @param string $scenario
	 */
	public function create_action($site, $mission = null, $scenario = 'split') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$oSite = $this->model('site')->byId($site, array('fields' => 'id,heading_pic'));
		if (false === $oSite) {
			return new \ObjectNotFoundError();
		}
		if (!empty($mission)) {
			$modelMis = $this->model('matter\mission');
			$oMission = $modelMis->byId($mission);
			if (false === $oMission) {
				return new \ObjectNotFoundError();
			}
		} else {
			$oMission = null;
		}

		$oCustomConfig = $this->getPostJson();
		$modelApp = $this->model('matter\group')->setOnlyWriteDbConn(true);

		$oNewApp = $modelApp->createByConfig($oUser, $oSite, $oCustomConfig, $oMission, $scenario);

		return new \ResponseData($oNewApp);
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
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelApp = $this->model('matter\group')->setOnlyWriteDbConn(true);
		$oCopied = $modelApp->byId($app);
		if (false === $oCopied) {
			return new \ObjectNotFoundError();
		}

		$current = time();
		$modelCode = $this->model('code\page');
		/**
		 * 获得的基本信息
		 */
		$oNewApp = new \stdClass;
		$oNewApp->siteid = $site;
		$oNewApp->title = $modelApp->escape($oCopied->title) . '（副本）';
		$oNewApp->pic = $oCopied->pic;
		$oNewApp->summary = $modelApp->escape($oCopied->summary);
		$oNewApp->scenario = $oCopied->scenario;
		$oNewApp->data_schemas = $modelApp->escape($modelApp->toJson($oCopied->dataSchemas));
		$oNewApp->group_rule = $modelApp->escape($oCopied->group_rule);
		if (!empty($mission)) {
			$oNewApp->mission_id = $mission;
		}
		$oNewApp = $modelApp->create($oUser, $oNewApp);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($site, $oUser, $oNewApp, 'C', (object) ['id' => $oCopied->id, 'title' => $oCopied->title]);

		return new \ResponseData($oNewApp);
	}
	/**
	 * 更新活动的属性信息
	 *
	 * @param string $app app's id
	 *
	 */
	public function update_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelApp = $this->model('matter\group')->setOnlyWriteDbConn(true);
		$oMatter = $modelApp->byId($app, 'id,title,summary,pic,scenario,start_at,end_at,mission_id');
		if (false === $oMatter) {
			return new \ObjectNotFoundError();
		}
		/**
		 * 处理数据
		 */
		$oUpdated = $this->getPostJson();
		foreach ($oUpdated as $n => $v) {
			if (in_array($n, ['title'])) {
				$oUpdated->{$n} = $modelApp->escape($v);
			}
			$oMatter->{$n} = $v;
		}

		if ($oMatter = $modelApp->modify($oUser, $oMatter, $oUpdated)) {
			$this->model('matter\log')->matterOp($site, $oUser, $oMatter, 'U');
		}

		return new \ResponseData($oMatter);
	}
	/**
	 * 更新分组规则
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
			foreach ($oApp->dataSchemas as $oSchema) {
				if (in_array($oSchema->id, $rule->schemas) && !empty($oSchema->ops)) {
					foreach ($oSchema->ops as $op) {
						$target = new \stdClass;
						$target->{$oSchema->id} = $op->v;
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
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelGrp = $this->model('matter\group');
		$oApp = $modelGrp->byId($app, 'siteid,id,title,summary,pic,mission_id,creater');
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		if ($oApp->creater !== $oUser->id) {
			return new \ResponseError('没有删除数据的权限');
		}

		/* check */
		$q = [
			'count(*)',
			'xxt_group_player',
			["aid" => $oApp->id],
		];
		if ((int) $modelGrp->query_val_ss($q) > 0) {
			$rst = $modelGrp->remove($oUser, $oApp, 'Recycle');
		} else {
			$modelGrp->delete(
				'xxt_group_round',
				["aid" => $oApp->id]
			);
			$rst = $modelGrp->remove($oUser, $oApp, 'D');
		}

		return new \ResponseData($rst);
	}
	/**
	 * 进行分组
	 */
	public function execute_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
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