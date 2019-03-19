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

		/*  *******************************************************
			为了兼容当前版本注释此段代码，等新版本发布后将运用此段代码
			*******************************************************  */

		if (in_array($page, ['task', 'kanban', 'event'])) {
			$this->redirect("/rest/site/fe/matter/enroll/activities/" . $page . "?site={$this->siteId}&app={$app}&rid={$rid}&page={$page}&ek={$ek}&topic={$topic}&ignoretime={$ignoretime}");
		} else if (in_array($page, ['rank', 'votes', 'marks', 'stat'])) {
			$this->redirect("/rest/site/fe/matter/enroll/summary/" . $page . "?site={$this->siteId}&app={$app}&rid={$rid}&page={$page}&ek={$ek}&topic={$topic}&ignoretime={$ignoretime}");
		} else if (in_array($page, ['user', 'favor'])) {
			$this->redirect("/rest/site/fe/matter/enroll/people/" . $page . "?site={$this->siteId}&app={$app}&rid={$rid}&page={$page}&ek={$ek}&topic={$topic}&ignoretime={$ignoretime}");
		}

		$this->_outputPage($oApp, $page, $rid, $ek, $topic, $ignoretime);

		// 处理输出页面信息
		// $outputTitle = '';
		// $outputUrl = '/site/fe/matter/enroll/';
		// if (in_array($page, ['cowork', 'share', 'score', 'repos', 'topic', 'task', 'kanban', 'event', 'rank', 'votes', 'marks', 'stat', 'user', 'favor'])) {
		// 	if ($page === 'topic' && empty($topic)) {
		// 		$this->outputError('参数不完整，无法访问专题页');
		// 	}
		// 	if (in_array($page, ['topic', 'share']) && !empty($topic)) {
		// 		$modelTop = $this->model('matter\enroll\topic', $oApp);
		// 		$oTopic = $modelTop->byId($topic, ['fields' => 'id,state,title']);
		// 		if ($oTopic && $oTopic->state === '1') {
		// 			$outputTitle = $oTopic->title . '|';
		// 		} else {
		// 			$this->outputError('专题页已删除');
		// 		}
		// 	} else if (in_array($page, ['cowork', 'share']) && !empty($ek)) {
		// 		$modelRec = $this->model('matter\enroll\record');
		// 		$oRecord = $modelRec->byId($ek, ['fields' => 'id,state']);
		// 		if ($oRecord && $oRecord->state === '1') {
		// 			$outputTitle = '记录' . $oRecord->id . '|';
		// 		} else {
		// 			$this->outputError('记录已删除');
		// 		}
		// 	}
		// 	$outputUrl .= $page;
		// } else {
		// 	$oOpenPage = $this->model('matter\enroll\page')->byName($oApp, $page);
		// 	if (empty($oOpenPage)) {
		// 		$this->outputError('没有可访问的页面');
		// 	}
		// 	$page = $oOpenPage->name;
		// 	if ($oOpenPage->type === 'I') {
		// 		$outputUrl .= 'input';
		// 	} else if ($oOpenPage->type === 'V') {
		// 		$outputUrl .= 'view';
		// 	} else {
		// 		$this->outputError('没有可访问的页面');
		// 	}
		// }

		// // 页面是否开放
		// if (!$this->_checkOpenRule($oApp, $page)) {
		// 	$this->outputError('页面未开放, 请联系系统管理员');
		// }

		// /* 检查是否需要第三方社交帐号OAuth */
		// if (!$this->afterSnsOAuth()) {
		// 	$this->requireSnsOAuth($oApp);
		// }

		// $oUser = $this->who;
		// // 检查进入活动规则
		// $this->checkEntryRule($oApp, true, $oUser, $page);
		// // 记录日志
		// if (in_array($page, ['topic', 'repos', 'cowork'])) {
		// 	$this->_pageReadlog($oApp, $page, $rid, $ek, $topic);
		// }

		// /* 返回记录活动页面 */
		// $customViewName = TMS_APP_VIEW_NAME;
		// if (isset($oUser->unionid)) {
		// 	$oAccount = $this->model('account')->byId($oUser->unionid, ['cascaded' => ['group']]);
		// 	if (isset($oAccount->group->view_name) && $oAccount->group->view_name !== TMS_APP_VIEW_NAME) {
		// 		$customViewName = $oAccount->group->view_name;
		// 	}
		// }

		// \TPL::assign('title', $outputTitle . $oApp->title);
		// \TPL::output($outputUrl, ['customViewName' => $customViewName]);
		// exit;

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

		$isCowork = false;
		foreach ($oApp->dynaDataSchemas as $oSchema) {
			if ($this->getDeepValue($oSchema, 'cowork') === 'Y') {
				$isCowork = true;
			}
		}
		$scenarioConfig = $oApp->scenarioConfig;
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
			$views = [];
			// 答案视图
			if ($isCowork) {
				$vieAns = new \stdClass;
				$vieAns->title = '答案';
				$vieAns->type = 'answer';
				$views[] = $vieAns;
			}
			// 记录视图
			$vieRec = new \stdClass;
			$vieRec->title = $isCowork ? '问题' : '记录';
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
			$repos->views = $views;
			$repos->defaultView = $repos->views[0];
			$navs[] = $repos;
			unset($views);
		}
		// 任务页
		if ($this->getDeepValue($scenarioConfig, 'can_kanban') === 'Y' || $this->getDeepValue($scenarioConfig, 'can_action') === 'Y') {

		}






		// 汇总页
		if ($this->getDeepValue($scenarioConfig, 'can_rank') === 'Y' || $this->getDeepValue($scenarioConfig, 'can_stat') === 'Y' || $this->getDeepValue($scenarioConfig, 'can_votes') === 'Y' || $this->getDeepValue($scenarioConfig, 'can_marks') === 'Y') {
			$summary = new \stdClass;
			$summary->title = '汇总';
			$summary->type = 'summary';
			// 视图
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
			$summary->views = $views;
			if (!empty($views)) {
				$summary->defaultView = $views[0];
				if (count($views) === 1) {
					$summary->title = $views[0]->title;
				}
				$urlSum = $url;
				$urlSum[1] = "/rest/site/fe/matter/enroll/summary/" . $summary->defaultView->type;
				$summary->url = implode('', $urlSum);
				$navs[] = $summary;
			}
			unset($views);
		}

		// 我的
		$people = new \stdClass;
		$people->title = '我的';
		$people->type = 'people';
		// 视图
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
		// 
		$people->views = $views;
		$people->defaultView = $people->views[0];
		$urlPep = $url;
		$urlPep[1] = "/rest/site/fe/matter/enroll/people/" . $people->defaultView->type;
		$people->url = implode('', $urlPep);
		//
		$navs[] = $people;
		unset($views);

		return new \ResponseData($navs);
	}
}