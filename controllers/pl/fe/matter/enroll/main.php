<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记活动主控制器
 */
class main extends \pl\fe\matter\base {
	/**
	 *
	 */
	protected function getMatterType() {
		return 'enroll';
	}
	/**
	 * 返回视图
	 */
	public function index_action($site, $id) {
		$app = $this->model('matter\enroll')->byId($id);
		if ($app->state === '2') {
			$this->redirect('/rest/pl/fe/matter/enroll/publish?site=' . $site . '&id=' . $id);
		} else {
			\TPL::output('/pl/fe/matter/enroll/frame');
			exit;
		}
	}
	/**
	 * 返回视图
	 */
	public function app_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 返回视图
	 */
	public function config_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 返回视图
	 */
	public function publish_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 返回视图
	 */
	public function event_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 返回视图
	 */
	public function preview_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 返回视图
	 */
	public function stat_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 返回视图
	 */
	public function coin_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 返回一个登记活动
	 */
	public function get_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$app = $this->model('matter\enroll')->byId($id);
		/**
		 * 活动签到回复消息
		 */
		if ($app->success_matter_type && $app->success_matter_id) {
			$m = $this->model('matter\base')->getMatterInfoById($app->success_matter_type, $app->success_matter_id);
			$app->successMatter = $m;
		}
		if ($app->failure_matter_type && $app->failure_matter_id) {
			$m = $this->model('matter\base')->getMatterInfoById($app->failure_matter_type, $app->failure_matter_id);
			$app->failureMatter = $m;
		}
		/* channels */
		$app->channels = $this->model('matter\channel')->byMatter($id, 'enroll');
		/* acl */
		$app->acl = $this->model('matter\acl')->byMatter($site, 'enroll', $id);
		/* 登记通知接收人 */
		$app->receiver = $this->model('matter\acl')->enrollReceiver($site, $id);
		/* 获得的轮次 */
		if ($rounds = $this->model('matter\enroll\round')->byApp($site, $id)) {
			!empty($rounds) && $app->rounds = $rounds;
		}
		/*所属项目*/
		if ($app->mission_id) {
			$app->mission = $this->model('matter\mission')->byId($app->mission_id, array('cascaded' => 'phase'));
		}

		return new \ResponseData($app);
	}
	/**
	 * 返回登记活动列表
	 *
	 * $src 是否来源于父账号，=p
	 */
	public function list_action($site, $page = 1, $size = 30, $mission = null, $scenario = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$model = $this->model();
		$q = array(
			'a.*',
			'xxt_enroll a',
			"siteid='$site' and state<>0",
		);
		if (!empty($scenario)) {
			$q[2] .= " and scenario='$scenario'";
		}
		if (!empty($mission)) {
			$q[2] .= " and exists(select 1 from xxt_mission_matter where mission_id='$mission' and matter_type='enroll' and matter_id=a.id)";
		}
		$q2['o'] = 'a.modify_at desc';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;
		if ($a = $model->query_objs_ss($q, $q2)) {
			$result['apps'] = $a;
			$q[0] = 'count(*)';
			$total = (int) $model->query_val_ss($q);
			$result['total'] = $total;
			return new \ResponseData($result);
		}
		return new \ResponseData(array());
	}
	/**
	 * 创建一个空的登记活动
	 *
	 * @param string $scenario scenario's name
	 * @param string $template template's name
	 */
	public function create_action($site, $mission = null, $scenario = null, $template = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$customConfig = $this->getPostJson();
		$current = time();
		$newapp = array();
		$site = $this->model('site')->byId($site, array('fields' => 'id,heading_pic'));
		/*从站点或任务获得的信息*/
		if (empty($mission)) {
			$newapp['pic'] = $site->heading_pic;
			$newapp['summary'] = '';
			$newapp['use_mission_header'] = 'N';
			$newapp['use_mission_footer'] = 'N';
		} else {
			$modelMis = $this->model('mission');
			$mission = $modelMis->byId($mission);
			$newapp['pic'] = $mission->pic;
			$newapp['summary'] = $mission->summary;
			$newapp['mission_id'] = $mission->id;
			$newapp['use_mission_header'] = 'Y';
			$newapp['use_mission_footer'] = 'Y';
		}
		$appId = uniqid();
		/*pages*/
		if (!empty($scenario) && !empty($template)) {
			$config = $this->_addPageByTemplate($user, $site->id, $appId, $scenario, $template, $customConfig);
			/*进入规则*/
			$entryRule = $config->entryRule;
			if (isset($config->enrolled_entry_page)) {
				$newapp['enrolled_entry_page'] = $config->enrolled_entry_page;
			}
			/*场景设置*/
			if (isset($config->scenarioConfig)) {
				$scenarioConfig = $config->scenarioConfig;
				$newapp['scenario_config'] = json_encode($scenarioConfig);
			}
			$newapp['scenario'] = $scenario;
		} else {
			$entryRule = $this->_addBlankPage($user, $site->id, $appId);
		}
		if (empty($entryRule)) {
			return new \ResponseError('没有获得页面进入规则');
		}
		/*create app*/
		$newapp['id'] = $appId;
		$newapp['siteid'] = $site->id;
		$newapp['title'] = empty($customConfig->proto->title) ? '新登记活动' : $customConfig->proto->title;
		$newapp['creater'] = $user->id;
		$newapp['creater_src'] = $user->src;
		$newapp['creater_name'] = $user->name;
		$newapp['create_at'] = $current;
		$newapp['modifier'] = $user->id;
		$newapp['modifier_src'] = $user->src;
		$newapp['modifier_name'] = $user->name;
		$newapp['modify_at'] = $current;
		$newapp['entry_rule'] = json_encode($entryRule);
		isset($config) && $newapp['data_schemas'] = \TMS_MODEL::toJson($config->schema);

		$this->model()->insert('xxt_enroll', $newapp, false);
		$app = $this->model('matter\enroll')->byId($appId);
		/*记录操作日志*/
		$app->type = 'enroll';
		$this->model('log')->matterOp($site->id, $user, $app, 'C');
		/*记录和任务的关系*/
		if (isset($mission->id)) {
			$modelMis->addMatter($user, $site->id, $mission->id, $app);
		}

		return new \ResponseData($app);
	}
	/**
	 * 更新活动的属性信息
	 *
	 * @param string $site
	 * @param string $app
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
		foreach ($nv as $n => $v) {
			if (in_array($n, array('entry_rule'))) {
				$nv[$n] = $model->escape(urldecode($v));
			} else if (in_array($n, array('data_schemas'))) {
				$nv[$n] = $model->toJson($v);
			}
		}
		$nv['modifier'] = $user->id;
		$nv['modifier_src'] = $user->src;
		$nv['modifier_name'] = $user->name;
		$nv['modify_at'] = time();

		$rst = $model->update('xxt_enroll', $nv, "id='$app'");
		if ($rst) {
			/*更新级联数据*/
			if (isset($nv['data_schemas'])) {
				//$this->_refreshPagesSchema($app);
			}
			/*记录操作日志*/
			$matter = $this->model('matter\\enroll')->byId($app, 'id,title,summary,pic');
			$matter->type = 'enroll';
			$this->model('log')->matterOp($site, $user, $matter, 'U');
		}

		return new \ResponseData($rst);
	}
	/**
	 * 应用的登记项更新时，级联更新页面的登记项
	 */
	private function _refreshPagesSchema($appId) {
		$app = $this->model('matter\enroll')->byId($appId);
		if (count($app->pages)) {
			$dataSchemas = json_decode($app->data_schemas);
			$mapOfDateSchemas = new \stdClass;
			foreach ($dataSchemas as $ds) {
				$mapOfDateSchemas->{$ds->id} = $ds;
			}
			foreach ($app->pages as $page) {
				if (!empty($page->data_schemas)) {
					$this->_refreshOnePageSchema($appId, $page, $mapOfDateSchemas);
				}
			}
		}
		return true;
	}
	/**
	 * 应用的登记项更新时，级联更新页面的登记项
	 */
	private function _refreshOnePageSchema($appId, &$page, &$mapOfDateSchemas) {
		$pageDataSchemas = json_decode($page->data_schemas);
		if (count($pageDataSchemas)) {
			if ($page->type === 'V') {
				$newPageDataSchemas = new \stdClass;
				if (isset($pageDataSchemas->record)) {
					$newPageDataSchemas->record = clone $pageDataSchemas->record;
					$newPageDataSchemas->record->schemas = array();
					foreach ($pageDataSchemas->record->schemas as $pds) {
						if (isset($mapOfDateSchemas->{$pds->id})) {
							$newPageDataSchemas->record->schemas[] = $mapOfDateSchemas->{$pds->id};
						} else if (in_array($pds->id, array('enrollAt', 'enrollerNickname', 'enrollerHeadpic'))) {
							$newPageDataSchemas->record->schemas[] = $pds;
						}
					}
				}
				if (isset($pageDataSchemas->list)) {
					$newPageDataSchemas->list = clone $pageDataSchemas->list;
					$newPageDataSchemas->list->schemas = array();
					foreach ($pageDataSchemas->list->schemas as $pds) {
						if (isset($mapOfDateSchemas->{$pds->id})) {
							$newPageDataSchemas->list->schemas[] = $mapOfDateSchemas->{$pds->id};
						} else if (in_array($pds->id, array('enrollAt', 'enrollerNickname', 'enrollerHeadpic'))) {
							$newPageDataSchemas->list->schemas[] = $pds;
						}
					}
				}
			} else {
				$newPageDataSchemas = array();
				foreach ($pageDataSchemas as $pds) {
					if (isset($mapOfDateSchemas->{$pds->id})) {
						$newPageDataSchemas[] = $mapOfDateSchemas->{$pds->id};
					}
				}

			}
			$model = $this->model();
			$newPageDataSchemas = $model->toJson($newPageDataSchemas);
			$rst = $model->update(
				'xxt_enroll_page',
				array('data_schemas' => $newPageDataSchemas),
				"aid='$appId' and id={$page->id}"
			);
			return $rst;
		}
		return 0;
	}
	/**
	 * 重置活动进入规则
	 *
	 * @param string $app
	 *
	 */
	public function entryRuleReset_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();
		/*缺省进入规则*/
		$entryRule = $this->_defaultEntryRule($site, $app);
		/*更新数据*/
		$nv['entry_rule'] = $model->toJson($entryRule);
		$nv['modifier'] = $user->id;
		$nv['modifier_src'] = $user->src;
		$nv['modifier_name'] = $user->name;
		$nv['modify_at'] = time();

		$rst = $model->update('xxt_enroll', $nv, "id='$app'");
		/*记录操作日志*/
		if ($rst) {
			$matter = $this->model('matter\\enroll')->byId($app, 'id,title,summary,pic');
			$matter->type = 'enroll';
			$this->model('log')->matterOp($site, $user, $matter, 'U');
		}

		return new \ResponseData($entryRule);
	}
	/**
	 * 缺省进入规则
	 */
	private function &_defaultEntryRule($site, $appid) {
		/*第一个登记页*/
		$modelPage = $this->model('matter\enroll\page');
		$pages = $modelPage->byApp($appid, array('cascaded' => 'N', 'fields' => 'name,type'));
		foreach ($pages as $page) {
			if ($page->type === 'I') {
				$firstInputPage = $page;
				break;
			}
		}
		/*设置规则*/
		$entryRule = new \stdClass;
		$entryRule->scope = 'none';
		$entryRule->otherwise = new \stdClass;
		$entryRule->otherwise->entry = isset($firstInputPage) ? $firstInputPage->name : '';

		return $entryRule;
	}
	/**
	 * 添加空页面
	 */
	private function _addBlankPage($user, $siteId, $appid) {
		$current = time();
		$modelPage = $this->model('matter\enroll\page');
		/* form page */
		$page = array(
			'title' => '登记信息页',
			'type' => 'I',
			'name' => 'z' . $current,
		);
		$page = $modelPage->add($user, $siteId, $appid, $page);
		/*entry rules*/
		$entryRule = array(
			'otherwise' => array('entry' => $page->name),
		);
		/* result page */
		$page = array(
			'title' => '查看结果页',
			'type' => 'V',
			'name' => 'z' . ($current + 1),
		);
		$modelPage->add($user, $siteId, $appid, $page);

		return $entryRule;
	}
	/**
	 * 根据模板生成页面
	 *
	 * @param string $app
	 * @param string $scenario scenario's name
	 * @param string $template template's name
	 */
	private function &_addPageByTemplate(&$user, $siteId, &$app, $scenario, $template, &$customConfig) {
		$templateDir = TMS_APP_TEMPLATE . '/pl/fe/matter/enroll/scenario/' . $scenario . '/templates/' . $template;
		$config = file_get_contents($templateDir . '/config.json');
		$config = preg_replace('/\t|\r|\n/', '', $config);
		$config = json_decode($config);
		$pages = $config->pages;
		if (empty($pages)) {
			return false;
		}
		$modelPage = $this->model('matter\enroll\page');
		$modelCode = $this->model('code\page');
		foreach ($pages as $page) {
			$ap = $modelPage->add($user, $siteId, $app, (array) $page);
			$data = array(
				'html' => file_get_contents($templateDir . '/' . $page->name . '.html'),
				'css' => file_get_contents($templateDir . '/' . $page->name . '.css'),
				'js' => file_get_contents($templateDir . '/' . $page->name . '.js'),
			);
			/*填充页面*/
			$matched = array();
			$pattern = '/<!-- begin: generate by schema -->.*<!-- end: generate by schema -->/s';
			if (preg_match($pattern, $data['html'], $matched)) {
				if (isset($customConfig->simpleSchema)) {
					$config->schema = $modelPage->schemaByText($customConfig->simpleSchema);
				}
				$html = $modelPage->htmlBySchema($config->schema, $matched[0]);
				$data['html'] = preg_replace($pattern, $html, $data['html']);
			}
			$modelCode->modify($ap->code_id, $data);
			/*页面关联的定义*/
			$pageSchemas = array();
			$pageSchemas['data_schemas'] = isset($page->data_schemas) ? \TMS_MODEL::toJson($page->data_schemas) : '[]';
			$pageSchemas['act_schemas'] = isset($page->act_schemas) ? \TMS_MODEL::toJson($page->act_schemas) : '[]';
			$rst = $modelPage->update(
				'xxt_enroll_page',
				$pageSchemas,
				"aid='$app' and id={$ap->id}"
			);
		}

		return $config;
	}
	/**
	 * 删除一个活动
	 *
	 * 如果没有报名数据，就将活动彻底删除
	 * 否则只是打标记
	 *
	 * @param string $app->id
	 */
	public function remove_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$model = $this->model();
		/*在删除数据前获得数据*/
		$app = $this->model('matter\\enroll')->byId($app, 'id,title,summary,pic');
		/*删除和任务的关联*/
		$this->model('mission')->removeMatter($site, $app->id, 'enroll');
		/*check*/
		$q = array(
			'count(*)',
			'xxt_enroll_record',
			"siteid='$site' and aid='$app->id'",
		);
		if ((int) $model->query_val_ss($q) > 0) {
			$rst = $model->update(
				'xxt_enroll',
				array('state' => 0),
				"siteid='$site' and id='$app->id'"
			);
		} else {
			$model->delete(
				'xxt_enroll_receiver',
				"siteid='$site' and aid='$app->id'"
			);
			$model->delete(
				'xxt_enroll_round',
				"siteid='$site' and aid='$app->id'"
			);
			$model->delete(
				'xxt_code_page',
				"id in (select code_id from xxt_enroll_page where aid='$app->id')"
			);
			$model->delete(
				'xxt_enroll_page',
				"siteid='$site' and aid='$app->id'"
			);
			$rst = $model->delete(
				'xxt_enroll',
				"siteid='$site' and id='$app->id'"
			);
		}
		/*记录操作日志*/
		$app->type = 'enroll';
		$this->model('log')->matterOp($site, $user, $app, 'D');

		return new \ResponseData($rst);
	}
	/**
	 * 版本升级
	 */
	public function verUpgrade_Action($site) {
		$result = array();
		/*app's data_schema*/
		$model = $this->model('matter\enroll');
		$apps = $model->bySite($site, 1, 999);
		$apps = $apps['apps'];
		foreach ($apps as $app) {
			if (!empty($app->data_schemas)) {
				$dataSchemas = json_decode($app->data_schemas);
				if (!isset($dataSchemas[0]->config)) {
					$newDataSchemas = array();
					foreach ($dataSchemas as $dataSchema) {
						$schema = new \stdClass;
						$schema->id = $dataSchema->id;
						isset($dataSchema->type) && $schema->type = $dataSchema->type;
						$schema->title = $dataSchema->title;
						isset($dataSchema->ops) && $schema->ops = $dataSchema->ops;

						$newDataSchemas[] = $schema;
					}
					$result[$app->id] = $newDataSchemas;
					/*update*/
					$model->update(
						'xxt_enroll',
						array('data_schemas' => $model->toJson($newDataSchemas)),
						"id='$app->id'"
					);
				}
			}
		}

		return new \ResponseData($result);
	}
}