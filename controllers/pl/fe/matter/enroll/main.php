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
			$app->mission = $this->model('matter\mission')->byId($app->mission_id, ['cascaded' => 'phase']);
		}

		return new \ResponseData($app);
	}
	/**
	 * 返回登记活动列表
	 */
	public function list_action($site, $page = 1, $size = 30, $mission = null, $scenario = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$result = ['apps' => null, 'total' => 0];
		$model = $this->model();
		$q = [
			"a.*,'enroll' type",
			'xxt_enroll a',
			"state<>0",
		];
		if (!empty($mission)) {
			$q[2] .= " and mission_id=" . $model->escape($mission);
		} else {
			$q[2] .= " and siteid='" . $model->escape($site) . "'";
		}
		if (!empty($scenario)) {
			$q[2] .= " and scenario='" . $model->escape($scenario) . "'";
		}
		$q2['o'] = 'a.modify_at desc';
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
		$newapp = [];
		$site = $this->model('site')->byId($site, ['fields' => 'id,heading_pic']);
		/*从站点或任务获得的信息*/
		if (empty($mission)) {
			$newapp['pic'] = $site->heading_pic;
			$newapp['summary'] = '';
			$newapp['use_mission_header'] = 'N';
			$newapp['use_mission_footer'] = 'N';
		} else {
			$modelMis = $this->model('matter\mission');
			$mission = $modelMis->byId($mission);
			$newapp['pic'] = $mission->pic;
			$newapp['summary'] = $mission->summary;
			$newapp['mission_id'] = $mission->id;
			$newapp['use_mission_header'] = 'Y';
			$newapp['use_mission_footer'] = 'Y';
		}
		$appId = uniqid();
		/* pages */
		if (!empty($scenario) && !empty($template)) {
			$config = $this->_addPageByTemplate($user, $site, $mission, $appId, $scenario, $template, $customConfig);
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
		/* create app */
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
		/* 记录操作日志 */
		$app->type = 'enroll';
		$this->model('matter\log')->matterOp($site->id, $user, $app, 'C');
		/* 记录和任务的关系 */
		if (isset($mission->id)) {
			$modelMis->addMatter($user, $site->id, $mission->id, $app);
		}

		return new \ResponseData($app);
	}
	/**
	 * 从模版创建一个登记活动
	 *
	 * @param int $template
	 *
	 * @return object ResponseData
	 */
	public function createByOther_action($site, $template) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$current = time();
		$modelApp = $this->model('matter\enroll');
		$modelPage = $this->model('matter\enroll\page');
		$modelCode = $this->model('code\page');

		$template = $this->model('template\shop')->byId($template);
		$aid = $template->matter_id;
		$copied = $modelApp->byId($aid);
		$copied->title = $template->title;
		$copied->summary = $template->summary;
		$copied->pic = $template->pic;

		/**获得的基本信息*/
		$newaid = uniqid();
		$newapp = array();
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
		$newapp['title'] = $copied->title;
		$newapp['pic'] = $copied->pic;
		$newapp['summary'] = $copied->summary;
		$newapp['scenario'] = $copied->scenario;
		$newapp['scenario_config'] = $copied->scenario_config;
		$newapp['count_limit'] = $copied->count_limit;
		$newapp['data_schemas'] = $copied->data_schemas;
		$newapp['public_visible'] = $copied->public_visible;
		$newapp['open_lastroll'] = $copied->open_lastroll;
		$newapp['tags'] = $copied->tags;
		$newapp['enrolled_entry_page'] = $copied->enrolled_entry_page;
		$newapp['entry_rule'] = json_encode($copied->entry_rule);
		$newapp['receiver_page'] = $copied->receiver_page;

		$this->model()->insert('xxt_enroll', $newapp, false);

		/**复制自定义页面*/
		if ($copied->pages) {
			foreach ($copied->pages as $ep) {
				$newPage = $modelPage->add($user, $site, $newaid);
				$rst = $modelPage->update(
					'xxt_enroll_page',
					['title' => $ep->title, 'name' => $ep->name, 'type' => $ep->type, 'data_schemas' => $ep->data_schemas, 'act_schemas' => $ep->act_schemas],
					["aid" => $newaid, "id" => $newPage->id]
				);
				$data = array(
					'title' => $ep->title,
					'html' => $ep->html,
					'css' => $ep->css,
					'js' => $ep->js,
				);
				$modelCode->modify($newPage->code_id, $data);
			}
		}

		$app = $modelApp->byId($newaid, ['cascaded' => 'N']);

		/*记录操作日志*/
		$app->type = 'enroll';
		$this->model('matter\log')->matterOp($site, $user, $app, 'C');

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
		$modelApp = $this->model('matter\enroll');
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
		$newapp['summary'] = $modelApp->escape($copied->summary);
		$newapp['scenario'] = $copied->scenario;
		$newapp['scenario_config'] = json_encode($copied->scenario_config);
		$newapp['count_limit'] = $copied->count_limit;
		$newapp['multi_rounds'] = $copied->multi_rounds;
		$newapp['data_schemas'] = $copied->data_schemas;
		$newapp['entry_rule'] = json_encode($copied->entry_rule);
		$newapp['extattrs'] = $copied->extattrs;
		if (!empty($mission)) {
			$newapp['mission_id'] = $mission;
		}

		$this->model()->insert('xxt_enroll', $newapp, false);
		/**
		 * 复制自定义页面
		 */
		if (count($copied->pages)) {
			$modelPage = $this->model('matter\enroll\page');
			foreach ($copied->pages as $ep) {
				$newPage = $modelPage->add($user, $site, $newaid);
				$rst = $modelPage->update(
					'xxt_enroll_page',
					[
						'title' => $ep->title,
						'name' => $ep->name,
						'type' => $ep->type,
						'data_schemas' => $ep->data_schemas,
						'act_schemas' => $ep->act_schemas,
						'user_schemas' => $ep->user_schemas,
					],
					"aid='$newaid' and id=$newPage->id"
				);
				$data = [
					'title' => $ep->title,
					'html' => $ep->html,
					'css' => $ep->css,
					'js' => $ep->js,
				];
				$modelCode->modify($newPage->code_id, $data);
			}
		}

		$app = $modelApp->byId($newaid, ['cascaded' => 'N']);
		/* 记录操作日志 */
		$app->type = 'enroll';
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
			if (in_array($n, ['entry_rule'])) {
				$nv[$n] = $model->escape(urldecode($v));
			} elseif (in_array($n, ['data_schemas'])) {
				$nv[$n] = $model->toJson($v);
			}
		}
		$nv['modifier'] = $user->id;
		$nv['modifier_src'] = $user->src;
		$nv['modifier_name'] = $user->name;
		$nv['modify_at'] = time();

		$rst = $model->update('xxt_enroll', $nv, ["id" => $app]);
		if ($rst) {
			/*更新级联数据*/
			if (isset($nv['data_schemas'])) {
				//$this->_refreshPagesSchema($app);
			}
			/*记录操作日志*/
			$matter = $this->model('matter\\enroll')->byId($app, 'id,title,summary,pic');
			$matter->type = 'enroll';
			$this->model('matter\log')->matterOp($site, $user, $matter, 'U');
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
					$newPageDataSchemas->record->schemas = [];
					foreach ($pageDataSchemas->record->schemas as $pds) {
						if (isset($mapOfDateSchemas->{$pds->id})) {
							$newPageDataSchemas->record->schemas[] = $mapOfDateSchemas->{$pds->id};
						} elseif (in_array($pds->id, ['enrollAt', 'enrollerNickname', 'enrollerHeadpic'])) {
							$newPageDataSchemas->record->schemas[] = $pds;
						}
					}
				}
				if (isset($pageDataSchemas->list)) {
					$newPageDataSchemas->list = clone $pageDataSchemas->list;
					$newPageDataSchemas->list->schemas = [];
					foreach ($pageDataSchemas->list->schemas as $pds) {
						if (isset($mapOfDateSchemas->{$pds->id})) {
							$newPageDataSchemas->list->schemas[] = $mapOfDateSchemas->{$pds->id};
						} elseif (in_array($pds->id, ['enrollAt', 'enrollerNickname', 'enrollerHeadpic'])) {
							$newPageDataSchemas->list->schemas[] = $pds;
						}
					}
				}
			} else {
				$newPageDataSchemas = [];
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
				['data_schemas' => $newPageDataSchemas],
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
			$this->model('matter\log')->matterOp($site, $user, $matter, 'U');
		}

		return new \ResponseData($entryRule);
	}
	/**
	 * 缺省进入规则
	 */
	private function &_defaultEntryRule($site, $appid) {
		/*第一个登记页*/
		$modelPage = $this->model('matter\enroll\page');
		$pages = $modelPage->byApp($appid, ['cascaded' => 'N', 'fields' => 'name,type']);
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
		$page = [
			'title' => '登记信息页',
			'type' => 'I',
			'name' => 'z' . $current,
		];
		$page = $modelPage->add($user, $siteId, $appid, $page);
		/*entry rules*/
		$entryRule = [
			'otherwise' => ['entry' => $page->name],
		];
		/* result page */
		$page = [
			'title' => '查看结果页',
			'type' => 'V',
			'name' => 'z' . ($current + 1),
		];
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
	private function &_addPageByTemplate(&$user, &$site, &$mission, &$app, $scenario, $template, &$customConfig) {
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
		/* 简单schema定义，目前用于投票场景 */
		if (isset($customConfig->simpleSchema)) {
			$config->schema = $modelPage->schemaByText($customConfig->simpleSchema);
		}
		/* 包含项目阶段 */
		if (isset($config->schema_include_mission_phases) && $config->schema_include_mission_phases === 'Y') {
			if (!empty($mission) && $mission->multi_phase === 'Y') {
				$schemaPhase = new \stdClass;
				$schemaPhase->id = 'phase';
				$schemaPhase->title = '项目阶段';
				$schemaPhase->type = 'phase';
				$schemaPhase->ops = [];
				$phases = $this->model('matter\mission\phase')->byMission($mission->id);
				foreach ($phases as $phase) {
					$newOp = new \stdClass;
					$newOp->l = $phase->title;
					$newOp->v = $phase->phase_id;
					$schemaPhase->ops[] = $newOp;
				}
				$config->schema[] = $schemaPhase;
			}
		}
		/**
		 * 处理页面
		 */
		foreach ($pages as $page) {
			$ap = $modelPage->add($user, $site->id, $app, (array) $page);
			/**
			 * 处理页面数据定义
			 */
			if (empty($page->data_schemas) && !empty($config->schema) && !empty($page->simpleConfig)) {
				/* 页面使用应用的所有数据定义 */
				$page->data_schemas = [];
				foreach ($config->schema as $schema) {
					$newPageSchema = new \stdClass;
					$newPageSchema->schema = $schema;
					$newPageSchema->config = clone $page->simpleConfig;
					if ($page->type === 'V') {
						$newPageSchema->config->id = 'V_' . $schema->id;
					}
					$page->data_schemas[] = $newPageSchema;
				}
			} else {
				/* 自动添加项目阶段定义 */
				if (isset($schemaPhase)) {
					if ($page->type === 'I') {
						$newPageSchema = new \stdClass;
						$schemaPhaseConfig = new \stdClass;
						$schemaPhaseConfig->component = 'R';
						$schemaPhaseConfig->align = 'V';
						$newPageSchema->schema = $schemaPhase;
						$newPageSchema->config = $schemaPhaseConfig;
						$page->data_schemas[] = $newPageSchema;
					} else if ($page->type === 'V') {
						$newPageSchema = new \stdClass;
						$schemaPhaseConfig = new \stdClass;
						$schemaPhaseConfig->id = 'V' . time();
						$schemaPhaseConfig->pattern = 'record';
						$schemaPhaseConfig->inline = 'Y';
						$schemaPhaseConfig->splitLine = 'Y';
						$newPageSchema->schema = $schemaPhase;
						$newPageSchema->config = $schemaPhaseConfig;
						$page->data_schemas[] = $newPageSchema;
					}
				}
			}
			$pageSchemas = [];
			$pageSchemas['data_schemas'] = isset($page->data_schemas) ? \TMS_MODEL::toJson($page->data_schemas) : '[]';
			$pageSchemas['act_schemas'] = isset($page->act_schemas) ? \TMS_MODEL::toJson($page->act_schemas) : '[]';
			$rst = $modelPage->update(
				'xxt_enroll_page',
				$pageSchemas,
				"aid='$app' and id={$ap->id}"
			);
			/* 填充页面 */
			$data = [
				'html' => file_get_contents($templateDir . '/' . $page->name . '.html'),
				'css' => file_get_contents($templateDir . '/' . $page->name . '.css'),
				'js' => file_get_contents($templateDir . '/' . $page->name . '.js'),
			];
			/* 页面存在动态信息 */
			$matched = [];
			$pattern = '/<!-- begin: generate by schema -->.*<!-- end: generate by schema -->/s';
			if (preg_match($pattern, $data['html'], $matched)) {
				$html = $modelPage->htmlBySchema($page->data_schemas, $matched[0]);
				$data['html'] = preg_replace($pattern, $html, $data['html']);
			}
			$modelCode->modify($ap->code_id, $data);
		}

		return $config;
	}
	/**
	 * 应用的微信二维码
	 *
	 * @param string $site
	 * @param string $app
	 *
	 */
	public function wxQrcode_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelQrcode = $this->model('sns\wx\call\qrcode');

		$qrcodes = $modelQrcode->byMatter('enroll', $app);

		return new \ResponseData($qrcodes);
	}
	/**
	 * 应用的易信二维码
	 *
	 * @param string $site
	 * @param string $app
	 *
	 */
	public function yxQrcode_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelQrcode = $this->model('sns\yx\call\qrcode');

		$qrcode = $modelQrcode->byMatter('enroll', $app);

		return new \ResponseData($qrcode);
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
		$q = [
			'count(*)',
			'xxt_enroll_record',
			["siteid" => $site, "aid" => $app->id],
		];
		if ((int) $model->query_val_ss($q) > 0) {
			$rst = $model->update(
				'xxt_enroll',
				['state' => 0],
				["id" => $app->id]
			);
		} else {
			$model->delete(
				'xxt_enroll_receiver',
				["aid" => $app->id]
			);
			$model->delete(
				'xxt_enroll_round',
				["aid" => $app->id]
			);
			$model->delete(
				'xxt_code_page',
				"id in (select code_id from xxt_enroll_page where aid='" . $model->escape($app->id) . "')"
			);
			$model->delete(
				'xxt_enroll_page',
				["aid" => $app->id]
			);
			$rst = $model->delete(
				'xxt_enroll',
				["id" => $app->id]
			);
		}
		/*记录操作日志*/
		$app->type = 'enroll';
		$this->model('matter\log')->matterOp($site, $user, $app, 'D');

		return new \ResponseData($rst);
	}
}