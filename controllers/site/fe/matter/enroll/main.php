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
	public function index_action($site, $app, $page = '', $ignoretime = 'N') {
		empty($site) && $this->outputError('没有指定当前站点的ID');
		empty($app) && $this->outputError('登记活动ID为空');

		$oApp = $this->modelApp->byId($app, ['cascaded' => 'N']);
		if ($oApp === false) {
			$this->outputError('指定的登记活动不存在，请检查参数是否正确');
		}
		if (!$this->afterSnsOAuth()) {
			/* 检查是否需要第三方社交帐号OAuth */
			$this->_requireSnsOAuth($site, $oApp);
		}

		// 检查进入规则
		$this->checkEntryRule($oApp, true);

		/* 返回登记活动页面 */
		\TPL::assign('title', $oApp->title);
		if ($page === 'repos') {
			\TPL::output('/site/fe/matter/enroll/repos');
		} elseif ($page === 'remark') {
			\TPL::output('/site/fe/matter/enroll/remark');
		} else {
			if (empty($page)) {
				/* 计算打开哪个页面 */
				$oOpenPage = $this->_defaultPage($oApp, true, $ignoretime);
			} else {
				$oOpenPage = $this->model('matter\enroll\page')->byName($oApp->id, $page);
			}
			empty($oOpenPage) && $this->outputError('没有可访问的页面');
			if ($oOpenPage->type === 'I') {
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
	 * 检查是否需要第三方社交帐号认证
	 * 检查条件：
	 * 0、应用是否设置了需要认证
	 * 1、站点是否绑定了第三方社交帐号认证
	 * 2、平台是否绑定了第三方社交帐号认证
	 * 3、用户客户端是否可以发起认证
	 *
	 * @param string $site
	 * @param object $app
	 */
	private function _requireSnsOAuth($siteid, &$app) {
		$entryRule = $app->entry_rule;
		if (isset($entryRule->scope) && $entryRule->scope === 'sns') {
			if ($this->userAgent() === 'wx') {
				if (!empty($entryRule->sns->wx->entry)) {
					if (!isset($this->who->sns->wx)) {
						$modelWx = $this->model('sns\wx');
						if (($wxConfig = $modelWx->bySite($siteid)) && $wxConfig->joined === 'Y') {
							$this->snsOAuth($wxConfig, 'wx');
						} else if (($wxConfig = $modelWx->bySite('platform')) && $wxConfig->joined === 'Y') {
							$this->snsOAuth($wxConfig, 'wx');
						}
					}
				}
				if (!empty($entryRule->sns->qy->entry)) {
					if (!isset($this->who->sns->qy)) {
						if ($qyConfig = $this->model('sns\qy')->bySite($siteid)) {
							if ($qyConfig->joined === 'Y') {
								$this->snsOAuth($qyConfig, 'qy');
							}
						}
					}
				}
			} else if (!empty($entryRule->sns->yx->entry) && $this->userAgent() === 'yx') {
				if (!isset($this->who->sns->yx)) {
					if ($yxConfig = $this->model('sns\yx')->bySite($siteid)) {
						if ($yxConfig->joined === 'Y') {
							$this->snsOAuth($yxConfig, 'yx');
						}
					}
				}
			}
		}

		return false;
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
	private function _defaultPage(&$oApp, $redirect = false, $ignoretime = 'N') {
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
			$userEnrolled = $modelRec->lastByUser($oApp, $oUser);
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
					$oOpenPage = $modelPage->byName($oApp->id, $oApp->enrolled_entry_page);
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
	public function get_action($site, $app, $rid = null, $page = null, $ek = null, $newRecord = null, $ignoretime = 'N', $cascaded = 'N') {
		$params = [];

		/* 登记活动定义 */
		$oApp = $this->modelApp->byId($app, ['cascaded' => $cascaded]);
		if ($oApp === false) {
			return new \ResponseError('指定的登记活动不存在，请检查参数是否正确');
		}
		$params['app'] = &$oApp;

		/* 当前访问用户的基本信息 */
		$user = $this->who;
		if (!empty($user->members)) {
			$modelMem = $this->model('site\user\member');
			foreach ($user->members as $schemaId => $member) {
				$freshMember = $modelMem->byId($member->id);
				$user->members->{$schemaId} = $freshMember;
			}
		}
		$params['user'] = $user;

		/* 站点页面设置 */
		if ($oApp->use_site_header === 'Y' || $oApp->use_site_footer === 'Y') {
			$params['site'] = $this->model('site')->byId(
				$site,
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
			$params['activeRound'] = $this->model('matter\enroll\round')->getActive($oApp);
		}

		$modelRec = $this->model('matter\enroll\record');
		if ($page !== 'repos' && $page !== 'remark') {
			$userEnrolled = $modelRec->lastByUser($oApp, $user);
			/* 自动登记???，解决之要打开了页面就登记？ */
			if (!$userEnrolled && $oApp->can_autoenroll === 'Y' && $oOpenPage->autoenroll_onenter === 'Y') {
				$options = [
					'fields' => 'enroll_key,enroll_at',
				];
				$lastRecord = $modelRec->lastByUser($$oApp->id, $user, $options);
				if (false === $lastRecord) {
					$modelRec->add($site, $oApp, $user, (empty($posted->referrer) ? '' : $posted->referrer));
				} else if ($lastRecord->enroll_at === '0') {
					$updated = array(
						'enroll_at' => time(),
					);
					!empty($posted->referrer) && $updated['referrer'] = $posted->referrer;
					$modelRec->update('xxt_enroll_record', $updated, "enroll_key='$lastRecord->enroll_key'");
				}
			}

			/* 计算打开哪个页面 */
			if (empty($page)) {
				$oOpenPage = $this->_defaultPage($oApp, false, $ignoretime);
			} else {
				$modelPage = $this->model('matter\enroll\page');
				$oOpenPage = $modelPage->byName($oApp->id, $page);
			}
			if (empty($oOpenPage)) {
				return new \ResponseError('页面不存在');
			}
			$params['page'] = $oOpenPage;

			/* 是否需要返回登记记录 */
			if (($oOpenPage->type === 'I' && $newRecord !== 'Y') || $page === 'remark') {
				if (empty($ek)) {
					if ($oApp->open_lastroll === 'Y') {
						/* 获得最后一条登记数据。记录有可能未进行过数据填写 */
						$options = [
							'fields' => '*',
						];
						$lastRecord = $modelRec->lastByUser($oApp, $user, $options);
						$params['record'] = $lastRecord;
					}
				} else {
					$record = $modelRec->byId($ek);
					$params['record'] = $record;
				}
			}
		} else if ($page === 'remark' && !empty($ek)) {
			$record = $modelRec->byId($ek);
			$params['record'] = $record;
		}

		return new \ResponseData($params);
	}
	/**
	 * 获得指定坐标对应的地址名称
	 *
	 * 没有指定位置信息时通过日志获取当前用户最后一次发送的位置
	 */
	public function locationGet_action($siteid, $lat = '', $lng = '') {
		$geo = array();
		if (empty($lat) || empty($lat)) {
			$user = $this->getUser($siteid);
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
	 * 根据邀请用户数的排名
	 */
	public function rankByFollower_action($siteid, $appid) {
		$user = $this->who;
		$rank = $this->modelApp->rankByFollower($siteid, $appid, $user->userid);

		return new \ResponseData(array('rank' => $rank));
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