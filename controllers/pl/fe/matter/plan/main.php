<?php
namespace pl\fe\matter\plan;

require_once dirname(dirname(__FILE__)) . '/main_base.php';
/*
 * 计划任务活动主控制器
 */
class main extends \pl\fe\matter\main_base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/plan/frame');
		exit;
	}
	/**
	 * 返回视图
	 */
	public function plan_action() {
		\TPL::output('/pl/fe/matter/plan/plan');
		exit;
	}
	/**
	 * 返回一个计划活动
	 */
	public function get_action($id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($id);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		if ($inviteUrl = $modelApp->getInviteUrl($oApp->id, $oApp->siteid)) {
			$oApp->entryUrl = $inviteUrl;
		}
		/*所属项目*/
		if ($oApp->mission_id) {
			$oApp->mission = $this->model('matter\mission')->byId($oApp->mission_id);
		}
		/*包含的所有任务*/
		$oApp->taskSchemas = $this->model('matter\plan\schema\task')->byApp($oApp->id, ['fields' => 'id,title']);
		/* 指定分组活动访问 */
		$oEntryRule = $oApp->entryRule;
		if (isset($oEntryRule->scope->group) && $oEntryRule->scope->group === 'Y') {
			if (isset($oEntryRule->group)) {
				$oRuleApp = $oEntryRule->group;
				if (!empty($oRuleApp->id)) {
					$oGroupApp = $this->model('matter\group')->byId($oRuleApp->id, ['fields' => 'title', 'cascaded' => 'Y']);
					if ($oGroupApp) {
						$oRuleApp->title = $oGroupApp->title;
						if (!empty($oRuleApp->round->id)) {
							$oGroupRnd = $this->model('matter\group\round')->byId($oRuleApp->round->id, ['fields' => 'title']);
							if ($oGroupRnd) {
								$oRuleApp->round->title = $oGroupRnd->title;
							}
						}
						$oApp->groupApp = $oGroupApp;
						$oApp->oRuleApp = $oRuleApp;
					}
				}
			}
		}

		return new \ResponseData($oApp);
	}
	/**
	 * 返回计划活动列表
	 * @param string $onlySns 是否仅查询进入规则为仅限关注用户访问的活动列表
	 *
	 */
	public function list_action($site = null, $mission = null, $page = null, $size = null, $cascaded = '') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oPosted = $this->getPostJson();
		$modelPlan = $this->model('matter\plan');
		$oOptions = [];
		if (!empty($oPosted->byTitle)) {
			$oOptions['byTitle'] = $oPosted->byTitle;
		}
		if (empty($mission)) {
			$site = $modelPlan->escape($site);
			if (!empty($oPosted->byTags)) {
				$oOptions['byTags'] = $oPosted->byTags;
			}
			if (!empty($oPosted->byStar) && $oPosted->byStar === 'Y') {
				$oOptions['byStar'] = $oUser->id;
			}
			$oOptions['user'] = $oUser;
			$result = $modelPlan->bySite($site, $page, $size, $oOptions);
		} else {
			$result = $modelPlan->byMission($mission, $oOptions, $page, $size);
		}

		if (strlen($cascaded) && count($result->apps)) {
			$cascaded = explode(',', $cascaded);
			$modelSchTsk = $this->model('matter\schema\task');
			foreach ($result->apps as &$oApp) {
				if (in_array('task', $cascaded)) {
					/* 轮次 */
					$oApp->rounds = $modelSchTsk->byApp($oApp->id, ['fields' => '*']);
				}
			}
		}

		return new \ResponseData($result);
	}
	/**
	 * 根据设置的进入规则获得活动关联的通讯录
	 */
	public function assocMschema_action($app) {
		$app = $this->escape($app);
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app, ['fields' => 'id,state,entry_rule']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$oEntryRule = $oApp->entryRule;
		if (empty($oEntryRule->scope->member) || $oEntryRule->scope->member !== 'Y' || empty($oEntryRule->member)) {
			return new \ResponseData([]);
		}
		$modelMs = $this->model('site\user\memberschema');
		$mschemas = [];
		foreach ($oEntryRule->member as $mschemaId => $rule) {
			$oSchema = $modelMs->byId($mschemaId);
			$mschemas[] = $oSchema;
		}

		return new \ResponseData($mschemas);
	}
	/**
	 * 创建一个空的计划活动
	 *
	 * @param string $site site's id
	 * @param string $mission mission's id
	 *
	 */
	public function create_action($site, $mission = null) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$oSite = $this->model('site')->byId($site, ['fields' => 'id,heading_pic']);
		if (false === $oSite) {
			return new \ObjectNotFoundError();
		}
		if (empty($mission)) {
			$oMission = null;
		} else {
			$modelMis = $this->model('matter\mission');
			$oMission = $modelMis->byId($mission);
			if (false === $oMission) {
				return new \ObjectNotFoundError();
			}
		}
		$oCustomConfig = $this->getPostJson();

		$modelApp = $this->model('matter\plan')->setOnlyWriteDbConn(true);
		$oNewApp = $modelApp->createByTemplate($oUser, $oSite, $oCustomConfig, $oMission);

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
		$modelApp = $this->model('matter\plan')->setOnlyWriteDbConn(true);
		$oCopied = $modelApp->byId($app);
		if (false === $oCopied) {
			return new \ObjectNotFoundError();
		}

		$current = time();
		/**
		 * 获得的基本信息
		 */
		$oNewApp = new \stdClass;
		$oNewApp->siteid = $site;
		$oNewApp->start_at = $current;
		$oNewApp->title = $modelApp->escape($oCopied->title) . '（副本）';
		$oNewApp->pic = $oCopied->pic;
		$oNewApp->summary = $modelApp->escape($oCopied->summary);
		$oNewApp->entry_rule = json_encode($oCopied->entry_rule);
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
	 * @param string $site
	 * @param string $app
	 *
	 */
	public function update_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app, ['fields' => 'siteid,id,title,summary,pic,mission_id', 'cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		/**
		 * 处理数据
		 */
		$posted = $this->getPostJson();
		$oUpdated = new \stdClass;
		foreach ($posted as $n => $v) {
			switch ($n) {
			case 'entryRule':
				if (isset($v->scope->group) && $v->scope->group === 'Y') {
					if (isset($v->group->title)) {
						unset($v->group->title);
					}
					if (isset($v->group->round->title)) {
						unset($v->group->round->title);
					}
				}
				$oUpdated->entry_rule = $modelApp->escape($modelApp->toJson($v));
				break;
			case 'title':
			case 'summary':
				$oUpdated->{$n} = $modelApp->escape($v);
				break;
			case 'checkSchemas':
				$oUpdated->check_schemas = $modelApp->escape($modelApp->toJson($v));
				break;
			case 'rpConfig':
				$oUpdated->rp_config = $modelApp->escape($modelApp->toJson($v));
				break;
			default:
				$oUpdated->{$n} = $v;
			}
		}

		if ($oApp = $modelApp->modify($oUser, $oApp, $oUpdated)) {
			$this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'U');
		}

		return new \ResponseData($oApp);
	}
	/**
	 * 删除一个活动
	 *
	 * 如果没有登记数据，就将活动彻底删除
	 * 否则只是打标记
	 *
	 * @param string $site
	 * @param string $app
	 */
	public function remove_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelPlan = $this->model('matter\plan');
		$oApp = $modelPlan->byId($app, ['fields' => 'siteid,id,title,summary,pic,mission_id,creater', 'cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		if ($oApp->creater !== $oUser->id) {
			if (!$this->model('site')->isAdmin($oApp->siteid, $oUser->id)) {
				return new \ResponseError('没有删除数据的权限');
			}
			$rst = $modelApp->remove($oUser, $oApp, 'Recycle');
		} else {
			$q = [
				'count(*)',
				'xxt_plan_task',
				["aid" => $oApp->id],
			];
			if ((int) $modelPlan->query_val_ss($q) > 0) {
				$rst = $modelPlan->remove($oUser, $oApp, 'Recycle');
			} else {
				$modelPlan->delete(
					'xxt_plan_user',
					["aid" => $oApp->id]
				);
				$modelPlan->delete(
					'xxt_plan_action_schema',
					["aid" => $oApp->id]
				);
				$modelPlan->delete(
					'xxt_plan_task_schema',
					["aid" => $oApp->id]
				);
				$rst = $modelPlan->remove($oUser, $oApp, 'D');
			}
		}

		return new \ResponseData($rst);
	}
}