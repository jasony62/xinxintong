<?php
namespace pl\fe\template;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 模板库管理控制器
 */
class main extends \pl\fe\base {
	/**
	 * 返回一个模板
	 * @param  [type] $site [description]
	 * @param  [type] $tid  [模板ID]
	 * @param  [type] $vid  [版本id]
	 * @return [type]       [description]
	 */
	public function get_action($site, $tid, $vid = null){
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$template = $this->model('matter\template')->byId($tid, $vid);
		
		return new \ResponseData($template);
	}
	/**
	 * 获得指定素材对应的模版
	 */
	public function byMatter_action($id, $type) {
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmpl = $this->model('matter\template');
		$template = $modelTmpl->byMatter($id, $type);

		return new \ResponseData($template);
	}
	/**
	 * 发布模版
	 *
	 * @param string $site
	 * @param string $scope [Platform|Site]
	 */
	public function put_action($site) {
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		/* 发布模版 */
		$matter = $this->getPostJson();
		$site = $this->model('site')->byId($site, ['fields' => 'id,name']);

		$modelTmpl = $this->model('matter\template');
		if ($template = $modelTmpl->byMatter($matter->matter_id, $matter->matter_type)) {
			$template = $modelTmpl->putMatter($site, $loginUser, $matter, $template);
		} else {
			$template = $modelTmpl->putMatter($site, $loginUser, $matter);
			/* 首次发布模版获得积分 */
			$modelCoin = $this->model('pl\coin\log');
			$modelCoin->award($loginUser, 'pl.matter.template.put.' . $template->visible_scope, $template);
		}

		return new \ResponseData($template);
	}
	/**
	 * 声请放到平台首页
	 */
	public function pushHome_action($template) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmpl = $this->model('matter\template');

		$rst = $modelTmpl->pushHome($template);

		return new \ResponseData($rst);
	}
	/**
	 * 在指定站点中收藏模版
	 *
	 * @param id $templte
	 * @param string $site 收藏模版的站点ID逗号分隔的字符串
	 * @param string $version 收藏模版的具体版本号
	 */
	public function favor_action($template, $site, $version = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmpl = $this->model('matter\template');

		if (false === ($template = $modelTmpl->byId($template))) {
			return new \ResponseError('数据不存在');
		}

		$modelSite = $this->model('site');
		$siteIds = explode(',', $site);
		foreach ($siteIds as $siteId) {
			$modelTmpl->favorBySite($user, $template, $siteId, $version);
		}

		return new \ResponseData('ok');
	}
	/**
	 * 在指定站点中取消收藏模版
	 *
	 * @param id $templte
	 * @param string $site 收藏模版的站点ID逗号分隔的字符串
	 */
	public function unfavor_action($template, $site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmpl = $this->model('matter\template');

		if (false === ($template = $modelTmpl->byId($template))) {
			return new \ResponseError('数据不存在');
		}

		$rst = $modelTmpl->unfavorBySite($user, $template, $site);

		return new \ResponseData($rst);
	}
	/**
	 * 在指定站点中使用模版
	 *
	 * @param id $templte
	 * @param string $site 模版的站点ID逗号分隔的字符串
	 * @param string $version 使用模版的版本
	 */
	public function purchase_action($template, $site, $version = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmpl = $this->model('matter\template');

		if (false === ($template = $modelTmpl->byId($template))) {
			return new \ResponseError('数据不存在');
		}

		$modelSite = $this->model('site');
		$siteIds = explode(',', $site);
		foreach ($siteIds as $siteId) {
			$modelTmpl->purchaseBySite($user, $template, $siteId, $version);
		}

		return new \ResponseData('ok');
	}
	/**
	 * 当前用户没有收藏过指定模板的站点
	 *
	 * @param int $template
	 */
	public function siteCanFavor_action($template) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmpl = $this->model('matter\template');
		if (false === ($template = $modelTmpl->byId($template))) {
			return new \ResponseError('数据不存在');
		}

		$targets = []; // 符合条件的站点
		$sites = $this->model('site')->byUser($user->id);
		foreach ($sites as &$site) {
			if ($site->id === $template->siteid) {
				continue;
			}
			if ($modelTmpl->isFavorBySite($template, $site->id)) {
				$site->_favored = 'Y';
			}
			$targets[] = $site;
		}

		return new \ResponseData($targets);
	}
	/**
	 * 创建模板
	 */
	public function create_action($site, $matterType){
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$post = new \stdClass;
		$post->title = '新模板（'.$matterType.'）';
		$post->matter_type = $matterType;
		$site = $this->model('site')->byid($site, ['fields' => 'id,name']);

		if($matterType === 'enroll'){
			$modelTmp = $this->model('matter\template\enroll');

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
		}

		$template = $this->model('matter\template')->byId($template->id);
		/* 记录操作日志 */
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

		$modelTmp = $this->model('matter\template');
		$template = $modelTmp->byId($tid, $vid, ['fields'=>'id,title,summary,pic','cascaded'=>'N']);

		$version = $this->model('matter\template\enroll')->checkVersion($site, $vid);
		if($version[0]){
			return new \ResponseError('当前版本已发布，不可更改');
		}

		/**
		 * 处理数据
		 */
		$current = time();
		$nv = $this->getPostJson();
		if($template->matter_type === 'enroll'){
			foreach ($nv as $n => $v) {
				if ($n === 'data_schemas') {
					$nv->$n = $modelTmp->escape($modelTmp->toJson($v));
				}
			}
			$dataTmp = array();
			isset($nv->scenario) && $dataTmp['scenario'] = $nv->scenario;
			isset($nv->title) && $dataTmp['title'] = $nv->title;
			isset($nv->pic) && $dataTmp['pic'] = $nv->pic;
			isset($nv->summary) && $dataTmp['summary'] = $nv->summary;
			isset($nv->visible_scope) && $dataTmp['visible_scope'] = $nv->visible_scope;
			isset($nv->coin) && $dataTmp['coin'] = $nv->coin;
			if(!empty($dataTmp)){
				$rst = $modelTmp->update('xxt_template', $dataTmp, ["id" => $tid]);
			}

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
					$dataE['scenario_config'] = json_encode($scenarioConfig);
				}
			}
			if(!empty($dataE)){
				$rst = $modelTmp->update('xxt_template_enroll', $dataE, ["id" => $vid]);
			}
		}
		
		if ($rst) {
			// 记录操作日志
			$this->model('matter\log')->matterOp($site, $loginUser, $template, 'U');
		}

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
	/**
	 * 发布模版,发布最新编辑的版本
	 *
	 * @param string $site
	 * @param string $scope [Platform|Site]
	 */
	public function putCreate_action($site, $tid) {
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmp = $this->model('matter\template');
		if(false === ($template = $modelTmp->byId($tid, null, ['cascaded'=>'N'])) ){
			return new \ResponseError('模板获取失败，请检查参数');
		}

		$options = [
			'put_at' => $current,
			'pub_version' => $template->last_version
		];
		$post = $this->getPostJson();
		isset($post->visible_scope) && $options['visible_scope'] = $post->visible_scope;

		/* 发布模版 */
		$current = time();
		$rst = $modelTmp->update(
				'xxt_template',
				$options,
				['id' => $tid]
			);
		if($rst){
			$modelTmp->update(
				'xxt_template_enroll',
				['pub_status' => 'Y'],
				['version' => $template->last_version, 'template_id' => $tid]
			);
		}

		if($template->put_at === '0'){
			/* 首次发布模版获得积分 */
			$modelCoin = $this->model('pl\coin\log');
			$modelCoin->award($loginUser, 'pl.matter.template.put.' . $template->visible_scope, $template);
		}

		// 记录操作日志
		$this->model('matter\log')->matterOp($site, $loginUser, $template, 'put');
		return new \ResponseData($rst);
	}
	/**
	 * 取消发布模版
	 *
	 * @param string $site
	 * @param string $scope [Platform|Site]
	 */
	public function unPut_action($site, $tid) {
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmp = $this->model('matter\template');
		if(false === ($template = $modelTmp->byId($tid, null, ['cascaded'=>'N'])) ){
			return new \ResponseError('模板获取失败，请检查参数');
		}
		//取消发布模板
		$current = time();
		$rst = $modelTmp->update(
				'xxt_template',
				['pub_version' => ''],
				['id' => $tid]
			);

		// 记录操作日志
		$this->model('matter\log')->matterOp($site, $loginUser, $template, 'unPut');
		return new \ResponseData($rst);
	}
	/**
	 * [createVersion_action 创建新版本]
	 * @param  [type] $site [description]
	 * @param  [type] $tid  [description]
	 * @param  [type] $lastVersion  [最新版本号]
	 * @return [type]       [description]
	 */
	public function createVersion_action($site, $tid, $lastVersion, $matterType){
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmp = $this->model('matter\template');
		//获取最新版本
		$q = array('*', '', ['siteid' => $site, 'template_id' => $tid, 'version' => $lastVersion] );
		if($matterType === 'enroll'){
			$q[2] = 'xxt_template_enroll';
		}
		$version = $modelTmp->query_obj_ss($q);
		//获取此版本的数据以及页面
		if(false === ($template = $modelTmp->byId($tid, $version->id)) ){
			return new \ResponseError('模板获取失败，请检查参数');
		}

		//创建新版本
		$current = time();
		if($matterType === 'enroll'){
			$versionNew = $this->model('matter\template\enroll')->createMatterEnroll($site, $tid, $template, $loginUser, $current, 'N');
		}
		$rst = $this->update(
			'xxt_template',
			['last_version' => $versionNew->version],
			['siteid' => $site, 'id' => $tid]
		);

		$template = $modelTmp->byId($tid, $versionNew->id);
		// 记录操作日志
		$this->model('matter\log')->matterOp($site, $loginUser, $template, 'createVersion');

		return new \ResponseData($rst);
	}
}