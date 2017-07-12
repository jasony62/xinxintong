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
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelEnl = $this->model('matter\enroll');
		if (false === ($oApp = $modelEnl->byId($id))) {
			return new \ResponseError('指定的数据不存在');
		}

		/* channels */
		$oApp->channels = $this->model('matter\channel')->byMatter($id, 'enroll');
		/* 所属项目 */
		if ($oApp->mission_id) {
			$oApp->mission = $this->model('matter\mission')->byId($oApp->mission_id, ['cascaded' => 'phase']);
		}
		/* 关联登记活动 */
		if ($oApp->enroll_app_id) {
			$oApp->enrollApp = $modelEnl->byId($oApp->enroll_app_id, ['cascaded' => 'N']);
		}
		/* 关联分组活动 */
		if ($oApp->group_app_id) {
			$oApp->groupApp = $this->model('matter\group')->byId($oApp->group_app_id);
		}

		return new \ResponseData($oApp);
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
		if (!empty($scenario)) {
			$q[2] .= " and scenario='" . $modelApp->escape($scenario) . "'";
		}
		if ($onlySns === 'Y') {
			$q[2] .= " and entry_rule like '%\"scope\":\"sns\"%'";
		}
		if (isset($filter->byTitle)) {
			$q[2] .= " and title like '%" . $modelApp->escape($filter->byTitle) . "%'";
		}
		if (isset($filter->mission_phase_id) && !empty($filter->mission_phase_id) && $filter->mission_phase_id !== "ALL") {
			$q[2] .= " and mission_phase_id = '" . $modelApp->escape($filter->mission_phase_id) . "'";
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
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\enroll');
		$modelApp->setOnlyWriteDbConn(true);

		$customConfig = $this->getPostJson();
		$current = time();
		$oNewApp = new \stdClass;
		$oSite = $this->model('site')->byId($site, ['fields' => 'id,heading_pic']);
		/*从站点或任务获得的信息*/
		if (empty($mission)) {
			$oNewApp->pic = $oSite->heading_pic;
			$oNewApp->summary = '';
			$oNewApp->use_mission_header = 'N';
			$oNewApp->use_mission_footer = 'N';
			$oMission = null;
		} else {
			$modelMis = $this->model('matter\mission');
			$oMission = $modelMis->byId($mission);
			$oNewApp->pic = $oMission->pic;
			$oNewApp->summary = $oMission->summary;
			$oNewApp->mission_id = $oMission->id;
			$oNewApp->use_mission_header = 'Y';
			$oNewApp->use_mission_footer = 'Y';
			$oMisEntryRule = $oMission->entry_rule;
		}
		$appId = uniqid();
		/* 使用指定模板 */
		$config = $this->_getSysTemplate($scenario, $template);
		/* 添加页面 */
		$this->_addPageByTemplate($oUser, $oSite, $oMission, $appId, $config, $customConfig);
		/* 进入规则 */
		$oEntryRule = $config->entryRule;
		if (empty($oEntryRule)) {
			return new \ResponseError('没有获得页面进入规则');
		}
		if (isset($oMisEntryRule)) {
			if (isset($oMisEntryRule->scope) && $oMisEntryRule->scope !== 'none') {
				$oEntryRule->scope = $oMisEntryRule->scope;
				switch ($oEntryRule->scope) {
				case 'member':
					if (isset($oMisEntryRule->member)) {
						$oEntryRule->member = $oMisEntryRule->member;
						foreach ($oEntryRule->member as &$oRule) {
							$oRule->entry = isset($oEntryRule->otherwise->entry) ? $oEntryRule->otherwise->entry : '';
						}
						$oEntryRule->other = new \stdClass;
						$oEntryRule->other->entry = '$memberschema';
					}
					break;
				case 'sns':
					$oEntryRule->sns = new \stdClass;
					if (isset($oMisEntryRule->sns)) {
						foreach ($oMisEntryRule->sns as $snsName => $oRule) {
							if (isset($oRule->entry) && $oRule->entry === 'Y') {
								$oEntryRule->sns->{$snsName} = new \stdClass;
								$oEntryRule->sns->{$snsName}->entry = isset($oEntryRule->otherwise->entry) ? $oEntryRule->otherwise->entry : '';
							}
						}
						$oEntryRule->other = new \stdClass;
						$oEntryRule->other->entry = '$mpfollow';
					}
					break;
				}
			}
		}
		/* 登记数量限制 */
		if (isset($config->count_limit)) {
			$oNewApp->count_limit = $config->count_limit;
		}
		if (isset($config->enrolled_entry_page)) {
			$oNewApp->enrolled_entry_page = $config->enrolled_entry_page;
		}
		/* 场景设置 */
		if (isset($config->scenarioConfig)) {
			$scenarioConfig = $config->scenarioConfig;
			$oNewApp->scenario_config = json_encode($scenarioConfig);
		}
		$oNewApp->scenario = $scenario;
		/* create app */
		$oNewApp->id = $appId;
		$oNewApp->siteid = $oSite->id;
		$oNewApp->title = empty($customConfig->proto->title) ? '新登记活动' : $modelApp->escape($customConfig->proto->title);
		$oNewApp->creater = $oUser->id;
		$oNewApp->creater_src = $oUser->src;
		$oNewApp->creater_name = $modelApp->escape($oUser->name);
		$oNewApp->create_at = $current;
		$oNewApp->modifier = $oUser->id;
		$oNewApp->modifier_src = $oUser->src;
		$oNewApp->modifier_name = $modelApp->escape($oUser->name);
		$oNewApp->modify_at = $current;
		$oNewApp->entry_rule = json_encode($oEntryRule);
		$oNewApp->can_siteuser = 'Y';
		isset($config) && $oNewApp->data_schemas = $modelApp->toJson($config->schema);

		/*任务码*/
		$entryUrl = $modelApp->getOpUrl($oSite->id, $appId);
		$code = $this->model('q\url')->add($oUser, $oSite->id, $entryUrl, $oNewApp->title);
		$oNewApp->op_short_url_code = $code;

		$modelApp->insert('xxt_enroll', $oNewApp, false);

		/* 记录操作日志 */
		$oNewApp->type = 'enroll';
		$this->model('matter\log')->matterOp($oSite->id, $oUser, $oNewApp, 'C');
		/* 记录和任务的关系 */
		if (isset($oMission->id)) {
			$modelMis->addMatter($oUser, $oSite->id, $oMission->id, $oNewApp);
		}

		return new \ResponseData($oNewApp);
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
	public function createByOther_action($site, $template, $vid = null, $mission = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$customConfig = $this->getPostJson();
		$current = time();
		$modelApp = $this->model('matter\enroll');
		$modelApp->setOnlyWriteDbConn(true);
		$modelPage = $this->model('matter\enroll\page');
		$modelCode = $this->model('code\page');

		$template = $this->model('matter\template')->byId($template, $vid);
		if (empty($template->pub_version)) {
			return new \ResponseError('模板已下架');
		}
		if ($template->pub_status === 'N') {
			return new \ResponseError('当前版本未发布，无法使用');
		}

		/* 检查用户积分 */
		if ($template->coin) {
			$account = $this->model('account')->byId($user->id, ['fields' => 'uid,nickname,coin']);
			if ((int) $account->coin < (int) $template->coin) {
				return new \ResponseError('使用模板【' . $template->title . '】需要积分（' . $template->coin . '），你的积分（' . $account->coin . '）不足');
			}
		}

		/* 创建活动 */
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
		$newapp['scenario'] = $template->scenario;
		$newapp['scenario_config'] = $template->scenario_config;
		$newapp['multi_rounds'] = $template->multi_rounds;
		$newapp['data_schemas'] = $modelApp->escape($template->data_schemas);
		$newapp['open_lastroll'] = $template->open_lastroll;
		$newapp['enrolled_entry_page'] = $template->enrolled_entry_page;
		$newapp['template_id'] = $template->id;
		$newapp['template_version'] = $template->version;
		$newapp['can_siteuser'] = 'Y';

		$modelApp->insert('xxt_enroll', $newapp, false);

		/* 复制自定义页面 */
		if ($template->pages) {
			foreach ($template->pages as $ep) {
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
	 * 通过通讯录联系人定义创建登记活动
	 *
	 * @param string $mschema schema's id
	 *
	 */
	public function createByMschema_action($mschema, $scenario = 'registration', $template = 'simple') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oMschema = $this->model('site\user\memberschema')->byId($mschema);
		if (false === $oMschema) {
			return new \ObjectNotFoundError();
		}

		$current = time();
		$newDataSchemas = [];
		if (substr($oMschema->attr_email, 0, 1) === '0') {
			$dataSchema = new \stdClass;
			$dataSchema->id = 'member.email';
			$dataSchema->type = 'member';
			$dataSchema->title = '邮箱';
			$dataSchema->schema_id = $oMschema->id;
			$dataSchema->required = 'Y';
			$dataSchema->unique = 'Y';
			$dataSchema->_ver = '1';
			$newDataSchemas[] = $dataSchema;
		}
		if (substr($oMschema->attr_mobile, 0, 1) === '0') {
			$dataSchema = new \stdClass;
			$dataSchema->id = 'member.mobile';
			$dataSchema->type = 'member';
			$dataSchema->title = '手机';
			$dataSchema->schema_id = $oMschema->id;
			$dataSchema->required = 'Y';
			$dataSchema->unique = 'Y';
			$dataSchema->_ver = '1';
			$newDataSchemas[] = $dataSchema;
		}
		if (substr($oMschema->attr_name, 0, 1) === '0') {
			$dataSchema = new \stdClass;
			$dataSchema->id = 'member.name';
			$dataSchema->type = 'member';
			$dataSchema->title = '姓名';
			$dataSchema->schema_id = $oMschema->id;
			$dataSchema->required = 'Y';
			$dataSchema->unique = 'N';
			$dataSchema->_ver = '1';
			$newDataSchemas[] = $dataSchema;
		}
		if (!empty($oMschema->extattr)) {
			foreach ($oMschema->extattr as $extattr) {
				$dataSchema = new \stdClass;
				$dataSchema->id = 'member.extattr.' . $extattr->id;
				$dataSchema->type = 'member';
				$dataSchema->title = $extattr->label;
				$dataSchema->schema_id = $oMschema->id;
				$dataSchema->required = 'Y';
				$dataSchema->unique = 'N';
				$dataSchema->_ver = '1';
				$newDataSchemas[] = $dataSchema;
			}
		}

		if (empty($newDataSchemas)) {
			return new \ParameterError();
		}

		$modelApp = $this->model('matter\enroll');
		$modelApp->setOnlyWriteDbConn(true);

		$current = time();
		$oNewApp = new \stdClass;
		$appId = uniqid();
		$oNewApp->id = $appId;
		$oNewApp->summary = '';
		$oNewApp->use_mission_header = 'N';
		$oNewApp->use_mission_footer = 'N';
		$oNewApp->scenario = $scenario;

		/* 从站点或任务获得的信息 */
		$oSite = $this->model('site')->byId($oMschema->siteid, ['fields' => 'id,heading_pic']);
		$oNewApp->pic = $oSite->heading_pic;

		/* 获得模板定义 */
		$templateConfig = $this->_getSysTemplate($scenario, $template);
		/* 改写模板定义 */
		$templateConfig->schema = [];
		foreach ($templateConfig->pages as &$page) {
			if (in_array($page->type, ['I', 'V', 'L'])) {
				$page->data_schemas = [];
			}
		}
		foreach ($newDataSchemas as $newSchema) {
			$templateConfig->schema[] = $newSchema;
			foreach ($templateConfig->pages as &$page) {
				if ($page->type === 'I') {
					$newWrap = new \stdClass;
					$newWrap->schema = $newSchema;
					$wrapConfig = new \stdClass;
					$wrapConfig->showname = 'label';
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
		$entryRule = $templateConfig->entryRule;
		if (empty($entryRule)) {
			return new \ResponseError('没有获得页面进入规则');
		}
		$entryRule->scope = 'member';
		$entryRule->other = (object) ['entry' => '$memberschema'];
		$entryRule->member = new \stdClass;
		$entryRule->member->{$oMschema->id} = (object) ['entry' => 'enroll'];

		/* 添加页面 */
		$this->_addPageByTemplate($oUser, $oSite, null, $appId, $templateConfig, null);

		/* 登记数量限制 */
		if (isset($templateConfig->count_limit)) {
			$oNewApp->count_limit = $templateConfig->count_limit;
		}
		if (isset($templateConfig->enrolled_entry_page)) {
			$oNewApp->enrolled_entry_page = $templateConfig->enrolled_entry_page;
		}
		/* 场景设置 */
		if (isset($templateConfig->scenarioConfig)) {
			$scenarioConfig = $templateConfig->scenarioConfig;
			$oNewApp->scenario_config = json_encode($scenarioConfig);
		}

		/* create app */
		$oNewApp->siteid = $oSite->id;
		$oNewApp->title = $modelApp->escape($oMschema->title . '-登记活动');
		$oNewApp->creater = $oUser->id;
		$oNewApp->creater_src = $oUser->src;
		$oNewApp->creater_name = $modelApp->escape($oUser->name);
		$oNewApp->create_at = $current;
		$oNewApp->modifier = $oUser->id;
		$oNewApp->modifier_src = $oUser->src;
		$oNewApp->modifier_name = $modelApp->escape($oUser->name);
		$oNewApp->modify_at = $current;
		$oNewApp->entry_rule = json_encode($entryRule);
		$oNewApp->can_siteuser = 'Y';
		isset($templateConfig) && $oNewApp->data_schemas = $modelApp->toJson($templateConfig->schema);

		/* 任务码 */
		$entryUrl = $modelApp->getOpUrl($oNewApp->siteid, $oNewApp->id);
		$code = $this->model('q\url')->add($oUser, $oNewApp->siteid, $entryUrl, $oNewApp->title);
		$oNewApp->op_short_url_code = $code;

		$modelApp->insert('xxt_enroll', $oNewApp, false);

		/* 记录操作日志 */
		$oNewApp->type = 'enroll';
		$this->model('matter\log')->matterOp($oSite->id, $oUser, $oNewApp, 'C');

		return new \ResponseData($oNewApp);
	}
	/**
	 * 根据活动定义文件创建登记活动
	 *
	 * @param string $site site's id
	 * @param string $mission mission's id
	 *
	 */
	public function createByConfig_action($site, $mission = null) {
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
			$mission = null;
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
		$customConfig = isset($config->customConfig) ? $config->customConfig : null;
		!empty($config->scenario) && $newapp['scenario'] = $config->scenario;
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

		$modelApp = $this->model('matter\enroll');
		$modelApp->setOnlyWriteDbConn(true);
		$modelApp->insert('xxt_enroll', $newapp, false);
		/* 保存数据 */
		$records = $config->records;
		$this->_persist($site->id, $appId, $records);

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
	 *
	 * 复制一个登记活动
	 *
	 * @param string $site 是否要支持跨团队进行活动的复制？
	 * @param string $app
	 * @param int $mission
	 *
	 */
	public function copy_action($site, $app, $mission = null) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$current = time();
		$modelApp = $this->model('matter\enroll');
		$modelApp->setOnlyWriteDbConn(true);
		$modelCode = $this->model('code\page');

		$copied = $modelApp->byId($app);
		/**
		 * 获得的基本信息
		 */
		$newaid = uniqid();
		$oNewApp = new \stdClass;
		$oNewApp->siteid = $site;
		$oNewApp->id = $newaid;
		$oNewApp->creater = $oUser->id;
		$oNewApp->creater_src = $oUser->src;
		$oNewApp->creater_name = $modelApp->escape($oUser->name);
		$oNewApp->create_at = $current;
		$oNewApp->modifier = $oUser->id;
		$oNewApp->modifier_src = $oUser->src;
		$oNewApp->modifier_name = $modelApp->escape($oUser->name);
		$oNewApp->modify_at = $current;
		$oNewApp->title = $modelApp->escape($copied->title) . '（副本）';
		$oNewApp->pic = $copied->pic;
		$oNewApp->summary = $modelApp->escape($copied->summary);
		$oNewApp->scenario = $copied->scenario;
		$oNewApp->scenario_config = $copied->scenario_config;
		$oNewApp->count_limit = $copied->count_limit;
		$oNewApp->multi_rounds = $copied->multi_rounds;
		$oNewApp->data_schemas = $modelApp->escape($copied->data_schemas);
		$oNewApp->entry_rule = json_encode($copied->entry_rule);
		$oNewApp->extattrs = $copied->extattrs;
		$oNewApp->can_siteuser = 'Y';

		/* 所属项目 */
		if (!empty($mission)) {
			$oNewApp->mission_id = $mission;
		}
		/* 任务码 */
		$entryUrl = $modelApp->getOpUrl($oNewApp->siteid, $oNewApp->id);
		$code = $this->model('q\url')->add($oUser, $oNewApp->siteid, $entryUrl, $oNewApp->title);
		$oNewApp->op_short_url_code = $code;

		$modelApp->insert('xxt_enroll', $oNewApp, false);
		/**
		 * 复制自定义页面
		 */
		if (count($copied->pages)) {
			$modelPage = $this->model('matter\enroll\page');
			foreach ($copied->pages as $ep) {
				$oNewPage = $modelPage->add($oUser, $oNewApp->siteid, $oNewApp->id);
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
					['aid' => $oNewApp->id, 'id' => $oNewPage->id]
				);
				$data = [
					'title' => $ep->title,
					'html' => $ep->html,
					'css' => $ep->css,
					'js' => $ep->js,
				];
				$modelCode->modify($oNewPage->code_id, $data);
			}
		}

		$oNewApp->type = 'enroll';
		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($oNewApp->siteid, $oUser, $oNewApp, 'C');

		/* 记录和任务的关系 */
		if (isset($mission)) {
			$modelMis = $this->model('matter\mission');
			$modelMis->addMatter($oUser, $oNewApp->siteid, $mission, $oNewApp);
		}

		return new \ResponseData($oNewApp);
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
		$modelApp->setOnlyWriteDbConn(true);
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
			$newSchema->required = 'Y';
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
	 * 为创建活动上传的xlsx
	 */
	public function uploadExcel4Create_action($site) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		if (defined('SAE_TMP_PATH')) {
			return new \ResponseError('not support');
		}

		$modelFs = $this->model('fs/local', $site, '_resumable');
		$dest = '/enroll_' . $site . '_' . $_POST['resumableFilename'];
		$resumable = $this->model('fs/resumable', $site, $dest, $modelFs);

		$resumable->handleRequest($_POST);

		exit;
	}
	/**
	 * 通过导入的Excel数据记录创建登记活动
	 * 目前就是填空题
	 */
	public function createByExcel_action($site) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oExcelFile = $this->getPostJson();

		if (defined('SAE_TMP_PATH')) {
			return new \ResponseError('not support');
		}

		// 文件存储在本地
		$modelFs = $this->model('fs/local', $site, '_resumable');
		$fileUploaded = 'enroll_' . $site . '_' . $oExcelFile->name;
		$filename = $modelFs->rootDir . '/' . $fileUploaded;

		require_once TMS_APP_DIR . '/lib/PHPExcel.php';
		$modelApp = $this->model('matter\enroll');
		$modelApp->setOnlyWriteDbConn(true);
		$appId = uniqid();

		if (!file_exists($filename)) {
			return new \ResponseError('上传文件失败！');
		}

		$objPHPExcel = \PHPExcel_IOFactory::load($filename);
		$objWorksheet = $objPHPExcel->getActiveSheet();
		//xlsx 行号是数字
		$highestRow = $objWorksheet->getHighestRow();
		//xlsx 列的标识 eg：A,B,C,D,……,Z
		$highestColumn = $objWorksheet->getHighestColumn();
		//把最大的列换成数字
		$highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn);
		/**
		 * 提取数据定义信息
		 */
		$schemasByCol = [];
		$record = [];
		for ($col = 0; $col < $highestColumnIndex; $col++) {
			$colTitle = (string) $objWorksheet->getCellByColumnAndRow($col, 1)->getValue();
			$data = new \stdClass;
			if ($colTitle === '备注') {
				$schemasByCol[$col] = 'comment';
			} else if ($colTitle === '标签') {
				$schemasByCol[$col] = 'tags';
			} else if ($colTitle === '审核通过') {
				$schemasByCol[$col] = 'verified';
			} else if ($colTitle === '昵称') {
				$schemasByCol[$col] = false;
			} else if (preg_match("/.*时间/", $colTitle)) {
				$schemasByCol[$col] = 'submit_at';
			} else if (preg_match("/姓名.*/", $colTitle)) {
				$data->id = $this->getTopicId();
				$data->title = $colTitle;
				$data->type = 'shorttext';
				$data->required = 'Y';
				$data->format = 'name';
				$data->unique = 'N';
				$data->_ver = '1';
				$schemasByCol[$col]['id'] = $data->id;
			} else if (preg_match("/手机.*/", $colTitle)) {
				$data->id = $this->getTopicId();
				$data->title = $colTitle;
				$data->type = 'shorttext';
				$data->required = 'Y';
				$data->format = 'mobile';
				$data->unique = 'N';
				$data->_ver = '1';
				$schemasByCol[$col]['id'] = $data->id;
			} else if (preg_match("/邮箱.*/", $colTitle)) {
				$data->id = $this->getTopicId();
				$data->title = $colTitle;
				$data->type = 'shorttext';
				$data->required = 'Y';
				$data->format = 'email';
				$data->unique = 'N';
				$data->_ver = '1';
				$schemasByCol[$col]['id'] = $data->id;
			} else {
				$data->id = $this->getTopicId();
				$data->title = $colTitle;
				$data->type = 'shorttext';
				$data->required = 'Y';
				$data->format = '';
				$data->unique = 'N';
				$data->_ver = '1';
				$schemasByCol[$col]['id'] = $data->id;
			}
			if (!empty((array) $data)) {
				$record[] = $data;
			}
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
		foreach ($record as $newSchema) {
			$config->schema[] = $newSchema;
			foreach ($config->pages as &$page) {
				if ($page->type === 'I') {
					$newWrap = new \stdClass;
					$newWrap->schema = $newSchema;
					$wrapConfig = new \stdClass;
					$wrapConfig->showname = 'label';

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

		$oSite = $this->model('site')->byId($site, ['fields' => 'id,heading_pic']);

		$current = time();
		$oNewApp = new \stdClass;
		/*从站点或任务获得的信息*/
		if (empty($mission)) {
			$oNewApp->pic = $oSite->heading_pic;
			$oNewApp->summary = '';
			$oNewApp->use_mission_header = 'N';
			$oNewApp->use_mission_footer = 'N';
			$mission = null;
		} else {
			$modelMis = $this->model('matter\mission');
			$mission = $modelMis->byId($mission);
			$oNewApp->pic = $mission->pic;
			$oNewApp->summary = $mission->summary;
			$oNewApp->mission_id = $mission->id;
			$oNewApp->use_mission_header = 'Y';
			$oNewApp->use_mission_footer = 'Y';
		}
		/* 添加页面 */
		$this->_addPageByTemplate($oUser, $oSite, $mission, $appId, $config, null);
		/* 登记数量限制 */
		if (isset($config->count_limit)) {
			$oNewApp->count_limit = $config->count_limit;
		}
		if (isset($config->enrolled_entry_page)) {
			$oNewApp->enrolled_entry_page = $config->enrolled_entry_page;
		}
		/* 场景设置 */
		if (isset($config->scenarioConfig)) {
			$scenarioConfig = $config->scenarioConfig;
			$oNewApp->scenario_config = json_encode($scenarioConfig);
		}
		$oNewApp->scenario = 'common';
		/* create app */
		$title = strtok($oExcelFile->name, '.');
		$oNewApp->id = $appId;
		$oNewApp->siteid = $oSite->id;
		$oNewApp->title = $modelApp->escape($title);
		$oNewApp->creater = $oUser->id;
		$oNewApp->creater_src = $oUser->src;
		$oNewApp->creater_name = $modelApp->escape($oUser->name);
		$oNewApp->create_at = $current;
		$oNewApp->modifier = $oUser->id;
		$oNewApp->modifier_src = $oUser->src;
		$oNewApp->modifier_name = $modelApp->escape($oUser->name);
		$oNewApp->modify_at = $current;
		$oNewApp->entry_rule = json_encode($entryRule);
		$oNewApp->can_siteuser = 'Y';
		$oNewApp->data_schemas = \TMS_MODEL::toJson($record);

		$modelApp->insert('xxt_enroll', $oNewApp, false);
		$oNewApp->type = 'enroll';

		/* 存放数据 */
		$records2 = [];
		for ($row = 2; $row <= $highestRow; $row++) {
			$record2 = new \stdClass;
			$data2 = new \stdClass;
			for ($col = 0; $col < $highestColumnIndex; $col++) {
				$schema = $schemasByCol[$col];
				if ($schema === false) {
					continue;
				}
				$value = (string) $objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
				if ($schema === 'verified') {
					if (in_array($value, ['Y', '是'])) {
						$record2->verified = 'Y';
					} else {
						$record2->verified = 'N';
					}
				} else if ($schema === 'comment') {
					$record2->comment = $value;
				} else if ($schema === 'tags') {
					$record2->tags = $value;
				} else if ($schema === 'submit_at') {
					$record2->submit_at = $value;
				} else {
					$data2->{$schema['id']} = $value;
				}
			}
			$record2->data = $data2;
			$records2[] = $record2;
		}
		/* 保存数据*/
		$this->_persist($site, $appId, $records2);
		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($oSite->id, $oUser, $oNewApp, 'C');
		/* 记录和任务的关系 */
		if (isset($mission->id)) {
			$modelMis->addMatter($oUser, $oSite->id, $mission->id, $oNewApp);
		}

		// 删除上传的文件
		$modelFs->delete($fileUploaded);

		return new \ResponseData($oNewApp);
	}
	/**
	 * 保存数据
	 */
	private function _persist($site, $appId, &$records) {
		$current = time();
		$modelApp = $this->model('matter\enroll');
		$modelRec = $this->model('matter\enroll\record');
		$enrollKeys = [];

		foreach ($records as $record) {
			$ek = $modelRec->genKey($site, $appId);

			$r = array();
			$r['aid'] = $appId;
			$r['siteid'] = $site;
			$r['enroll_key'] = $ek;
			$r['enroll_at'] = $current;
			$r['verified'] = isset($record->verified) ? $record->verified : 'N';
			$r['comment'] = isset($record->comment) ? $record->comment : '';
			if (isset($record->tags)) {
				$r['tags'] = $record->tags;
				$modelApp->updateTags($appId, $record->tags);
			}
			$id = $modelRec->insert('xxt_enroll_record', $r, true);
			$r['id'] = $id;
			/**
			 * 登记数据
			 */
			if (isset($record->data)) {
				//
				$jsonData = $modelRec->toJson($record->data);
				$modelRec->update('xxt_enroll_record', ['data' => $jsonData], "enroll_key='$ek'");
				$enrollKeys[] = $ek;
				//
				foreach ($record->data as $n => $v) {
					if (is_object($v) || is_array($v)) {
						$v = json_encode($v);
					}
					if (count($v)) {
						$cd = [
							'aid' => $appId,
							'enroll_key' => $ek,
							'schema_id' => $n,
							'value' => $v,
						];
						$modelRec->insert('xxt_enroll_record_data', $cd, false);
					}
				}
			}
		}

		return $enrollKeys;
	}
	/**
	 * 创建题目的id
	 *
	 */
	protected function getTopicId() {
		list($usec, $sec) = explode(" ", microtime());
		$microtime = ((float) $usec) * 1000000;
		$id = 's' . floor($microtime);

		return $id;
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

		$posted = $this->getPostJson();
		$modelApp = $this->model('matter\enroll');
		$oMatter = $modelApp->byId($app, 'id,title,summary,pic,scenario,start_at,end_at,mission_id,mission_phase_id');

		/* 处理数据 */
		$updated = new \stdClass;
		foreach ($posted as $n => $v) {
			if (in_array($n, ['title', 'summary'])) {
				$updated->{$n} = $modelApp->escape($v);
			} else if (in_array($n, ['entry_rule', 'data_schemas'])) {
				$updated->{$n} = $modelApp->escape($modelApp->toJson($v));
			} else if ($n === 'scenarioConfig') {
				$updated->scenario_config = $modelApp->escape($modelApp->toJson($v));
			} else if ($n === 'roundCron') {
				$updated->round_cron = $modelApp->escape($modelApp->toJson($v));
			} else if ($n === 'rpConfig') {
				$updated->rp_config = $modelApp->escape($modelApp->toJson($v));
			} else {
				$updated->{$n} = $v;
			}
			$oMatter->{$n} = $v;
		}

		$updated->modifier = $user->id;
		$updated->modifier_src = $user->src;
		$updated->modifier_name = $modelApp->escape($user->name);
		$updated->modify_at = time();

		$rst = $modelApp->update('xxt_enroll', $updated, ["id" => $app]);
		if ($rst) {
			// 更新项目中的素材信息
			if ($oMatter->mission_id) {
				$this->model('matter\mission')->updateMatter($oMatter->mission_id, $oMatter);
			}
			// 记录操作日志并更新信息
			$this->model('matter\log')->matterOp($site, $user, $oMatter, 'U');
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
	private function &_addPageByTemplate(&$user, &$site, $oMission, &$app, &$config, $customConfig) {
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
			if (!empty($oMission) && $oMission->multi_phase === 'Y') {
				$schemaPhase = new \stdClass;
				$schemaPhase->id = 'phase';
				$schemaPhase->title = '项目阶段';
				$schemaPhase->type = 'phase';
				$schemaPhase->ops = [];
				$phases = $this->model('matter\mission\phase')->byMission($oMission->id);
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
				$code = (array) $page->code;
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

		$modelEnroll = \TMS_APP::M('matter\enroll');
		$oApp = $modelEnroll->byId($app);
		$template = new \stdClass;
		/* setting */
		!empty($oApp->scenario) && $template->scenario = $oApp->scenario;
		$template->count_limit = $oApp->count_limit;

		/* schema */
		$template->schema = json_decode($oApp->data_schemas);

		/* pages */
		$pages = $oApp->pages;
		foreach ($pages as &$rec) {
			$rec->data_schemas = json_decode($rec->data_schemas);
			$rec->act_schemas = json_decode($rec->act_schemas);
			$code = new \stdClass;
			$code->css = $rec->css;
			$code->js = $rec->js;
			$code->html = $rec->html;
			$rec->code = $code;
		}
		$template->pages = $pages;

		/* entry_rule */
		$template->entryRule = $oApp->entry_rule;

		/* records */
		$records = $modelEnroll->query_objs_ss([
			'id,userid,openid,nickname,data',
			'xxt_enroll_record',
			['siteid' => $site, 'aid' => $app],
		]);

		foreach ($records as &$rec) {
			$rec->data = json_decode($rec->data);
		}
		$template->records = $records;

		$template = \TMS_MODEL::toJson($template);
		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		header('Content-disposition: attachment; filename=' . $oApp->title . '.json');
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
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		$summary = $modelApp->opData($oApp);

		return new \ResponseData($summary);
	}
}