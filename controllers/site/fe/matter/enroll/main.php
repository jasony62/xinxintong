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
		if (in_array($page, ['task', 'kanban', 'event'])) {
			$this->redirect("/rest/site/fe/matter/enroll/activities/" . $page . "?site={$this->siteId}&app={$app}&rid={$rid}&page={$page}&ek={$ek}&topic={$topic}&ignoretime={$ignoretime}");
		} else if (in_array($page, ['rank', 'votes', 'marks', 'stat'])) {
			$this->redirect("/rest/site/fe/matter/enroll/summary/" . $page . "?site={$this->siteId}&app={$app}&rid={$rid}&page={$page}&ek={$ek}&topic={$topic}&ignoretime={$ignoretime}");
		} else if (in_array($page, ['user', 'favor'])) {
			$this->redirect("/rest/site/fe/matter/enroll/people/" . $page . "?site={$this->siteId}&app={$app}&rid={$rid}&page={$page}&ek={$ek}&topic={$topic}&ignoretime={$ignoretime}");
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
}