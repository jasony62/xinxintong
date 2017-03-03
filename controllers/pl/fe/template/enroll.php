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
	 * 创建模板
	 */
	public function create_action($site){
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmp = $this->model('matter\template\enroll');

		$post = new \stdClass;
		$post->title = '新模板（登记活动）';
		$post->matter_type = 'enroll';
		$site = $this->model('site')->byid($site, ['fields' => 'id,name']);
		$pageConfig = $this->_getSysTemplate();
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
	 * [保存]
	 * @param  [type] $site [description]
	 * @param  [type] $tid  [description]
	 * @param  [type] $vid  [版本id]
	 * @return [type]       [description]
	 */
	public function update_action($site, $tid, $vid){
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$version = $this->model('matter\template\enroll')->checkVersion($site, $vid);
		if($version[0]){
			return new \ResponseError('当前版本已发布，不可更改');
		}

		$modelTmp = $this->model('matter\template');
		/**
		 * 处理数据
		 */
		$current = time();
		$nv = $this->getPostJson();
		foreach ($nv as $n => $v) {
			if ($n === 'data_schemas') {
				$nv->$n = $modelTmp->escape($modelTmp->toJson($v));
			}
		}
		$nv->modifier = $loginUser->id;
		$nv->modifier_name = $loginUser->name;
		$dataTmp = array();
		isset($nv->scenario) && $dataTmp['scenario'] = $nv->scenario;
		isset($nv->title) && $dataTmp['title'] = $nv->title;
		isset($nv->pic) && $dataTmp['pic'] = $nv->pic;
		isset($nv->summary) && $dataTmp['summary'] = $nv->summary;
		isset($nv->visible_scope) && $dataTmp['visible_scope'] = $nv->visible_scope;
		isset($nv->coin) && $dataTmp['coin'] = $nv->coin;
		$rst = $modelTmp->update('xxt_template', $dataTmp, ["id" => $tid]);

		$dataE = array();
		isset($nv->multi_rounds) && $dataE['multi_rounds'] = $nv->multi_rounds;
		isset($nv->data_schemas) && $dataE['data_schemas'] = $nv->data_schemas;
		isset($nv->enrolled_entry_page) && $dataE['enrolled_entry_page'] = $nv->enrolled_entry_page;
		isset($nv->open_lastroll) && $dataE['open_lastroll'] = $nv->open_lastroll;
		isset($nv->up_said) && $dataE['up_said'] = $nv->up_said;
		if(isset($nv->scenario)){
			$pageConfig = $this->_getSysTemplate($nv->scenario, 'simple');
			/* 场景设置 */
			if (isset($pageConfig->scenarioConfig)) {
				$scenarioConfig = $pageConfig->scenarioConfig;
				$dataE->scenario_config = json_encode($scenarioConfig);
			}
		}
		$rst = $modelTmp->update('xxt_template_enroll', $dataE, ["id" => $vid]);
		
		if ($rst) {
			// 记录操作日志
			$matter = $modelTmp->byId($site, $tid, $vid, ['fields'=>'id,title,summary,pic','cascaded'=>'N']);
			$matter->type = 'template';
			$this->model('matter\log')->matterOp($site, $loginUser, $matter, 'U');
		}

		return new \ResponseData($rst);
	}
	/**
	 * 更新活动的页面的属性信息
	 *
	 * string $app 版本的id
	 * $page 页面的id
	 * $cname 页面对应code page id
	 */
	public function updatePage_action($site, $vid, $pageId, $cname) {
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$version = $this->model('matter\template\enroll')->checkVersion($site, $vid);
		if($version[0]){
			return new \ResponseError('当前版本已发布，不可更改');
		}

		$nv = $this->getPostJson();
		$vid = 'template:'.$vid;
		$modelPage = $this->model('matter\enroll\page');
		$page = $modelPage->byId($vid, $pageId);
		if ($page === false) {
			return new \ResponseError('指定的页面不存在');
		}
		/* 更新页面内容 */
		if (isset($nv->html)) {
			$data = [
				'html' => urldecode($nv->html),
			];
			$modelCode = $this->model('code\page');
			$code = $modelCode->lastByName($site, $cname);
			$rst = $modelCode->modify($code->id, $data);
			unset($nv->html);
		}
		/* 更新了除内容外，页面的其他属性 */
		if (count(array_keys(get_object_vars($nv)))) {
			if (isset($nv->data_schemas)) {
				$nv->data_schemas = $modelPage->escape($modelPage->toJson($nv->data_schemas));
			}
			if (isset($nv->act_schemas)) {
				$nv->act_schemas = $modelPage->escape($modelPage->toJson($nv->act_schemas));
			}
			if (isset($nv->user_schemas)) {
				$nv->user_schemas = $modelPage->escape($modelPage->toJson($nv->user_schemas));
			}
			$rst = $modelPage->update(
				'xxt_enroll_page',
				$nv,
				["id" => $page->id]
			);
		}

		// 记录操作日志
		$matter = $this->model('matter\template')->byId($site, $tid, $vid, ['fields'=>'id,title,summary,pic','cascaded'=>'N']);
		$matter->type = 'template';
		$this->model('matter\log')->matterOp($site, $loginUser, $matter, 'U');
		return new \ResponseData($rst);
	}
	/**
	 * 发布模版
	 *
	 * @param string $site
	 * @param string $scope [Platform|Site]
	 */
	public function put_action($site, $tid, $vid) {
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		/* 发布模版 */
		$modelTmp = $this->model('matter\template');
		$template = $modelTmp->byId($site, $tid, $vid, ['cascaded'=>'N']);
		if(!$template){
			return new \ResponseError('模板获取失败');
		}
		$version = null;
		foreach($template->versions as $v){
			if($v->id === $vid){
				$version = $v;
			}
		}
		if(empty($version)){
			return new \ResponseError('版本获取失败');
		}

		//发布模板
		$current = time();
		$rst = $modelTmp->update(
				'xxt_template',
				['put_at' => $current, 'pub_version' => $version->version],
				['id' => $tid]
			);
		if($rst){
			$modelTmp->update(
				'xxt_template_enroll',
				['pub_status' => 'Y'],
				['id' => $vid]
			);
		}

		if($template->put_at === 0){
			/* 首次发布模版获得积分 */
			$modelCoin = $this->model('pl\coin\log');
			$modelCoin->award($loginUser, 'pl.matter.template.put.' . $template->visible_scope, $template);
		}

		// 记录操作日志
		$this->model('matter\log')->matterOp($site, $loginUser, $template, 'put');
		return new \ResponseData($rst);
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