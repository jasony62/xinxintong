<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动
 */
class main extends base {
	/**
	 *
	 */
	const AppFields = 'id,state,siteid,title,summary,pic,assigned_nickname,open_lastroll,can_coin,can_cowork,can_rank,can_repos,can_siteuser,count_limit,data_schemas,start_at,end_at,end_submit_at,entry_rule,action_rule,mission_id,multi_rounds,read_num,scenario,share_friend_num,share_timeline_num,use_mission_header,use_mission_footer,use_site_header,use_site_footer,enrolled_entry_page,group_app_id,enroll_app_id,repos_config,rank_config,scenario_config,round_cron,mission_id,sync_mission_round';
	/**
	 *
	 */
	private $modelApp;
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->modelApp = $this->model('matter\enroll');
	}
	/**
	 * 返回活动页
	 *
	 * @param string $app
	 * @param string $page 要进入活动的哪一页，页面的名称
	 *
	 */
	public function index_action($app, $rid = '', $page = '', $ek = null, $topic = null, $ignoretime = 'N') {
		empty($app) && $this->outputError('登记活动ID为空');

		$oApp = $this->modelApp->byId($app, ['cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			$this->outputError('指定的登记活动不存在，请检查参数是否正确');
		}

		/* 检查是否需要第三方社交帐号OAuth */
		if (!$this->afterSnsOAuth()) {
			$this->requireSnsOAuth($oApp);
		}

		$bSkipEntryCheck = false;
		if (!empty($page) && !empty($oApp->entryRule->exclude)) {
			if (in_array($page, $oApp->entryRule->exclude)) {
				$bSkipEntryCheck = true;
			}
		}
		// 检查进入活动规则
		if (!$bSkipEntryCheck) {
			$this->checkEntryRule($oApp, true);
		}

		/* 返回登记活动页面 */
		if (in_array($page, ['cowork', 'share', 'event', 'rank', 'score', 'repos', 'favor', 'topic'])) {
			/* 设置页面标题 */
			if (in_array($page, ['topic', 'share']) && !empty($topic)) {
				$modelTop = $this->model('matter\enroll\topic');
				$oTopic = $modelTop->byId($topic, ['fields' => 'id,state,title']);
				if ($oTopic && $oTopic->state === '1') {
					$title = $oTopic->title . '|';
				}
			} else if (in_array($page, ['cowork', 'share']) && !empty($ek)) {
				$modelRec = $this->model('matter\enroll\record');
				$oRecord = $modelRec->byId($ek, ['fields' => 'id,state']);
				if ($oRecord && $oRecord->state === '1') {
					$title = '记录' . $oRecord->id . '|';
				}
			}
			if (in_array($page, ['topic', 'repos', 'cowork'])) {
				$this->_pageReadlog($oApp, $page, $rid, $ek, $topic);
			}
			\TPL::assign('title', empty($title) ? $oApp->title : ($title . $oApp->title));
			\TPL::output('/site/fe/matter/enroll/' . $page);
		} else {
			if (empty($page)) {
				/* 计算打开哪个页面 */
				$oOpenPage = $this->_defaultPage($oApp, $rid, true, $ignoretime);
			} else {
				$oOpenPage = $this->model('matter\enroll\page')->byName($oApp->id, $page);
			}
			empty($oOpenPage) && $this->outputError('没有可访问的页面');
			\TPL::assign('title', $oApp->title);
			if (in_array($oOpenPage->name, ['event', 'rank', 'score', 'repos', 'favor', 'topic'])) {
				\TPL::output('/site/fe/matter/enroll/' . $oOpenPage->name);
			} else if ($oOpenPage->type === 'I') {
				\TPL::output('/site/fe/matter/enroll/input');
			} else if ($oOpenPage->type === 'V') {
				\TPL::output('/site/fe/matter/enroll/view');
			} else if ($oOpenPage->type === 'L') {
				\TPL::output('/site/fe/matter/enroll/list');
			}
		}
		exit;
	}
	/**
	 *
	 */
	private function _pageReadlog($oApp, $page, $rid = '', $ek = null, $topic = null) {
		// 获得当前获得所属轮次
		if ($rid === 'ALL') {
			$rid = '';
		}
		if (empty($rid)) {
			if ($activeRound = $this->model('matter\enroll\round')->getActive($oApp)) {
				$rid = $activeRound->rid;
			}
		}
		$oUser = $this->getUser($oApp);
		// 修改阅读数'topic', 'repos', 'cowork'
		if ($page === 'topic') {
			$upUserData = new \stdClass;
			$upUserData->do_topic_read_num = 1;
			// 查询专题页创建者
			$creater = $this->model('matter\enroll\topic')->byId($topic, ['fields' => 'userid uid,nickname']);
			if ($creater) {
				$upCreaterData = new \stdClass;
				$upCreaterData->topic_read_num = 1;
			}
		} else if ($page === 'cowork') {
			$upUserData = new \stdClass;
			$upUserData->do_cowork_read_num = 1;
			// 查询记录提交者
			$creater = $this->model('matter\enroll\record')->byId($ek, ['fields' => 'userid uid,rid,nickname', 'verbose' => 'N']);
			if ($creater) {
				$upCreaterData = new \stdClass;
				$upCreaterData->cowork_read_num = 1;
				$rid = $creater->rid;
			}
		} else {
			$upUserData = new \stdClass;
			$upUserData->do_repos_read_num = 1;
		}

		// 更新用户轮次数据
		$modelEvent = $this->model('matter\enroll\event');
		$modelEvent->_updateUsrData($oApp, $rid, false, $oUser, $upUserData);
		// 更新被阅读者轮次数据
		if (!empty($upCreaterData)) {
			$modelEvent->_updateUsrData($oApp, $rid, false, $creater, $upCreaterData);
		}

		return [true];
	}
	/**
	 * 登记活动是否可用
	 *
	 * @param object $app 登记活动
	 */
	private function _isValid(&$app) {
		$tipPage = false;
		$current = time();
		if ($app->start_at != 0 && $current < $app->start_at) {
			if (empty($app->before_start_page)) {
				return [false, '【' . $app->title . '】没有开始'];
			} else {
				$tipPage = $app->before_start_page;
			}
		} else if ($app->end_at != 0 && $current > $app->end_at) {
			if (empty($app->after_end_page)) {
				return [false, '【' . $app->title . '】已经结束'];
			} else {
				$tipPage = $app->after_end_page;
			}
		}
		if ($tipPage !== false) {
			$oOpenPage = $this->model('matter\enroll\page')->byName($app->id, $tipPage);
			return [false, $oOpenPage];
		}

		return [true];
	}
	/**
	 * 当前用户的缺省页面
	 *
	 * 1、如果没有登记过，根据设置的进入规则获得指定页面
	 * 2、如果已经登记过，且指定了登记过访问页面，进入指定的页面
	 * 3、如果已经登记过，且没有指定登记过访问页面，进入第一个查看页
	 */
	private function _defaultPage($oApp, $rid = '', $redirect = false, $ignoretime = 'N') {
		$oUser = $this->getUser($oApp);
		$oOpenPage = null;
		$modelPage = $this->model('matter\enroll\page');

		if ($ignoretime === 'N') {
			$rst = $this->_isValid($oApp);
			if ($rst[0] === false) {
				if (is_string($rst[1])) {
					if ($redirect === true) {
						$this->outputError($rst[1], $oApp->title);
					}
					return null;
				} else {
					$oOpenPage = $rst[1];
				}
			}
		}

		if ($oOpenPage === null) {
			// 根据登记状态确定进入页面
			$modelRec = $this->model('matter\enroll\record');
			$userEnrolled = $modelRec->lastByUser($oApp, $oUser, ['assignRid' => $rid]);
			if ($userEnrolled) {
				if (empty($oApp->enrolled_entry_page)) {
					$pages = $modelPage->byApp($oApp->id);
					foreach ($pages as $p) {
						if ($p->type === 'V') {
							$oOpenPage = $modelPage->byId($oApp->id, $p->id);
							break;
						}
					}
				} else {
					if ($oApp->enrolled_entry_page === 'score') {
						$oOpenPage = new \stdClass;
						$oOpenPage->name = $oApp->enrolled_entry_page;
						$oOpenPage->type = '';
					} else {
						$oOpenPage = $modelPage->byName($oApp->id, $oApp->enrolled_entry_page);
					}
				}
			}
		}

		if ($oOpenPage === null) {
			// 根据进入规则确定进入页面
			$aResult = $this->checkEntryRule($oApp, $redirect);
			if (true === $aResult[0]) {
				$oOpenPage = $modelPage->byName($oApp->id, $aResult[1]);
			}
		}

		if ($oOpenPage === null) {
			if ($redirect === true) {
				$this->outputError('指定的页面[' . $page . ']不存在');
			}
		}

		return $oOpenPage;
	}
	/**
	 * 返回登记活动定义
	 *
	 * @param string $appid
	 * @param string $rid
	 * @param string $page page's name
	 * @param string $ek record's enroll key
	 *
	 */
	public function get_action($app, $rid = '', $page = null, $ek = null, $ignoretime = 'N', $cascaded = 'N') {
		$params = []; // 返回的结果
		/* 要打开的记录 */
		$modelRec = $this->model('matter\enroll\record');
		if (!empty($ek)) {
			$oOpenedRecord = $modelRec->byId($ek, ['verbose' => 'Y', 'state' => 1]);
		}
		/* 要打开的应用 */
		$oApp = $this->modelApp->byId($app, ['cascaded' => $cascaded, 'fields' => self::AppFields, 'appRid' => empty($oOpenedRecord->rid) ? $rid : $oOpenedRecord->rid]);
		if ($oApp === false || $oApp->state !== '1') {
			return new \ResponseError('指定的登记活动不存在，请检查参数是否正确');
		}
		if (isset($oApp->appRound)) {
			$rid = $oApp->appRound->rid;
		}
		unset($oApp->round_cron);
		unset($oApp->rp_config);
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
		if (!in_array($page, ['event', 'repos', 'cowork', 'share', 'rank', 'score', 'favor', 'topic'])) {
			$oUserEnrolled = $modelRec->lastByUser($oApp, $oUser, ['asaignRid' => $rid]);
			/* 计算打开哪个页面 */
			if (empty($page)) {
				$oOpenPage = $this->_defaultPage($oApp, $rid, false, $ignoretime);
			} else {
				$modelPage = $this->model('matter\enroll\page');
				$oOpenPage = $modelPage->byName($oApp->id, $page);
			}
			if (empty($oOpenPage)) {
				return new \ResponseError('页面不存在');
			}
			$params['page'] = $oOpenPage;
		}

		/**
		 * 获得当前活动的分组和当前用户所属的分组，是否为组长，及同组成员
		 */
		if ((isset($oApp->entryRule->scope->group) && $oApp->entryRule->scope->group === 'Y' && !empty($oApp->entryRule->group->id)) || !empty($oApp->group_app_id)) {
			$assocGroupAppId = (isset($oApp->entryRule->scope->group) && $oApp->entryRule->scope->group === 'Y' && !empty($oApp->entryRule->group->id)) ? $oApp->entryRule->group->id : $oApp->group_app_id;
			/* 获得的分组信息 */
			$modelGrpRnd = $this->model('matter\group\round');
			$groups = $modelGrpRnd->byApp($assocGroupAppId, ['fields' => "round_id,title"]);
			$params['groups'] = $groups;
			/* 用户所属分组 */
			$modelGrpUsr = $this->model('matter\group\player');
			$oGrpApp = (object) ['id' => $assocGroupAppId];
			$oGrpUsr = $modelGrpUsr->byUser($oGrpApp, $oUser->uid, ['fields' => 'is_leader,round_id,round_title,userid,nickname', 'onlyOne' => true]);
			if ($oGrpUsr) {
				$others = $modelGrpUsr->byRound($oGrpApp->id, $oGrpUsr->round_id, ['fields' => 'is_leader,userid,nickname']);
				$params['groupUser'] = $oGrpUsr;
				$params['groupOthers'] = [];
				foreach ($others as $other) {
					if ($other->userid !== $oGrpUsr->userid) {
						$params['groupOthers'][] = $other;
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
			return new \ResponseError('指定的登记活动不存在，请检查参数是否正确');
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
}