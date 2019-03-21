<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/main_base.php';
/**
 * 记录活动
 */
class main extends main_base {
	/**
	 * 返回活动页
	 *
	 * @param string $app
	 * @param string $page 要进入活动的哪一页，页面的名称
	 *
	 */
	public function index_action($app, $rid = '', $page = '', $ek = null, $topic = null, $ignoretime = 'N') {
		$oApp = $this->modelApp->byId($app, ['cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			$this->outputError('指定的记录活动不存在，请检查参数是否正确');
		}

		if (empty($page)) {
			/* 计算打开哪个页面 */
			$oOpenPage = $this->_defaultPage($oApp, $rid, true, $ignoretime);
			$page = $oOpenPage->name;
		}

		/*页面是否要求必须存在填写轮次*/
		if (!in_array($page, ['rank'])) {
			if (empty($oApp->appRound)) {
				$this->outputError('【' . $oApp->title . '】没有可用的填写轮次，请检查');
			}
		}

		if (in_array($page, ['task', 'kanban', 'event'])) {
			$this->redirect("/rest/site/fe/matter/enroll/activities/" . $page . "?site={$this->siteId}&app={$app}");
		} else if (in_array($page, ['rank', 'votes', 'marks', 'stat'])) {
			$this->redirect("/rest/site/fe/matter/enroll/summary/" . $page . "?site={$this->siteId}&app={$app}&rid={$rid}");
		} else if (in_array($page, ['user', 'favor'])) {
			$this->redirect("/rest/site/fe/matter/enroll/people/" . $page . "?site={$this->siteId}&app={$app}");
		}

		$this->_outputPage($oApp, $page, $rid, $ek, $topic, $ignoretime);
	}
	/**
	 * 返回记录活动定义
	 *
	 * @param string $appid
	 * @param string $rid
	 * @param string $page page's name
	 * @param string $ek record's enroll key
	 * @param int $task 活动任务id
	 *
	 */
	public function get_action($app, $rid = '', $page = null, $ek = null, $ignoretime = 'N', $cascaded = 'N', $task = null) {
		$params = []; // 返回的结果
		/* 要打开的记录 */
		$modelRec = $this->model('matter\enroll\record');
		if (!empty($ek)) {
			$oOpenedRecord = $modelRec->byId($ek, ['verbose' => 'Y', 'state' => 1]);
		}
		/* 要打开的应用 */
		$aOptions = ['cascaded' => $cascaded, 'fields' => '*', 'appRid' => empty($oOpenedRecord->rid) ? $rid : $oOpenedRecord->rid];
		if (!empty($task)) {
			if ($oTask = $this->model('matter\enroll\task')->byId($task)) {
				$aOptions['task'] = $oTask;
			}
		}
		$oApp = $this->modelApp->byId($app, $aOptions);
		if ($oApp === false || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		if (isset($oApp->appRound->rid)) {
			$rid = $oApp->appRound->rid;
		}
		$params['app'] = $oApp;

		/* 当前访问用户的基本信息 */
		$oUser = $this->getUser($oApp);
		$params['user'] = $oUser;

		/* 进入规则 */
		$oEntryRuleResult = $this->checkEntryRule2($oApp);
		$params['entryRuleResult'] = $oEntryRuleResult;

		/* 站点页面设置 */
		if ($oApp->use_site_header === 'Y' || $oApp->use_site_footer === 'Y') {
			$params['site'] = $this->model('site')->byId(
				$oApp->siteid,
				[
					'fields' => 'id,name,summary,heading_pic,header_page_name,footer_page_name',
					'cascaded' => 'header_page_name,footer_page_name',
				]
			);
		}

		/* 项目页面设置 */
		if ($oApp->use_mission_header === 'Y' || $oApp->use_mission_footer === 'Y') {
			if ($oApp->mission_id) {
				$params['mission'] = $this->model('matter\mission')->byId(
					$oApp->mission_id,
					['cascaded' => 'header_page_name,footer_page_name']
				);
			}
		}

		/* 要打开的页面 */
		if (!in_array($page, ['task', 'event', 'kanban', 'repos', 'cowork', 'share', 'rank', 'score', 'votes', 'marks', 'favor', 'topic', 'stat'])) {
			$modelPage = $this->model('matter\enroll\page');
			$oUserEnrolled = $modelRec->lastByUser($oApp, $oUser, ['rid' => $rid]);
			/* 计算打开哪个页面 */
			if (empty($page)) {
				$oOpenPage = $this->_defaultPage($oApp, $rid, false, $ignoretime);
			} else {
				$oOpenPage = $modelPage->byName($oApp, $page);
			}
			if (empty($oOpenPage)) {
				return new \ResponseError('页面不存在');
			}
			/* 根据动态题目更新页面定义 */
			$modelPage->setDynaSchemas($oApp, $oOpenPage);
			/* 根据动态选项更新页面定义 */
			$modelPage->setDynaOptions($oApp, $oOpenPage);

			$params['page'] = $oOpenPage;
		}

		/**
		 * 获得当前活动的分组和当前用户所属的分组，是否为组长，及同组成员
		 */
		if (!empty($oApp->entryRule->group->id)) {
			$assocGroupAppId = $oApp->entryRule->group->id;
			/* 获得的分组信息 */
			$modelGrpTeam = $this->model('matter\group\team');
			$groups = $modelGrpTeam->byApp($assocGroupAppId, ['fields' => "team_id,title,team_type"]);
			$params['groups'] = $groups;
			/* 用户所属分组 */
			$modelGrpRec = $this->model('matter\group\record');
			$oGrpApp = (object) ['id' => $assocGroupAppId];
			$oGrpUsr = $modelGrpRec->byUser($oGrpApp, $oUser->uid, ['fields' => 'is_leader,team_id,team_title,userid,nickname', 'onlyOne' => true]);
			if ($oGrpUsr) {
				$params['groupUser'] = $oGrpUsr;
				$params['groupOthers'] = [];
				if (!empty($oGrpUsr->team_id)) {
					$others = $modelGrpRec->byTeam($oGrpUsr->team_id, ['fields' => 'is_leader,userid,nickname']);
					foreach ($others as $other) {
						if ($other->userid !== $oGrpUsr->userid) {
							$params['groupOthers'][] = $other;
						}
					}
				}
			}
		}

		return new \ResponseData($params);
	}
	/**
	 * 获得用户执行操作规则的状态
	 */
	public function entryRule_action($app) {
		$oApp = $this->modelApp->byId($app, ['cascaded' => 'N']);
		if ($oApp === false) {
			return new \ResponseError('指定的记录活动不存在，请检查参数是否正确');
		}

		$oEntryRuleResult = $this->checkEntryRule2($oApp);

		return new \ResponseData($oEntryRuleResult);
	}
	/**
	 * 获得指定坐标对应的地址名称
	 *
	 * 没有指定位置信息时通过日志获取当前用户最后一次发送的位置
	 */
	public function locationGet_action($siteid, $lat = '', $lng = '') {
		$geo = array();
		if (empty($lat) || empty($lat)) {
			$user = $this->who;
			if (empty($user->openid)) {
				return new \ResponseError('无法获得身份信息');
			}
			$q = array(
				'max(id)',
				'xxt_log_mpreceive',
				"mpid='$siteid' and openid='$user->openid' and type='event' and data like '%LOCATION%'",
			);
			if ($lastid = $this->model()->query_val_ss($q)) {
				$q = array(
					'data',
					'xxt_log_mpreceive',
					"id=$lastid",
				);
				$data = $this->model()->query_val_ss($q);
				$data = json_decode($data);
				$lat = $data[1];
				$lng = $data[2];
			} else {
				return new \ResponseError('无法获取位置信息');
			}
		}

		$url = "http://apis.map.qq.com/ws/geocoder/v1/";
		$url .= "?location=$lat,$lng";
		$url .= "&key=JUXBZ-JL3RW-UYYR2-O3QGA-CDBSZ-QBBYK";
		$rsp = file_get_contents($url);
		$rsp = json_decode($rsp);
		if ($rsp->status !== 0) {
			return new \ResponseError($rsp->message);
		}
		$geo['address'] = $rsp->result->address;

		return new \ResponseData($geo);
	}
	/**
	 * 给登记用户看的统计登记信息
	 *
	 * 只统计radio/checkbox类型的数据项
	 *
	 * return
	 * name => array(l=>label,c=>count)
	 *
	 */
	public function statGet_action($site, $app, $fromCache = 'N', $interval = 600) {
		$modelRec = $this->model('matter\enroll\record');
		if ($fromCache === 'Y') {
			$current = time();
			$q = [
				'create_at,id,title,v,l,c',
				'xxt_enroll_record_stat',
				"aid='$app'",
			];
			$cached = $modelRec->query_objs_ss($q);
			if (count($cached) && $cached[0]->create_at >= $current - $interval) {
				/*从缓存中获取统计数据*/
				$result = [];
				foreach ($cached as $data) {
					if (isset($result[$data->id])) {
						$item = &$result[$data->id];
					} else {
						$item = [
							'id' => $data->id,
							'title' => $data->title,
							'ops' => [],
						];
						$result[$data->id] = &$item;
					}
					$op = new \stdClass;
					$op->v = $data->v;
					$op->l = $data->l;
					$op->c = $data->c;
					$item['ops'][] = $op;
				}
			} else {
				$result = $modelRec->getStat($app);
				/*更新缓存的统计数据*/
				$modelRec->delete('xxt_enroll_record_stat', "aid='$app'");
				foreach ($result as $id => $stat) {
					foreach ($stat['ops'] as $op) {
						$r = [
							'siteid' => $site,
							'aid' => $app,
							'create_at' => $current,
							'id' => $id,
							'title' => $stat['title'],
							'v' => $op->v,
							'l' => $op->l,
							'c' => $op->c,
						];
						$modelRec->insert('xxt_enroll_record_stat', $r);
					}
				}
			}
		} else {
			/*直接获取统计数据*/
			$result = $modelRec->getStat($app);
		}

		return new \ResponseData($result);
	}
	/**
	 * 页面导航栏
	 */
	public function navs_action($site, $app) {
		$oApp = $this->modelApp->byId($app, ['cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$oUser = $this->getUser($oApp);

		$scenarioConfig = $oApp->scenarioConfig;
		// 获得共享页视图
		$getReposViews = function () use ($oApp) {
			$views = [];
			// 答案视图
			$can_cowork = 'N';
			foreach ($oApp->dynaDataSchemas as $oSchema) {
				if ($this->getDeepValue($oSchema, 'cowork') === 'Y') {
					$can_cowork = 'Y';
				}
			}
			if ($can_cowork === 'Y') {
				$vieAns = new \stdClass;
				$vieAns->title = '答案';
				$vieAns->type = 'cowork';
				$views[] = $vieAns;
			}
			// 记录视图
			$vieRec = new \stdClass;
			$vieRec->title = ($can_cowork === 'Y') ? '问题' : '记录';
			$vieRec->type = 'record';
			$views[] = $vieRec;
			// 专题视图
			$q = [
				'count(id)',
				'xxt_enroll_topic',
			];
			$q[2] = "state=1 and aid='{$oApp->id}' and (";
			$q[2] .= "is_public = 'Y'";
			isset($oUser->unionid) && $q[2] .= " or unionid = '{$oUser->unionid}'";
			isset($oUser->group_id) && $q[2] .= " or (share_in_group='Y' and group_id='{$oUser->group_id}')";
			$q[2] .= ")";
			if (($val = $this->modelApp->query_val_ss($q)) > 0) {
				$vieTopic = new \stdClass;
				$vieTopic->title = '专题';
				$vieTopic->type = 'topic';
				$views[] = $vieTopic;
			}

			return $views;
		};
		// 获得任务页视图
		$getActiViews = function () use ($oApp, $scenarioConfig, $oUser) {
			$views = [];
			// 是否有任务
			$can_task = 'N';
			$aTaskTypes = ['baseline', 'question', 'answer', 'vote', 'score'];
			$aTaskStates = ['IP', 'BS', 'AE'];
			$modelTsk = $this->model('matter\enroll\task', $oApp);
			foreach ($aTaskTypes as $taskType) {
				$rules = $modelTsk->getRule($taskType, $oUser);
				if (!empty($rules)) {
					foreach ($rules as $oRule) {
						if (!in_array($oRule->state, $aTaskStates)) {
							continue;
						}
						$oTask = $modelTsk->byRule($oRule, ['createIfNone' => true]);
						if ($oTask) {
							$can_task = 'Y';
							break;
						}
					}
				}
			}
			// 任务视图
			if ($can_task === 'Y') {
				$vieTask = new \stdClass;
				$vieTask->title = '任务';
				$vieTask->type = 'task';
				$views[] = $vieTask;
			}
			// 动态视图
			if ($this->getDeepValue($scenarioConfig, 'can_action') === 'Y') {
				$vieEvent = new \stdClass;
				$vieEvent->title = '动态';
				$vieEvent->type = 'event';
				$views[] = $vieEvent;
			}
			// 看板视图
			if ($this->getDeepValue($scenarioConfig, 'can_kanban') === 'Y') {
				$vieKanban = new \stdClass;
				$vieKanban->title = '看板';
				$vieKanban->type = 'kanban';
				$views[] = $vieKanban;
			}

			return $views;
		};
		// 获得汇总页视图
		$getSummViews = function () use ($scenarioConfig) {
			$views = [];
			// 排行榜
			if ($this->getDeepValue($scenarioConfig, 'can_rank') === 'Y') {
				$vieRank = new \stdClass;
				$vieRank->title = '排行';
				$vieRank->type = 'rank';
				$views[] = $vieRank;
			}
			// 投票榜
			if ($this->getDeepValue($scenarioConfig, 'can_votes') === 'Y') {
				$vieVotes = new \stdClass;
				$vieVotes->title = '投票榜';
				$vieVotes->type = 'votes';
				$views[] = $vieVotes;
			}
			// 打分榜
			if ($this->getDeepValue($scenarioConfig, 'can_marks') === 'Y') {
				$vieMarks = new \stdClass;
				$vieMarks->title = '打分榜';
				$vieMarks->type = 'marks';
				$views[] = $vieMarks;
			}
			// 统计
			if ($this->getDeepValue($scenarioConfig, 'can_stat') === 'Y') {
				$vieStat = new \stdClass;
				$vieStat->title = '统计';
				$vieStat->type = 'stat';
				$views[] = $vieStat;
			}

			return $views;
		};
		// 获得个人中心视图
		$getPeoViews = function () {
			$views = [];
			// 收藏
			$vieFavor = new \stdClass;
			$vieFavor->title = '收藏';
			$vieFavor->type = 'favor';
			$views[] = $vieFavor;
			// 个人
			$vieUser = new \stdClass;
			$vieUser->title = '个人';
			$vieUser->type = 'user';
			$views[] = $vieUser;

			return $views;
		};

		// 配置导航栏
		$navs = [];
		$url = [];
		$url[] = APP_PROTOCOL . APP_HTTP_HOST;
		$url[] = "/rest/site/fe/matter/enroll";
		$url[] = "?site={$site}&app=" . $oApp->id;
		// 项目
		if ($oApp->mission_id) {
			$misApp = $this->model('matter\mission')->byId($oApp->mission_id,['fields' => 'siteid,id']);
			$mis = new \stdClass;
			$mis->title = '项目';
			$mis->type = 'mission';
			$mis->url = $misApp->entryUrl;
			$navs[] = $mis;
		}

		// 共享页
		if ($this->getDeepValue($scenarioConfig, 'can_repos') === 'Y') {
			$repos = new \stdClass;
			$repos->title = '首页';
			$repos->type = 'repos';
			$repos->url = implode('', $url) . '&page=repos';
			// 视图
			$repos->views = $getReposViews();
			if (!empty($repos->views)) {
				$repos->defaultView = $repos->views[0];
				$navs[] = $repos;
			}
		}

		// 任务页
		$activities = new \stdClass;
		$activities->title = '任务';
		$activities->type = 'activities';
		// 视图
		$activities->views = $getActiViews();
		// 有至少一个视图时才有此导航页
		if (!empty($activities->views)) {
			$activities->defaultView = $activities->views[0];
			if (count($activities->views) === 1) {
				$activities->title = $activities->defaultView->title;
			}
			$urlActi = $url;
			$urlActi[1] = "/rest/site/fe/matter/enroll/activities/" . $activities->defaultView->type;
			$activities->url = implode('', $urlActi);
			$navs[] = $activities;
		}

		// 汇总页
		$summary = new \stdClass;
		$summary->title = '汇总';
		$summary->type = 'summary';
		// 视图
		$summary->views = $getSummViews();
		// 有至少一个视图时才有此导航页
		if (!empty($summary->views)) {
			$summary->defaultView = $summary->views[0];
			if (count($summary->views) === 1) {
				$summary->title = $summary->defaultView->title;
			}
			$urlSum = $url;
			$urlSum[1] = "/rest/site/fe/matter/enroll/summary/" . $summary->defaultView->type;
			$summary->url = implode('', $urlSum);
			$navs[] = $summary;
		}

		// 我的
		$people = new \stdClass;
		$people->title = '我的';
		$people->type = 'people';
		// 视图
		$people->views = $getPeoViews();
		if (!empty($people->views)) {
			$people->defaultView = $people->views[0];
			$urlPep = $url;
			$urlPep[1] = "/rest/site/fe/matter/enroll/people/" . $people->defaultView->type;
			$people->url = implode('', $urlPep);
			//
			$navs[] = $people;
		}

		return new \ResponseData($navs);
	}
}