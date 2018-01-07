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
	 * @param string $site
	 * @param string $app
	 * @param string $page 要进入活动的哪一页，页面的名称
	 *
	 */
	public function index_action($site, $app, $rid = '', $page = '', $ignoretime = 'N') {
		empty($site) && $this->outputError('没有指定当前站点的ID');
		empty($app) && $this->outputError('登记活动ID为空');
		$app = $this->escape($app);

		$oApp = $this->modelApp->byId($app, ['cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			$this->outputError('指定的登记活动不存在，请检查参数是否正确');
		}

		/* 检查是否需要第三方社交帐号OAuth */
		if (!$this->afterSnsOAuth()) {
			$this->requireSnsOAuth($oApp);
		}

		$skipEntryCheck = false;
		if (!empty($page) && !empty($oApp->entry_rule->exclude)) {
			if (in_array($page, $oApp->entry_rule->exclude)) {
				$skipEntryCheck = true;
			}
		}
		// 检查进入活动规则
		if (!$skipEntryCheck) {
			$this->checkEntryRule($oApp, true);
		}

		/* 返回登记活动页面 */
		if ($page === 'repos') {
			\TPL::assign('title', $oApp->title);
			\TPL::output('/site/fe/matter/enroll/repos');
		} elseif ($page === 'remark') {
			\TPL::assign('title', $oApp->title);
			\TPL::output('/site/fe/matter/enroll/remark');
		} elseif ($page === 'rank') {
			\TPL::assign('title', $oApp->title);
			\TPL::output('/site/fe/matter/enroll/rank');
		} elseif ($page === 'score') {
			\TPL::assign('title', $oApp->title);
			\TPL::output('/site/fe/matter/enroll/score');
		} else {
			if (empty($page)) {
				/* 计算打开哪个页面 */
				$oOpenPage = $this->_defaultPage($oApp, $rid, true, $ignoretime);
			} else {
				$oOpenPage = $this->model('matter\enroll\page')->byName($oApp->id, $page);
			}
			empty($oOpenPage) && $this->outputError('没有可访问的页面');
			if ($oOpenPage->name === 'repos') {
				\TPL::assign('title', $oApp->title);
				\TPL::output('/site/fe/matter/enroll/repos');
			} else if ($oOpenPage->name === 'rank') {
				\TPL::assign('title', $oApp->title);
				\TPL::output('/site/fe/matter/enroll/rank');
			} elseif ($oOpenPage->name === 'score') {
				\TPL::assign('title', $oApp->title);
				\TPL::output('/site/fe/matter/enroll/score');
			} else if ($oOpenPage->type === 'I') {
				\TPL::assign('title', $oApp->title);
				\TPL::output('/site/fe/matter/enroll/input');
			} else if ($oOpenPage->type === 'V') {
				\TPL::assign('title', $oApp->title);
				\TPL::output('/site/fe/matter/enroll/view');
			} else if ($oOpenPage->type === 'L') {
				\TPL::assign('title', $oApp->title);
				\TPL::output('/site/fe/matter/enroll/list');
			}
		}
		exit;
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
	private function _defaultPage(&$oApp, $rid = '', $redirect = false, $ignoretime = 'N') {
		$oUser = $this->who;
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
			$page = $this->checkEntryRule($oApp, $redirect);
			$oOpenPage = $modelPage->byName($oApp->id, $page);
		}

		if ($oOpenPage === null) {
			if ($redirect === true) {
				$this->outputError('指定的页面[' . $page . ']不存在');
			}
		}

		return $oOpenPage;
	}
	/**
	 * 返回登记记录
	 *
	 * @param string $siteid
	 * @param string $appid
	 * @param string $rid round's id
	 * @param string $page page's name
	 * @param string $ek record's enroll key
	 * @param string $newRecord
	 */
	public function get_action($app, $rid = '', $page = null, $ek = null, $newRecord = null, $ignoretime = 'N', $cascaded = 'N') {
		/* 登记活动定义 */
		$oApp = $this->modelApp->byId($app, ['cascaded' => $cascaded]);
		if ($oApp === false) {
			return new \ResponseError('指定的登记活动不存在，请检查参数是否正确');
		}
		unset($oApp->data_schemas);
		unset($oApp->round_cron);
		unset($oApp->rp_config);

		$params = [];
		$params['app'] = &$oApp;

		/* 当前访问用户的基本信息 */
		$oUser = $this->who;

		/* 补充联系人信息，是在什么情况下都需要补充吗？ 应该在限制了联系人访问的情况下，而且应该只返回相关的 */
		$modelMem = $this->model('site\user\member');
		if (empty($oUser->unionid)) {
			$aMembers = $modelMem->byUser($oUser->uid);
			if (count($aMembers)) {
				!isset($oUser->members) && $oUser->members = new \stdClass;
				foreach ($aMembers as $oMember) {
					$oUser->members->{$oMember->schema_id} = $oMember;
				}
			}
		} else {
			$modelAcnt = $this->model('site\user\account');
			$aUnionUsers = $modelAcnt->byUnionid($oUser->unionid, ['siteid' => $oApp->siteid, 'fields' => 'uid']);
			foreach ($aUnionUsers as $oUnionUser) {
				$aMembers = $modelMem->byUser($oUnionUser->uid);
				if (count($aMembers)) {
					!isset($oUser->members) && $oUser->members = new \stdClass;
					foreach ($aMembers as $oMember) {
						$oUser->members->{$oMember->schema_id} = $oMember;
					}
				}
			}
		}
		$params['user'] = $oUser;

		/* 操作规则 */
		$oActionRule = $this->checkActionRule($oApp);
		$params['actionRule'] = $oActionRule;

		/* 站点页面设置 */
		if ($oApp->use_site_header === 'Y' || $oApp->use_site_footer === 'Y') {
			$params['site'] = $this->model('site')->byId(
				$oApp->siteid,
				['cascaded' => 'header_page_name,footer_page_name']
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
		if ($oApp->multi_rounds === 'Y') {
			$modelRnd = $this->model('matter\enroll\round');
			if (empty($rid)) {
				$oActiveRnd = $modelRnd->getActive($oApp);
				if ($oActiveRnd) {
					$rid = $oActiveRnd->rid;
				}
				$params['activeRound'] = $oActiveRnd;
			} else {
				$params['activeRound'] = $modelRnd->byId($rid);
			}
		}

		$modelRec = $this->model('matter\enroll\record');
		if (!in_array($page, ['repos', 'remark', 'rank', 'score'])) {
			$oUserEnrolled = $modelRec->lastByUser($oApp, $oUser, ['asaignRid' => $rid]);
			/* 自动登记???，解决之要打开了页面就登记？ */
			if (!$oUserEnrolled && $oApp->can_autoenroll === 'Y' && $oOpenPage->autoenroll_onenter === 'Y') {
				$options = [
					'fields' => 'enroll_key,enroll_at',
					'asaignRid' => $rid,
				];
				$lastRecord = $modelRec->lastByUser($oApp, $oUser, $options);
				if (false === $lastRecord) {
					$modelRec->add($oApp->siteid, $oApp, $oUser, (empty($posted->referrer) ? '' : $posted->referrer));
				} else if ($lastRecord->enroll_at === '0') {
					$updated = [
						'enroll_at' => time(),
					];
					!empty($posted->referrer) && $updated['referrer'] = $posted->referrer;
					$modelRec->update('xxt_enroll_record', $updated, "enroll_key='$lastRecord->enroll_key'");
				}
			}
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

			if (!in_array($oOpenPage->name, ['repos', 'rank'])) {
				$params['page'] = $oOpenPage;
				/* 是否需要返回登记记录 */
				if ($oOpenPage->type === 'I' && ($newRecord === 'Y' || empty($ek))) {
					/* 查询是否有保存的数据 */
					$saveRecode = $this->model('matter\log')->lastByUser($oApp->id, 'enroll', $oUser->uid, ['byOp' => 'saveData']);
					$params['record'] = new \stdClass;
					if (count($saveRecode) == 1) {
						$saveRecode = $saveRecode[0];
						$saveRecode->opData = json_decode($saveRecode->operate_data);
						$params['record']->data = $saveRecode->opData->data;
						$params['record']->supplement = $saveRecode->opData->supplement;
						$params['record']->data_tag = $saveRecode->opData->data_tag;
					}
					/* 返回当前用户在关联活动中填写的数据 */
					if (!empty($oApp->enroll_app_id)) {
						$oAssocApp = $this->model('matter\enroll')->byId($oApp->enroll_app_id, ['cascaded' => 'N']);
						if ($oAssocApp) {
							$oAssocRec = $modelRec->byUser($oAssocApp, $oUser);
							if (count($oAssocRec) === 1) {
								if (!empty($oAssocRec[0]->data)) {
									$oAssocRecord = $oAssocRec[0]->data;
									if (!isset($params['record']->data)) {
										$params['record']->data = new \stdClass;
									}
									foreach ($oAssocRecord as $key => $value) {
										$params['record']->data->{$key} = $value;
									}
								}
							}
						}
					}
					if (!empty($oApp->group_app_id)) {
						$oGrpApp = $this->model('matter\group')->byId($oApp->group_app_id, ['cascaded' => 'N']);
						$oGrpPlayer = $this->model('matter\group\player')->byUser($oGrpApp, $oUser->uid);
						if (count($oGrpPlayer) === 1) {
							if (!empty($oGrpPlayer[0]->data)) {
								if (!isset($params['record']->data)) {
									$params['record']->data = new \stdClass;
								}
								if (is_string($oGrpPlayer[0]->data)) {
									$oAssocRecord = json_decode($oGrpPlayer[0]->data);
								} else {
									$oAssocRecord = $oGrpPlayer[0]->data;
								}

								$oAssocRecord->_round_id = $oGrpPlayer[0]->round_id;
								foreach ($oAssocRecord as $k => $v) {
									$params['record']->data->{$k} = $v;
								}
							}
						}
					}
				} else {
					if (($oOpenPage->type === 'I' && $newRecord !== 'Y') || $oOpenPage->type === 'V' || $oOpenPage->name === 'score') {
						if (empty($ek)) {
							if ($oApp->open_lastroll === 'Y' || $oOpenPage->type === 'V') {
								/* 获得最后一条登记数据。记录有可能未进行过数据填写 */
								$options = [
									'fields' => '*',
									'verbose' => 'Y',
									'assignRid' => $rid,
								];
								$oLastRecord = $modelRec->lastByUser($oApp, $oUser, $options);
								$params['record'] = $oLastRecord;
							}
						} else {
							$oRecord = $modelRec->byId($ek, ['verbose' => 'Y', 'state' => 1]);
							$params['record'] = $oRecord;
						}
						if ($oOpenPage->type === 'I') {
							/* 查询是否有保存的数据 */
							$saveRecode = $this->model('matter\log')->lastByUser($oApp->id, 'enroll', $oUser->uid, ['byOp' => 'saveData']);
							if (count($saveRecode) == 1) {
								$saveRecode = $saveRecode[0];
								$saveRecode->opData = json_decode($saveRecode->operate_data);
								$params['record']->data = $saveRecode->opData->data;
								$params['record']->supplement = $saveRecode->opData->supplement;
								$params['record']->data_tag = $saveRecode->opData->data_tag;
							}
						}
					}
				}
			}
		} else if (in_array($page, ['remark', 'score']) && !empty($ek)) {
			$oRecord = $modelRec->byId($ek, ['verbose' => 'Y', 'state' => 1]);
			$params['record'] = $oRecord;
		}
		/**
		 * 获得当前用户所属的分组，是否为组长，及同组成员
		 */
		if (!empty($oApp->entry_rule->group->id)) {
			$modelGrpUsr = $this->model('matter\group\player');
			$oGrpApp = (object) ['id' => $oApp->entry_rule->group->id];
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
	public function actionRule_action($app) {
		$oApp = $this->modelApp->byId($app, ['cascaded' => 'N']);
		if ($oApp === false) {
			return new \ResponseError('指定的登记活动不存在，请检查参数是否正确');
		}

		$oActionRule = $this->checkActionRule($oApp);

		return new \ResponseData($oActionRule);
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