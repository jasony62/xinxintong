<?php
namespace pl\fe\matter\signin;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 签到活动主控制器
 */
class main extends \pl\fe\matter\base {
	/**
	 *
	 */
	protected function getMatterType() {
		return 'signin';
	}
	/**
	 * 返回视图
	 */
	public function index_action($site, $id) {
		\TPL::output('/pl/fe/matter/signin/frame');
		exit;
	}
	/**
	 * 返回一个签到活动
	 */
	public function get_action($site, $id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oApp = $this->model('matter\signin')->byId($id);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		/*关联登记活动*/
		if ($oApp->enroll_app_id) {
			$oApp->enrollApp = $this->model('matter\enroll')->byId($oApp->enroll_app_id, ['cascaded' => 'N']);
		}
		/*关联分组活动*/
		if ($oApp->group_app_id) {
			$oApp->groupApp = $this->model('matter\group')->byId($oApp->group_app_id);
		}
		/*所属项目*/
		if ($oApp->mission_id) {
			$oApp->mission = $this->model('matter\mission')->byId($oApp->mission_id, ['cascaded' => 'phase']);
		}

		return new \ResponseData($oApp);
	}
	/**
	 * 返回签到活动列表
	 * @param string $onlySns 是否仅查询进入规则为仅限关注用户访问的活动列表
	 *
	 */
	public function list_action($site = null, $mission = null, $page = null, $size = null, $cascaded = '', $onlySns = 'N') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$post = $this->getPostJson();
		$model = $this->model('matter\signin');
		if (empty($mission)) {
			$site = $model->escape($site);
			$options = array();
			if (!empty($post->byTitle)) {
				$options['byTitle'] = $post->byTitle;
			}
			if (!empty($post->byTags)) {
				$options['byTags'] = $post->byTags;
			}
			$result = $model->bySite($site, $page, $size, $onlySns, $options);
		} else {
			$options = [];
			//按项目阶段筛选
			if (isset($post->mission_phase_id) && !empty($post->mission_phase_id) && $post->mission_phase_id !== "ALL") {
				$options['where']['mission_phase_id'] = $post->mission_phase_id;
			}
			if (!empty($post->byTitle)) {
				$options['byTitle'] = $post->byTitle;
			}
			$result = $model->byMission($mission, $options, $page, $size);
		}

		if (strlen($cascaded) && count($result->apps)) {
			$cascaded = explode(',', $cascaded);
			$modelRnd = $this->model('matter\signin\round');
			foreach ($result->apps as &$oApp) {
				if (in_array('round', $cascaded)) {
					/* 轮次 */
					$oApp->rounds = $modelRnd->byApp($oApp->id, ['fields' => 'id,rid,title,start_at,end_at,late_at']);
				}
				if (in_array('opData', $cascaded)) {
					$oApp->opData = $model->opData($oApp, true);
				}
			}
		}

		return new \ResponseData($result);
	}
	/**
	 * 返回和指定登记活动关联的签到活动列表
	 *
	 */
	public function listByEnroll_action($site, $enroll) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model('matter\signin');
		$result = $model->byEnrollApp($enroll);

		return new \ResponseData($result);
	}
	/**
	 * 创建一个空的签到活动
	 *
	 * @param string $site site's id
	 * @param string $mission mission's id
	 *
	 */
	public function create_action($site, $mission = null, $template = 'basic') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		/* 模板信息 */
		$templateDir = TMS_APP_TEMPLATE . '/pl/fe/matter/signin/' . $template;
		$templateConfig = file_get_contents($templateDir . '/config.json');
		$templateConfig = preg_replace('/\t|\r|\n/', '', $templateConfig);
		$templateConfig = json_decode($templateConfig);
		if (JSON_ERROR_NONE !== json_last_error()) {
			return new \ResponseError('解析模板数据错误：' . json_last_error_msg());
		}

		$modelApp = $this->model('matter\signin');
		$modelApp->setOnlyWriteDbConn(true);
		$oNewApp = new \stdClass;
		$current = time();
		$appId = uniqid();

		/* 从站点和项目中获得pic定义 */
		$oSite = $this->model('site')->byId($site, ['fields' => 'id,heading_pic']);
		if (!empty($mission)) {
			$modelMis = $this->model('matter\mission');
			$oMission = $modelMis->byId($mission);
			$oNewApp->summary = $oMission->summary;
			$oNewApp->pic = $oMission->pic;
			$oNewApp->mission_id = $oMission->id;
			$oNewApp->use_mission_header = 'Y';
			$oNewApp->use_mission_footer = 'Y';
			$oMisEntryRule = $oMission->entry_rule;
		} else {
			$oNewApp->summary = '';
			$oNewApp->pic = $oSite->heading_pic;
			$oNewApp->use_mission_header = 'N';
			$oNewApp->use_mission_footer = 'N';
		}
		/* 用户指定的属性 */
		$customConfig = $this->getPostJson();
		$title = empty($customConfig->proto->title) ? '新签到活动' : $modelApp->escape($customConfig->proto->title);
		/* 登记数据 */
		if (!empty($templateConfig->schema)) {
			$oNewApp->data_schemas = $modelApp->toJson($templateConfig->schema);
		}
		/* 进入规则 */
		if (empty($templateConfig->entryRule)) {
			return new \ResponseError('没有获得页面进入规则');
		}
		$oEntryRule = $templateConfig->entryRule;
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
		/*create app*/
		$oNewApp->siteid = $oSite->id;
		$oNewApp->id = $appId;
		$oNewApp->title = $title;
		$oNewApp->creater = $oUser->id;
		$oNewApp->creater_src = $oUser->src;
		$oNewApp->creater_name = $modelApp->escape($oUser->name);
		$oNewApp->create_at = $current;
		$oNewApp->modifier = $oUser->id;
		$oNewApp->modifier_src = $oUser->src;
		$oNewApp->modifier_name = $modelApp->escape($oUser->name);
		$oNewApp->modify_at = $current;
		$oNewApp->entry_rule = $modelApp->toJson($oEntryRule);

		/*任务码*/
		$entryUrl = $modelApp->getOpUrl($oSite->id, $appId);
		$code = $this->model('q\url')->add($oUser, $oSite->id, $entryUrl, $oNewApp->title);
		$oNewApp->op_short_url_code = $code;

		$modelApp->insert('xxt_signin', $oNewApp, false);
		$oNewApp->type = 'signin';

		/* 记录和任务的关系 */
		if (isset($oNewApp->mission_id)) {
			$modelMis->addMatter($oUser, $oSite->id, $oMission->id, $oNewApp);
		}
		/* 创建缺省页面 */
		$this->_addPageByTemplate($oUser, $oSite->id, $oNewApp, $templateConfig);
		/* 创建缺省轮次 */
		$this->_addFirstRound($oUser, $oSite->id, $oNewApp);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($oSite->id, $oUser, $oNewApp, 'C');

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

		$current = time();
		$modelApp = $this->model('matter\signin');
		$modelApp->setOnlyWriteDbConn(true);
		$modelCode = $this->model('code\page');

		$oCopied = $modelApp->byId($app);
		/**
		 * 获得的基本信息
		 */
		$newaid = uniqid();
		$oNewApp = [];
		$oNewApp['siteid'] = $site;
		$oNewApp['id'] = $newaid;
		$oNewApp['creater'] = $oUser->id;
		$oNewApp['creater_src'] = $oUser->src;
		$oNewApp['creater_name'] = $modelApp->escape($oUser->name);
		$oNewApp['create_at'] = $current;
		$oNewApp['modifier'] = $oUser->id;
		$oNewApp['modifier_src'] = $oUser->src;
		$oNewApp['modifier_name'] = $modelApp->escape($oUser->name);
		$oNewApp['modify_at'] = $current;
		$oNewApp['title'] = $modelApp->escape($oCopied->title) . '（副本）';
		$oNewApp['pic'] = $oCopied->pic;
		$oNewApp['summary'] = $modelApp->escape($oCopied->summary);
		$oNewApp['data_schemas'] = $modelApp->escape($oCopied->data_schemas);
		$oNewApp['entry_rule'] = json_encode($oCopied->entry_rule);
		if (!empty($mission)) {
			$oNewApp['mission_id'] = $mission;
		}

		$modelApp->insert('xxt_signin', $oNewApp, false);
		/**
		 * 复制自定义页面
		 */
		if (count($oCopied->pages)) {
			$modelPage = $this->model('matter\signin\page');
			foreach ($oCopied->pages as $ep) {
				$newPage = $modelPage->add($oUser, $site, $newaid);
				$rst = $modelPage->update(
					'xxt_signin_page',
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
		$this->model('matter\log')->matterOp($site, $oUser, $app, 'C', (object) ['id' => $oCopied->id, 'title' => $oCopied->title]);

		/* 记录和任务的关系 */
		if (isset($mission)) {
			$modelMis = $this->model('matter\mission');
			$modelMis->addMatter($oUser, $site, $mission, $app);
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
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\signin');
		$oMatter = $modelApp->byId($app, ['fields' => 'id,title,summary,pic,start_at,end_at,mission_id,mission_phase_id', 'cascaded' => 'N']);
		/**
		 * 处理数据
		 */
		$updated = $this->getPostJson();
		foreach ($updated as $n => $v) {
			if (in_array($n, ['entry_rule', 'data_schemas'])) {
				$updated->{$n} = $modelApp->escape($modelApp->toJson($v));
			} else if (in_array($n, ['title', 'summary'])) {
				$updated->{$n} = $modelApp->escape($v);
			}
			$oMatter->{$n} = $v;
		}

		$updated->modifier = $oUser->id;
		$updated->modifier_src = $oUser->src;
		$updated->modifier_name = $oUser->name;
		$updated->modify_at = time();

		if ($rst = $modelApp->update('xxt_signin', $updated, ["id" => $app])) {
			// 更新项目中的素材信息
			if ($oMatter->mission_id) {
				$this->model('matter\mission')->updateMatter($oMatter->mission_id, $oMatter);
			}
			// 记录操作日志并更新信息
			$this->model('matter\log')->matterOp($site, $oUser, $oMatter, 'U');
		}

		return new \ResponseData($rst);
	}
	/**
	 * 根据模板生成页面
	 *
	 * @param string $app
	 * @param string $scenario scenario's name
	 * @param string $template template's name
	 */
	private function &_addPageByTemplate(&$oUser, $siteId, &$app, &$templateConfig) {
		$pages = $templateConfig->pages;
		if (empty($pages)) {
			return false;
		}
		/* 创建页面 */
		$templateDir = TMS_APP_TEMPLATE . $templateConfig->path;
		$modelPage = $this->model('matter\signin\page');
		$modelCode = $this->model('code\page');
		foreach ($pages as $page) {
			$ap = $modelPage->add($oUser, $siteId, $app->id, $page);
			$data = [
				'html' => file_get_contents($templateDir . '/' . $page->name . '.html'),
				'css' => file_get_contents($templateDir . '/' . $page->name . '.css'),
				'js' => file_get_contents($templateDir . '/' . $page->name . '.js'),
			];
			$modelCode->modify($ap->code_id, $data);
			/*页面关联的定义*/
			$pageSchemas = [];
			$pageSchemas['data_schemas'] = isset($page->data_schemas) ? \TMS_MODEL::toJson($page->data_schemas) : '[]';
			$pageSchemas['act_schemas'] = isset($page->act_schemas) ? \TMS_MODEL::toJson($page->act_schemas) : '[]';
			$rst = $modelPage->update(
				'xxt_signin_page',
				$pageSchemas,
				"aid='{$app->id}' and id={$ap->id}"
			);
		}

		return $pages;
	}
	/**
	 * 添加第一个轮次
	 *
	 * @param string $app
	 */
	private function &_addFirstRound(&$oUser, $siteId, &$app) {
		$modelRnd = $this->model('matter\signin\round');

		$roundId = uniqid();
		$round = [
			'siteid' => $siteId,
			'aid' => $app->id,
			'rid' => $roundId,
			'creater' => $oUser->id,
			'create_at' => time(),
			'title' => '第1轮',
			'state' => 1,
		];

		$modelRnd->insert('xxt_signin_round', $round, false);

		$round = (object) $round;

		return $round;
	}
	/**
	 * 应用的微信二维码
	 *
	 * @param string $site
	 * @param string $app
	 * @param string $round
	 *
	 */
	public function wxQrcode_action($site, $app, $round = null) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelQrcode = $this->model('sns\wx\call\qrcode');

		if (empty($round)) {
			$qrcodes = $modelQrcode->byMatter('signin', $app);
		} else {
			$params = new \stdClass;
			$params->round = $round;
			$params = \TMS_MODEL::toJson($params);
			$qrcodes = $modelQrcode->byMatter('signin', $app, $params);
		}

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
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelQrcode = $this->model('sns\yx\call\qrcode');

		$qrcode = $modelQrcode->byMatter('signin', $app);

		return new \ResponseData($qrcode);
	}
	/**
	 * 重置活动进入规则
	 *
	 * @param string $app
	 *
	 */
	public function entryRuleReset_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();
		/*缺省进入规则*/
		$entryRule = $this->_defaultEntryRule($site, $app);
		// 更新数据
		$nv['entry_rule'] = $model->toJson($entryRule);
		$nv['modifier'] = $oUser->id;
		$nv['modifier_src'] = $oUser->src;
		$nv['modifier_name'] = $oUser->name;
		$nv['modify_at'] = time();

		$rst = $model->update('xxt_signin', $nv, "id='$app'");

		//记录操作日志
		if ($rst) {
			$matter = $this->model('matter\signin')->byId($app, 'id,title,summary,pic');
			$this->model('matter\log')->matterOp($site, $oUser, $matter, 'U');
		}

		return new \ResponseData($entryRule);
	}
	/**
	 * 缺省进入规则
	 */
	private function &_defaultEntryRule($site, $appid) {
		// 设置规则
		$entryRule = new \stdClass;
		$entryRule->scope = 'none';
		$entryRule->otherwise = new \stdClass;
		$entryRule->otherwise->entry = '';
		$entryRule->success = new \stdClass;
		$entryRule->success->entry = '';
		$entryRule->fail = new \stdClass;
		$entryRule->fail->entry = '';

		// 设置页面
		$cnt = 0;
		$modelPage = $this->model('matter\signin\page');
		$pages = $modelPage->byApp($appid, ['cascaded' => 'N']);
		foreach ($pages as $page) {
			if (empty($entryRule->otherwise->entry) && $page->type === 'I') {
				$entryRule->otherwise->entry = $page->name;
				$cnt++;
			} else if (empty($entryRule->success->entry) && $page->name === 'success') {
				$entryRule->success->entry = $page->name;
				$cnt++;
			} else if (empty($entryRule->fail->entry) && $page->name === 'failure') {
				$entryRule->fail->entry = $page->name;
				$cnt++;
			}
			if ($cnt === 3) {
				break;
			}
		}

		return $entryRule;
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
		/*在删除数据前获得数据*/
		$modelSig = $this->model('matter\signin');
		$app = $modelSig->byId($app, ['fields' => 'id,title,summary,pic,mission_id,creater', 'cascaded' => 'N']);
		if ($app->creater !== $oUser->id) {
			return new \ResponseError('没有删除数据的权限');
		}
		/*删除和任务的关联*/
		if ($app->mission_id) {
			$this->model('matter\mission')->removeMatter($app->id, 'signin');
		}
		/*check*/
		$q = [
			'count(*)',
			'xxt_signin_record',
			["aid" => $app->id],
		];
		if ((int) $modelSig->query_val_ss($q) > 0) {
			$rst = $modelSig->update(
				'xxt_signin',
				['state' => 0],
				["id" => $app->id]
			);
			/*记录操作日志*/
			$this->model('matter\log')->matterOp($site, $oUser, $app, 'Recycle');
		} else {
			$modelSig->delete(
				'xxt_signin_log',
				["aid" => $app->id]
			);
			$modelSig->delete(
				'xxt_signin_round',
				["aid" => $app->id]
			);
			$modelSig->delete(
				'xxt_code_page',
				"id in (select code_id from xxt_signin_page where aid='" . $modelSig->escape($app->id) . "')"
			);
			$modelSig->delete(
				'xxt_signin_page',
				["aid" => $app->id]
			);
			$rst = $modelSig->delete(
				'xxt_signin',
				["id" => $app->id]
			);
			/*记录操作日志*/
			$this->model('matter\log')->matterOp($site, $oUser, $app, 'D');
		}

		return new \ResponseData($rst);
	}
	/**
	 * 恢复被删除的分组活动
	 */
	public function restore_action($site, $id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model('matter\signin');
		if (false === ($app = $model->byId($id, 'id,title,summary,pic,mission_id'))) {
			return new \ResponseError('数据已经被彻底删除，无法恢复');
		}
		if ($app->mission_id) {
			$modelMis = $this->model('matter\mission');
			$modelMis->addMatter($oUser, $site, $app->mission_id, $app);
		}

		/* 恢复数据 */
		$rst = $model->update(
			'xxt_signin',
			['state' => 1],
			["id" => $app->id]
		);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($site, $oUser, $app, 'Restore');

		return new \ResponseData($rst);
	}
	/**
	 * 登记情况汇总信息
	 */
	public function opData_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$mdoelApp = $this->model('matter\signin');
		$oApp = new \stdClass;
		$oApp->siteid = $site;
		$oApp->id = $app;
		$opData = $mdoelApp->opData($oApp);

		return new \ResponseData($opData);
	}
}