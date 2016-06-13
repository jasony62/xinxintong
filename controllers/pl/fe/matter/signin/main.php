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
		$app = $this->model('matter\signin')->byId($id);
		if ($app->state === '2') {
			$this->redirect('/rest/pl/fe/matter/signin/publish?site=' . $site . '&id=' . $id);
		} else {
			\TPL::output('/pl/fe/matter/signin/frame');
			exit;
		}
	}
	/**
	 * 返回视图
	 */
	public function app_action() {
		\TPL::output('/pl/fe/matter/signin/frame');
		exit;
	}
	/**
	 * 返回视图
	 */
	public function publish_action() {
		\TPL::output('/pl/fe/matter/signin/frame');
		exit;
	}
	/**
	 * 返回视图
	 */
	public function event_action() {
		\TPL::output('/pl/fe/matter/signin/frame');
		exit;
	}
	/**
	 * 返回一个签到活动
	 */
	public function get_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$app = $this->model('matter\signin')->byId($id);
		/*关联签到活动*/
		if ($app->enroll_app_id) {
			$app->enrollApp = $this->model('matter\enroll')->byId($app->enroll_app_id);
		}
		/*所属项目*/
		if ($app->mission_id) {
			$app->mission = $this->model('matter\mission')->byId($app->mission_id, ['cascaded' => 'phase']);
		}

		return new \ResponseData($app);
	}
	/**
	 * 返回签到活动列表
	 *
	 */
	public function list_action($site, $page = 1, $size = 30, $mission = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$model = $this->model('matter\signin');
		$result = $model->bySite($site, $page, $size, $mission);

		return new \ResponseData($result);
	}
	/**
	 * 创建一个空的签到活动
	 *
	 * @param string $site site's id
	 * @param string $mission mission's id
	 * @param string $enrollApp 关联的签到活动
	 *
	 */
	public function create_action($site, $mission = null, $enrollApp = null, $template = 'basic') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$newapp = [];
		$current = time();
		$appId = uniqid();
		/*从关联的签到活动中获取登记项定义*/
		if (!empty($enrollApp)) {
			$enrollApp = $this->model('matter\enroll')->byId(
				$enrollApp,
				['fields' => 'data_schemas', 'cascaded' => 'N']
			);
			$newapp['enroll_app_id'] = $enrollApp->id;
		}
		/*从站点和项目中获得pic定义*/
		$site = $this->model('site')->byId($site, ['fields' => 'id,heading_pic']);
		if (!empty($mission)) {
			$modelMis = $this->model('mission');
			$mission = $modelMis->byId($mission);
			$newapp['summary'] = $mission->summary;
			$newapp['pic'] = $mission->pic;
			$newapp['mission_id'] = $mission->id;
			$newapp['use_mission_header'] = 'Y';
			$newapp['use_mission_footer'] = 'Y';
		} else {
			$newapp['summary'] = '';
			$newapp['pic'] = $site->heading_pic;
			$newapp['use_mission_header'] = 'N';
			$newapp['use_mission_footer'] = 'N';
		}
		/*用户指定的*/
		$customConfig = $this->getPostJson();
		$title = empty($customConfig->proto->title) ? '新签到活动' : $customConfig->proto->title;
		/*模版信息*/
		$templateDir = TMS_APP_TEMPLATE . '/pl/fe/matter/signin/' . $template;
		$templateConfig = file_get_contents($templateDir . '/config.json');
		$templateConfig = preg_replace('/\t|\r|\n/', '', $templateConfig);
		$templateConfig = json_decode($templateConfig);
		if (JSON_ERROR_NONE !== json_last_error()) {
			return new \ResponseError('解析模版数据错误：' . json_last_error_msg());
		}
		/*登记数据*/{
			if (!empty($templateConfig->schema)) {
				$newapp['data_schemas'] = \TMS_MODEL::toJson($templateConfig->schema);
			}
		}
		/*进入规则*/
		if (isset($templateConfig->entryRule)) {
			$newapp['entry_rule'] = \TMS_MODEL::toJson($templateConfig->entryRule);
		}
		/*create app*/
		$newapp['siteid'] = $site->id;
		$newapp['id'] = $appId;
		$newapp['title'] = $title;
		$newapp['creater'] = $user->id;
		$newapp['creater_src'] = $user->src;
		$newapp['creater_name'] = $user->name;
		$newapp['create_at'] = $current;
		$newapp['modifier'] = $user->id;
		$newapp['modifier_src'] = $user->src;
		$newapp['modifier_name'] = $user->name;
		$newapp['modify_at'] = $current;
		$this->model()->insert('xxt_signin', $newapp, false);
		$app = $this->model('matter\signin')->byId($appId, ['cascaded' => 'N']);

		/*记录操作日志*/
		$app->type = 'signin';
		$this->model('log')->matterOp($site->id, $user, $app, 'C');

		/*记录和任务的关系*/
		if ($app->mission_id) {
			$modelMis->addMatter($user, $site->id, $mission->id, $app);
		}
		/*创建缺省页面*/
		$this->_addPageByTemplate($user, $site->id, $app, $templateConfig);
		/*创建缺省轮次*/
		$this->_addFirstRound($user, $site->id, $app);

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
			if (in_array($n, ['data_schemas', 'entry_rule'])) {
				$nv[$n] = $model->toJson($v);
			}
		}
		$nv['modifier'] = $user->id;
		$nv['modifier_src'] = $user->src;
		$nv['modifier_name'] = $user->name;
		$nv['modify_at'] = time();

		if ($rst = $model->update('xxt_signin', $nv, "id='$app'")) {
			/*记录操作日志*/
			$matter = $this->model('matter\\signin')->byId($app, ['fields' => 'id,title,summary,pic', 'cascaded' => 'N']);
			$matter->type = 'signin';
			$this->model('log')->matterOp($site, $user, $matter, 'U');
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
	private function &_addPageByTemplate(&$user, $siteId, &$app, &$templateConfig) {
		$pages = $templateConfig->pages;
		if (empty($pages)) {
			return false;
		}
		/* 创建页面 */
		$templateDir = TMS_APP_TEMPLATE . $templateConfig->path;
		$modelPage = $this->model('matter\signin\page');
		$modelCode = $this->model('code\page');
		foreach ($pages as $page) {
			$ap = $modelPage->add($user, $siteId, $app->id, $page);
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
	private function &_addFirstRound(&$user, $siteId, &$app) {
		$modelRnd = $this->model('matter\signin\round');

		$roundId = uniqid();
		$round = [
			'siteid' => $siteId,
			'aid' => $app->id,
			'rid' => $roundId,
			'creater' => $user->id,
			'create_at' => time(),
			'title' => '第1轮',
			'state' => 1,
		];

		$modelRnd->insert('xxt_signin_round', $round, false);

		$round = (object) $round;

		return $round;
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
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		/*在删除数据前获得数据*/
		$app = $this->model('matter\signin')->byId($app, ['fields' => 'id,title,summary,pic,mission_id', 'cascaded' => 'N']);
		/*删除和任务的关联*/
		if ($app->mission_id) {
			$this->model('mission')->removeMatter($site, $app->id, 'signin');
		}
		/*check*/
		$q = [
			'count(*)',
			'xxt_signin_record',
			"siteid='$site' and aid='$app->id'",
		];
		$model = $this->model();
		if ((int) $model->query_val_ss($q) > 0) {
			$rst = $model->update(
				'xxt_signin',
				['state' => 0],
				"siteid='$site' and id='$app->id'"
			);
		} else {
			$model->delete(
				'xxt_signin_log',
				"siteid='$site' and aid='$app->id'"
			);
			$model->delete(
				'xxt_signin_round',
				"siteid='$site' and aid='$app->id'"
			);
			$model->delete(
				'xxt_code_page',
				"id in (select code_id from xxt_signin_page where aid='$app->id')"
			);
			$model->delete(
				'xxt_signin_page',
				"siteid='$site' and aid='$app->id'"
			);
			$rst = $model->delete(
				'xxt_signin',
				"siteid='$site' and id='$app->id'"
			);
		}
		/*记录操作日志*/
		$app->type = 'signin';
		$this->model('log')->matterOp($site, $user, $app, 'D');

		return new \ResponseData($rst);
	}
	/**
	 * 版本升级
	 */
	public function verUpgrade_Action($site) {
		$result = [];
		/*app's data_schema*/
		$model = $this->model('matter\signin');
		$apps = $model->bySite($site, 1, 999);
		$apps = $apps['apps'];
		foreach ($apps as $app) {
			/*app*/
			if (!empty($app->data_schemas)) {
				$dataSchemas = json_decode($app->data_schemas);
				$newDataSchemas = [];
				foreach ($dataSchemas as $dataSchema) {
					$schema = new \stdClass;
					$schema->id = $dataSchema->id;
					isset($dataSchema->type) && $schema->type = $dataSchema->type;
					isset($dataSchema->title) && $schema->title = $dataSchema->title;
					isset($dataSchema->ops) && $schema->ops = $dataSchema->ops;

					$newDataSchemas[] = $schema;
				}
				$result[$app->id] = $newDataSchemas;
				/*update*/
				$model->update(
					'xxt_signin',
					['data_schemas' => $model->toJson($newDataSchemas)],
					"id='$app->id'"
				);
			}
			/*page*/
			$pages = $this->model('matter\signin\page')->byApp($app->id);
			foreach ($pages as $page) {
				if (!empty($page->data_schemas)) {
					if ($page->type === 'S') {
						/**
						 * data schemas
						 */
						$dataSchemas = json_decode($page->data_schemas);
						if (!isset($dataSchemas[0]->schema)) {
							$newDataSchemas = [];
							foreach ($dataSchemas as $dataSchema) {
								$config = new \stdClass;
								isset($dataSchema->showname) && $config->showname = $dataSchema->showname;
								isset($dataSchema->required) && $config->required = $dataSchema->required;
								isset($dataSchema->component) && $config->component = $dataSchema->component;
								isset($dataSchema->align) && $config->align = $dataSchema->align;

								$schema = new \stdClass;
								$schema->id = $dataSchema->id;
								isset($dataSchema->type) && $schema->type = $dataSchema->type;
								$schema->title = $dataSchema->title;
								isset($dataSchema->ops) && $schema->ops = $dataSchema->ops;

								$wrap = new \stdClass;
								$wrap->config = $config;
								$wrap->schema = $schema;

								$newDataSchemas[] = $wrap;
							}
							$result[$app->id . '.' . $page->name] = $newDataSchemas;
							/*update*/
							$model->update(
								'xxt_signin_page',
								['data_schemas' => $model->toJson($newDataSchemas)],
								"id='$page->id'"
							);
						}
						/**
						 * html
						 */
						if (!empty($page->html) && false === strpos($page->html, 'schema-type=')) {
							$newHtml = $this->_upgradeHtml($page->html);
							$model->update(
								'xxt_code_page',
								['html' => $model->escape($newHtml)],
								"siteid='$site' and name='$page->code_name'"
							);
						}
					}
				}
			}
		}

		return new \ResponseData($result);
	}
	/**
	 *
	 */
	public function _upgradeHtml($html) {
		$schemas = [];

		if (preg_match_all('/<div.+?wrap="input".+?>.*?<\/div>/i', $html, $wraps)) {
			$wraps = $wraps[0];
			foreach ($wraps as $wrap) {
				$schema = [];
				$inp = [];
				$title = [];
				$ngmodel = [];
				$opval = [];
				$optit = [];
				if (!preg_match('/<input.+?>/', $wrap, $inp) && !preg_match('/<option.+?>/', $wrap, $inp) && !preg_match('/<textarea.+?>/', $wrap, $inp) && !preg_match('/wrap="datetime".+?>/', $wrap, $inp) && !preg_match('/wrap="img".+?>/', $wrap, $inp) && !preg_match('/wrap="file".+?>/', $wrap, $inp)) {
					continue;
				}
				$inp = $inp[0];
				if (preg_match('/title="(.*?)"/', $inp, $title)) {
					$title = $title[1];
				}
				if (preg_match('/type="radio"/', $inp)) {
					/**
					 * for radio group.
					 */
					if (preg_match('/ng-model="data\.(.+?)"/', $inp, $ngmodel)) {
						$id = $ngmodel[1];
					}
					if (empty($id)) {
						continue;
					}
					$existing = false;
					foreach ($schemas as &$d) {
						if ($existing = ($d['id'] === $id)) {
							break;
						}
					}
					if (!$existing) {
						$schema = ['title' => $title, 'id' => $id, 'type' => 'single', 'ops' => []];
						$schemas[] = $schema;
						$d = &$schemas[count($schemas) - 1];
					}
					$op = [];
					if (preg_match('/value="(.+?)"/', $inp, $opval)) {
						$op['v'] = $opval[1];
					}
					if (preg_match_all('/data-(.+?)="(.+?)"/', $wrap, $opAttrs)) {
						for ($i = 0, $l = count($opAttrs[0]); $i < $l; $i++) {
							$op[$opAttrs[1][$i]] = $opAttrs[2][$i];
						}
					}
					$d['ops'][] = $op;
				} elseif (preg_match('/<option/', $inp)) {
					/**
					 * for radio group.
					 */
					if (preg_match('/name="data\.(.+?)"/', $inp, $ngmodel)) {
						$id = $ngmodel[1];
					}
					if (empty($id)) {
						continue;
					}
					$existing = false;
					foreach ($schemas as &$d) {
						if ($existing = ($d['id'] === $id)) {
							break;
						}
					}
					if (!$existing) {
						$schema = ['title' => $title, 'id' => $id, 'type' => 'single', 'ops' => []];
						$schemas[] = $schema;
						$d = &$schemas[count($schemas) - 1];
					}
					$op = [];
					if (preg_match('/value="(.+?)"/', $inp, $opval)) {
						$op['v'] = $opval[1];
					}
					if (preg_match_all('/data-(.+?)="(.+?)"/', $wrap, $opAttrs)) {
						for ($i = 0, $l = count($opAttrs[0]); $i < $l; $i++) {
							$op[$opAttrs[1][$i]] = $opAttrs[2][$i];
						}
					}
					$d['ops'][] = $op;
				} elseif (preg_match('/type="checkbox"/', $inp)) {
					if (preg_match('/ng-model="data\.(.+?)\.(.+?)"/', $inp, $ngmodel)) {
						$id = $ngmodel[1];
						$opval = $ngmodel[2];
					}
					if (empty($id) || !isset($opval)) {
						continue;
					}
					$existing = false;
					foreach ($schemas as &$d) {
						if ($existing = ($d['id'] === $id)) {
							break;
						}
					}
					if (!$existing) {
						$schema = ['title' => $title, 'id' => $id, 'type' => 'multiple', 'ops' => []];
						$schemas[] = $schema;
						$d = &$schemas[count($schemas) - 1];
					}
					$op = [];
					$op['v'] = $opval;
					if (preg_match_all('/data-(.+?)="(.+?)"/', $wrap, $opAttrs)) {
						for ($i = 0, $l = count($opAttrs[0]); $i < $l; $i++) {
							$op[$opAttrs[1][$i]] = $opAttrs[2][$i];
						}
					}
					$d['ops'][] = $op;
				} elseif (preg_match('/ng-repeat="img in data\.(.+?)"/', $inp, $ngrepeat)) {
					$id = $ngrepeat[1];
					$schema = ['title' => $title, 'id' => $id, 'type' => 'img'];
					$schemas[] = $schema;
				} elseif (preg_match('/ng-repeat="file in data\.(.+?)"/', $inp, $ngrepeat)) {
					$id = $ngrepeat[1];
					$schema = ['title' => $title, 'id' => $id, 'type' => 'file'];
					$schemas[] = $schema;
				} elseif (preg_match('/ng-bind="data\.(.+?)\|/', $inp, $ngmodel)) {
					$id = $ngmodel[1];
					$schema = ['title' => $title, 'id' => $id, 'type' => 'datetime'];
					$schemas[] = $schema;
				} else {
					/**
					 * for text input/textarea/location.
					 */
					if (preg_match('/ng-model="data\.(.+?)"/', $inp, $ngmodel)) {
						$id = $ngmodel[1];
					}
					if (empty($id)) {
						continue;
					}
					if (in_array($id, ['name', 'email', 'mobile'])) {
						$type = $id;
					} elseif ($id === 'mobile') {
						$type = 'mobile';
					} else {
						if (preg_match('/<textarea.+?>/', $wrap)) {
							$type = 'longtext';
						} else {
							$type = 'shorttext';
						}
					}
					$schema = ['title' => $title, 'id' => $id, 'type' => $type];
					$schemas[] = $schema;
				}
			}
		}
		/*update html*/
		$i = $offset = 0;
		while ($offset = strpos($html, 'wrap="input"', $offset)) {
			if (!isset($schemas[$i])) {
				break;
			}

			$schema = $schemas[$i];
			$newAttrs = 'wrap="input" schema="' . $schema['id'] . '" schema-type="' . $schema['type'] . '"';
			$html = substr_replace($html, $newAttrs, $offset, 12);

			$offset++;
			$i++;
		}

		return $html;
	}
}