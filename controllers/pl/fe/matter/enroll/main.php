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

		$modelEnl = $this->model('matter\enroll');
		$app = $modelEnl->byId($id);

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
		/* 所属项目 */
		if ($app->mission_id) {
			$app->mission = $this->model('matter\mission')->byId($app->mission_id, ['cascaded' => 'phase']);
		}
		/* 关联登记活动 */
		if ($app->enroll_app_id) {
			$app->enrollApp = $modelEnl->byId($app->enroll_app_id);
		}
		/* 关联分组活动 */
		if ($app->group_app_id) {
			$app->groupApp = $this->model('matter\group')->byId($app->group_app_id);
		}

		return new \ResponseData($app);
	}
	/**
	 * 返回登记活动列表
	 * @param string $onlySns 是否仅查询进入规则为仅限关注用户访问的活动列表
	 */
	public function list_action($site = null, $mission = null, $page = 1, $size = 30, $scenario = null, $onlySns = 'N') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$filter = $this->getPostJson();
		$result = ['apps' => null, 'total' => 0];
		$modelApp = $this->model('matter\enroll');
		$q = [
			"a.*,'enroll' type",
			'xxt_enroll a',
			"state<>0",
		];
		if (!empty($mission)) {
			$q[2] .= " and mission_id=" . $modelApp->escape($mission);
		} else {
			$q[2] .= " and siteid='" . $modelApp->escape($site) . "'";
		}
		if ($scenario !== null) {
			$q[2] .= " and scenario='" . $modelApp->escape($scenario) . "'";
		}
		if ($onlySns === 'Y') {
			$q[2] .= " and entry_rule like '%\"scope\":\"sns\"%'";
		}
		if (isset($filter->byTitle)) {
			$q[2] .= " and title like '%" . $modelApp->escape($filter->byTitle) . "%'";
		}

		$q2['o'] = 'a.modify_at desc';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;
		if ($apps = $modelApp->query_objs_ss($q, $q2)) {
			foreach ($apps as &$app) {
				$app->url = $modelApp->getEntryUrl($app->siteid, $app->id);
			}
			$result['apps'] = $apps;
			$q[0] = 'count(*)';
			$total = (int) $modelApp->query_val_ss($q);
			$result['total'] = $total;
		}

		return new \ResponseData($result);
	}
	/**
	 * 创建登记活动
	 *
	 * @param string $site site's id
	 * @param string $mission mission's id
	 * @param string $scenario scenario's name
	 * @param string $template template's name
	 *
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
		/* 使用指定模板 */
		$config = $this->_getSysTemplate($scenario, $template);
		/* 进入规则 */
		$entryRule = $config->entryRule;
		if (empty($entryRule)) {
			return new \ResponseError('没有获得页面进入规则');
		}
		/* 添加页面 */
		$this->_addPageByTemplate($user, $site, $mission, $appId, $config, $customConfig);
		/* 登记数量限制 */
		if (isset($config->count_limit)) {
			$newapp['count_limit'] = $config->count_limit;
		}
		if (isset($config->enrolled_entry_page)) {
			$newapp['enrolled_entry_page'] = $config->enrolled_entry_page;
		}
		/* 场景设置 */
		if (isset($config->scenarioConfig)) {
			$scenarioConfig = $config->scenarioConfig;
			$newapp['scenario_config'] = json_encode($scenarioConfig);
		}
		$newapp['scenario'] = $scenario;
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
		$newapp['can_siteuser'] = 'Y';
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
	 * 从共享模板模板创建登记活动
	 *
	 * @param string $site
	 * @param int $template
	 * @param int $mission
	 *
	 * @return object ResponseData
	 *
	 */
	public function createByOther_action($site, $template, $mission = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$customConfig = $this->getPostJson();
		$current = time();
		$modelApp = $this->model('matter\enroll');
		$modelPage = $this->model('matter\enroll\page');
		$modelCode = $this->model('code\page');

		$template = $this->model('matter\template')->byId($template);
		$aid = $template->matter_id;
		if (false === ($copied = $modelApp->byId($aid))) {
			return new \ResponseError('模板对应的活动已经不存在，无法创建活动');
		}

		/* 检查用户积分 */
		if ($template->coin) {
			$account = $this->model('account')->byId($user->id, ['fields' => 'uid,nickname,coin']);
			if ((int) $account->coin < (int) $template->coin) {
				return new \ResponseError('使用模板【' . $template->title . '】需要积分（' . $template->coin . '），你的积分（' . $account->coin . '）不足');
			}
		}

		/* 创建活动 */
		$template = $modelApp->escape($template);

		$newaid = uniqid();
		$newapp = array();
		if (empty($mission)) {
			$newapp['pic'] = $template->pic;
			$newapp['summary'] = $template->summary;
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
		$newapp['title'] = empty($customConfig->proto->title) ? $template->title : $customConfig->proto->title;
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
		$newapp['scenario'] = $copied->scenario;
		$newapp['scenario_config'] = $copied->scenario_config;
		$newapp['count_limit'] = $copied->count_limit;
		$newapp['data_schemas'] = $modelApp->escape($copied->data_schemas);
		$newapp['public_visible'] = $copied->public_visible;
		$newapp['open_lastroll'] = $copied->open_lastroll;
		$newapp['tags'] = $copied->tags;
		$newapp['enrolled_entry_page'] = $copied->enrolled_entry_page;
		$newapp['entry_rule'] = json_encode($copied->entry_rule);
		$newapp['receiver_page'] = $copied->receiver_page;
		$newapp['template_id'] = $template->id;
		$newapp['can_siteuser'] = 'Y';

		$modelApp->insert('xxt_enroll', $newapp, false);

		/* 复制自定义页面 */
		if ($copied->pages) {
			foreach ($copied->pages as $ep) {
				$newPage = $modelPage->add($user, $site, $newaid);
				$rst = $modelPage->update(
					'xxt_enroll_page',
					['title' => $ep->title, 'name' => $ep->name, 'type' => $ep->type, 'data_schemas' => $modelApp->escape($ep->data_schemas), 'act_schemas' => $modelApp->escape($ep->act_schemas)],
					["aid" => $newaid, "id" => $newPage->id]
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
		if (isset($mission->id)) {
			$modelMis->addMatter($user, $site, $mission->id, $app);
		}

		/* 支付积分 */
		if ($template->coin) {
			$modelCoin = $this->model('pl\coin\log');
			$creator = $this->model('account')->byId($template->creater, ['fields' => 'uid id,nickname name']);
			$modelCoin->transfer('pl.template.use', $user, $creator, (int) $template->coin);
		}
		/* 更新模板使用情况数据 */

		return new \ResponseData($app);
	}
	/**
	 * 创建登记活动
	 *
	 * @param string $site site's id
	 * @param string $mission mission's id
	 *
	 */
	public function createByFile_action($site, $mission = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$config = $this->getPostJson();
		$current = time();
		$newapp = [];
		$site = $this->model('site')->byId($site, ['fields' => 'id,heading_pic']);

		/* 从站点或任务获得的信息 */
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

		empty($config->scenario) && $newapp['scenario'] = $scenario;
		/* 登记数量限制 */
		if (isset($config->count_limit)) {
			$newapp['count_limit'] = $config->count_limit;
		}
		if (!empty($config->pages) && !empty($config->entryRule)) {
			$this->_addPageByTemplate($user, $site, $mission, $appId, $config, $customConfig);
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
		$newapp['can_siteuser'] = 'Y';
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
	 *
	 * 复制一个登记活动
	 *
	 * @param string $site 是否要支持跨团队进行活动的复制？
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
		$newapp['scenario_config'] = $copied->scenario_config;
		$newapp['count_limit'] = $copied->count_limit;
		$newapp['multi_rounds'] = $copied->multi_rounds;
		$newapp['data_schemas'] = $modelApp->escape($copied->data_schemas);
		$newapp['entry_rule'] = json_encode($copied->entry_rule);
		$newapp['extattrs'] = $copied->extattrs;
		$newapp['can_siteuser'] = 'Y';
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
						'data_schemas' => $modelApp->escape($ep->data_schemas),
						'act_schemas' => $modelApp->escape($ep->act_schemas),
						'user_schemas' => $modelApp->escape($ep->user_schemas),
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
		$this->model('matter\log')->matterOp($site, $user, $app, 'C');

		/* 记录和任务的关系 */
		if (isset($mission)) {
			$modelMis = $this->model('matter\mission');
			$modelMis->addMatter($user, $site, $mission, $app);
		}

		return new \ResponseData($app);
	}
	/**
	 * 根据登记记录创建登记活动
	 * 选中的登记项的标题作为题目，选中的记录对应的内容作为选项
	 * 目前支持生成单选题、多选题和打分题
	 * 目前只支持通用登记模板页面
	 *
	 * @param string $site site's id
	 * @param string $app app's id
	 * @param string $mission mission's id
	 *
	 */
	public function createByRecords_action($site, $app, $mission = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\enroll');
		$modelRec = $this->model('matter\enroll\record');

		$customConfig = $this->getPostJson();
		/* 获得指定记录的数据 */
		$records = [];
		$eks = $customConfig->record->eks;
		foreach ($eks as $index => $ek) {
			$records[] = $modelRec->byId($ek);
		}
		/* 生成活动的schema */
		$protoSchema = $customConfig->proto->schema;
		$newSchemas = [];
		foreach ($customConfig->record->schemas as $recordSchema) {
			$newSchema = clone $protoSchema;
			$newSchema->id = $recordSchema->id;
			$newSchema->title = $recordSchema->title;
			$newSchema->ops = [];
			foreach ($records as $index => $record) {
				if (empty($record->data->{$recordSchema->id})) {
					continue;
				}
				$op = new \stdClass;
				$op->v = 'v' . ($index + 1);
				$op->l = $record->data->{$recordSchema->id};
				$newSchema->ops[] = $op;
			}
			$newSchemas[] = $newSchema;
		}
		/* 使用缺省模板 */
		$config = $this->_getSysTemplate('common', 'simple');
		$config->schema_include_mission_phases = 'N';

		/* 修改模板的配置 */
		$config->schema = [];
		foreach ($config->pages as &$page) {
			if ($page->type === 'I') {
				$page->data_schemas = [];
			} else if ($page->type === 'V') {
				$page->data_schemas = [];
			} else if ($page->type === 'L') {
				$page->data_schemas = [];
			}
		}
		foreach ($newSchemas as $newSchema) {
			$config->schema[] = $newSchema;
			foreach ($config->pages as &$page) {
				if ($page->type === 'I') {
					$newWrap = new \stdClass;
					$newWrap->schema = $newSchema;
					$wrapConfig = new \stdClass;
					$wrapConfig->showname = 'label';
					$wrapConfig->required = 'Y';
					$newWrap->config = $wrapConfig;
					$page->data_schemas[] = $newWrap;
				} else if ($page->type === 'V') {
					$newWrap = new \stdClass;
					$newWrap->schema = $newSchema;
					$wrapConfig = new \stdClass;
					$newWrap->config = $wrapConfig;
					$wrapConfig->id = "V1";
					$wrapConfig->pattern = "record";
					$wrapConfig->inline = "N";
					$wrapConfig->splitLine = "Y";
					$page->data_schemas[] = $newWrap;
				}
			}
		}
		/* 进入规则 */
		$entryRule = $config->entryRule;
		if (empty($entryRule)) {
			return new \ResponseError('没有获得页面进入规则');
		}

		$site = $this->model('site')->byId($site, ['fields' => 'id,heading_pic']);
		$copied = $modelApp->byId($app);

		$current = time();
		$appId = uniqid();
		$newapp = [];
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
		/* 添加页面 */
		$this->_addPageByTemplate($user, $site, $mission, $appId, $config, null);
		/* 登记数量限制 */
		if (isset($config->count_limit)) {
			$newapp['count_limit'] = $config->count_limit;
		}
		if (isset($config->enrolled_entry_page)) {
			$newapp['enrolled_entry_page'] = $config->enrolled_entry_page;
		}
		/* 场景设置 */
		if (isset($config->scenarioConfig)) {
			$scenarioConfig = $config->scenarioConfig;
			$newapp['scenario_config'] = json_encode($scenarioConfig);
		}
		$newapp['scenario'] = $customConfig->proto->scenario;
		/* create app */
		$newapp['id'] = $appId;
		$newapp['siteid'] = $site->id;
		$newapp['title'] = empty($customConfig->proto->title) ? '新登记活动' : $customConfig->proto->title;
		$newapp['creater'] = $user->id;
		$newapp['creater_src'] = $user->src;
		$newapp['creater_name'] = $modelApp->escape($user->name);
		$newapp['create_at'] = $current;
		$newapp['modifier'] = $user->id;
		$newapp['modifier_src'] = $user->src;
		$newapp['modifier_name'] = $modelApp->escape($user->name);
		$newapp['modify_at'] = $current;
		$newapp['entry_rule'] = json_encode($entryRule);
		$newapp['can_siteuser'] = 'Y';
		$newapp['data_schemas'] = \TMS_MODEL::toJson($config->schema);

		$modelApp->insert('xxt_enroll', $newapp, false);

		$app = $modelApp->byId($appId);
		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($site->id, $user, $app, 'C');
		/* 记录和任务的关系 */
		if (isset($mission->id)) {
			$modelMis->addMatter($user, $site->id, $mission->id, $app);
		}

		return new \ResponseData($app);
	}
	/**
	 * 更新活动的属性信息
	 *
	 * @param string $site site'id
	 * @param string $app app'id
	 *
	 */
	public function update_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\enroll');
		/**
		 * 处理数据
		 */
		$nv = $this->getPostJson();
		foreach ($nv as $n => $v) {
			if (in_array($n, ['entry_rule', 'data_schemas'])) {
				$nv->$n = $modelApp->escape($modelApp->toJson($v));
			}
		}
		$nv->modifier = $user->id;
		$nv->modifier_src = $user->src;
		$nv->modifier_name = $user->name;
		$nv->modify_at = time();
		$rst = $modelApp->update('xxt_enroll', $nv, ["id" => $app]);
		if ($rst) {
			// 记录操作日志
			$matter = $modelApp->byId($app, 'id,title,summary,pic');
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
			'title' => '填写信息页',
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
	 * 获得系统内置登记活动模板
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
	private function &_addPageByTemplate(&$user, &$site, &$mission, &$app, &$config, $customConfig) {
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
	 * 只允许活动的创建者删除数据，其他用户不允许删除
	 * 如果没有报名数据，就将活动彻底删除，否则只是打标记
	 *
	 * @param string $site site's id
	 * @param string $app app's id
	 */
	public function remove_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$model = $this->model('matter\enroll');
		/* 在删除数据前获得数据 */
		$app = $model->byId($app, 'id,title,summary,pic,mission_id,creater');
		if ($app === false) {
			return new \ResponseError('指定对象不存在');
		}
		if ($app->creater !== $user->id) {
			return new \ResponseError('没有删除数据的权限');
		}
		/* 删除和任务的关联 */
		if ($app->mission_id) {
			$this->model('matter\mission')->removeMatter($app->id, 'enroll');
		}
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
			/* 记录操作日志 */
			$this->model('matter\log')->matterOp($site, $user, $app, 'Recycle');
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
			/* 记录操作日志 */
			$this->model('matter\log')->matterOp($site, $user, $app, 'D');
		}

		return new \ResponseData($rst);
	}
	/**
	 * 恢复被删除的登记活动
	 */
	public function restore_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model('matter\enroll');
		if (false === ($app = $model->byId($id, 'id,title,summary,pic,mission_id'))) {
			return new \ResponseError('数据已经被彻底删除，无法恢复');
		}
		if ($app->mission_id) {
			$modelMis = $this->model('matter\mission');
			$modelMis->addMatter($user, $site, $app->mission_id, $app);
		}

		/* 恢复数据 */
		$rst = $model->update(
			'xxt_enroll',
			['state' => 1],
			["id" => $app->id]
		);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($site, $user, $app, 'Restore');

		return new \ResponseData($rst);
	}
	/**
	 * 将应用定义导出为模板
	 */
	public function exportAsTemplate_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$app = $this->model('matter\enroll')->byId($app);

		$template = new \stdClass;
		/* setting */
		!empty($app->scenario) && $template->scenario = $app->scenario;
		$template->count_limit = $app->count_limit;

		/* schema */
		$template->schema = json_decode($app->data_schemas);

		$template = \TMS_MODEL::toJson($template);
		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		header('Content-disposition: attachment; filename=' . $app->title . '.json');
		header("Content-Type: text/plain");
		header('Content-Length: ' . strlen($template));
		die($template);
	}
	/**
	 * 登记情况汇总信息
	 *
	 * @param string $site site'id
	 * @param string $app app'id
	 *
	 */
	public function summary_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\enroll');
		$app = $modelApp->byId($app);
		$summary = $modelApp->opData($app);

		return new \ResponseData($summary);
	}
}