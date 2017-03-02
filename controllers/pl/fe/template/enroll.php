<?php
namespace pl\fe\template;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 登记活动模板管理控制器
 */
class enroll extends \pl\fe\base {
	/**
	 * 
	 */
	public function index_action(){
		\TPL::output('/pl/fe/site/template/enroll/frame');
		exit;
	}
	/**
	 * 获得模板列表
	 *
	 * @param string $matterType
	 * @param int $page
	 * @param int $size
	 *
	 */
	public function list_action($site, $matterType = null, $scenario = null, $scope = 'S', $page = 1, $size = 20) {
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();

		if (in_array($scope, ['P', 'S'])) {
			$q = [
				'*',
				"xxt_template",
				["visible_scope" => $scope],
			];
		}
		if(!empty($matterType)){
			$q[2]['matter_type'] = $matterType;
		}
		if (!empty($scenario)) {
			$q[2]['scenario'] = $scenario;
		}
		if ($scope === 'S') {
			$q[2]['siteid'] = $site;
		}

		$q2 = [
			'r' => ['o' => ($page - 1) * $size, 'l' => $size],
		];
		if (in_array($scope, ['P', 'S'])) {
			$q2['o'] = 'put_at desc';
		}

		$orders = $model->query_objs_ss($q, $q2);
		$q[0] = "count(*)";
		$total = $model->query_val_ss($q);

		return new \ResponseData(['templates' => $orders, 'total' => $total]);
	}
	/**
	 * 返回一个模板
	 */
	public function get_action($site, $tid){
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$template = $this->model('matter\template')->byId($site, $tid);
		
		return new \ResponseData($template);
	}
	/**
	 * 创建模板
	 */
	public function create_action($site){
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmp = $this->model('matter\template\enroll');

		$post = $this->getPostJson();
		$site = $this->model('site')->byid($site, ['fields' => 'id,name']);
		$pageConfig = $this->_getSysTemplate($post->scenario, 'simple');
		/* 场景设置 */
		if (isset($pageConfig->scenarioConfig)) {
			$scenarioConfig = $pageConfig->scenarioConfig;
			$post->scenario_config = json_encode($scenarioConfig);
		}
		if (isset($pageConfig->enrolled_entry_page)) {
			$post->enrolled_entry_page = $pageConfig->enrolled_entry_page;
		}
		if (isset($pageConfig->open_lastroll)) {
			$post->open_lastroll = $pageConfig->open_lastroll;
		}
		isset($pageConfig) && $post->data_schemas = \TMS_MODEL::toJson($pageConfig->schema);

		//创建模板
		$template = $modelTmp->create($site, $loginUser, $post);
		$vid = 'template:'.$template->version->id;
		/* 添加页面 */
		$this->_addPageByTemplate($loginUser, $site, $vid, $pageConfig, $post);

		$template = $this->model('matter\template')->byId($site->id, $template->id);
		/* 记录操作日志 */
		$template->type = 'template';
		$this->model('matter\log')->matterOp($site->id, $loginUser, $template, 'C');

		return new \ResponseData($template);
	}
	/**
	 * 创建默认的模板页面
	 * 如果没有指定场景或模板，那么就使用系统的缺省模板
	 *
	 * @param string $scenario scenario's name
	 * @param string $template template's name
	 *
	 */
	private function _getSysTemplate($scenario = null, $template = null) {
		if (empty($scenario) || empty($template)) {
			$scenario = 'common';
			$template = 'simple';
		}
		$templateDir = TMS_APP_TEMPLATE . '/pl/fe/matter/enroll/scenario/' . $scenario . '/templates/' . $template;
		$config = file_get_contents($templateDir . '/config.json');
		$config = preg_replace('/\t|\r|\n/', '', $config);
		$config = json_decode($config);
		/**
		 * 处理页面
		 */
		if (!empty($config->pages)) {
			foreach ($config->pages as &$page) {
				/* 填充代码 */
				$code = [
					'html' => file_get_contents($templateDir . '/' . $page->name . '.html'),
					'css' => file_get_contents($templateDir . '/' . $page->name . '.css'),
					'js' => file_get_contents($templateDir . '/' . $page->name . '.js'),
				];
				$page->code = $code;
			}
		}

		return $config;
	}
	/**
	 * 根据模板生成页面
	 *
	 * @param string $app
	 * @param string $scenario scenario's name
	 * @param string $template template's name
	 */
	private function &_addPageByTemplate(&$user, &$site, &$app, &$config, $customConfig) {
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
			if (!empty($page->code)) {
				$code = $page->code;
				/* 页面存在动态信息 */
				$matched = [];
				$pattern = '/<!-- begin: generate by schema -->.*<!-- end: generate by schema -->/s';
				if (preg_match($pattern, $code['html'], $matched)) {
					$html = $modelPage->htmlBySchema($page->data_schemas, $matched[0]);
					$code['html'] = preg_replace($pattern, $html, $code['html']);
				}
				$modelCode->modify($ap->code_id, $code);
			}
		}

		return $config;
	}
}