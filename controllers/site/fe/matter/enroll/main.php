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
	 * 活动是否只向会员开放，如果是要求先成为会员，否则允许直接
	 * 如果已经报过名如何判断？
	 * 如果已经是会员，则可以查看和会员的关联
	 * 如果不是会员，临时分配一个key，保存在cookie中，允许重新报名
	 *
	 * $siteid 因为活动有可能来源于父账号，因此需要指明活动是在哪个公众号中进行的
	 * $appid
	 * $page 要进入活动的哪一页
	 * $ek 登记记录的id
	 * $shareid 谁进行的分享
	 * $mocker 用于测试，模拟访问用户
	 * $code OAuth返回的code
	 *
	 */
	public function index_action($site, $app, $shareby = '', $page = '', $ek = '', $ignoretime = 'N') {
		empty($site) && $this->outputError('没有指定当前站点的ID');
		empty($app) && $this->outputError('登记活动ID为空');

		$app = $this->modelApp->byId($app, ['cascaded' => 'Y']);
		if ($app === false) {
			$this->outputError('指定的登记活动不存在，请检查参数是否正确');
		}
		if (!$this->afterSnsOAuth()) {
			/* 检查是否需要第三方社交帐号OAuth */
			$this->_requireSnsOAuth($site, $app);
		}
		/* 判断活动的开始结束时间。允许指定参数绕过限制，应该改为更严格的检查 */
		if (empty($page) && $ignoretime === 'N') {
			$this->_isValid($app);
		}
		/* 计算打开哪个页面 */
		if (empty($page)) {
			$oPage = $this->_defaultPage($site, $app, true);
		} else {
			foreach ($app->pages as $p) {
				if ($p->name === $page) {
					$oPage = &$p;
					break;
				}
			}
		}
		empty($oPage) && $this->outputError('没有可访问的页面');

		/* 记录日志，完成前置活动再次进入的情况不算 */
		//$this->modelApp->update("update xxt_enroll set read_num=read_num+1 where id='$app->id'");

		/* 返回登记活动页面 */
		\TPL::assign('title', $app->title);
		if ($oPage->type === 'I') {
			\TPL::output('/site/fe/matter/enroll/input');
		} else if ($oPage->type === 'V') {
			\TPL::output('/site/fe/matter/enroll/view');
		} else if ($oPage->type === 'L') {
			\TPL::output('/site/fe/matter/enroll/list');
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
				$this->outputError('【' . $app->title . '】没有开始', $app->title);
			} else {
				$tipPage = $app->before_start_page;
			}
		} else if ($app->end_at != 0 && $current > $app->end_at) {
			if (empty($app->after_end_page)) {
				$this->outputError('【' . $app->title . '】已经结束', $app->title);
			} else {
				$tipPage = $app->after_end_page;
			}
		}
		if ($tipPage !== false) {
			$mapPages = array();
			foreach ($app->pages as &$p) {
				$mapPages[$p->name] = $p;
			}
			$oPage = $mapPages[$tipPage];
			$modelPage = $this->model('matter\enroll\page');
			$oPage = $modelPage->byId($app->id, $oPage->id);
			!empty($oPage->html) && \TPL::assign('body', $oPage->html);
			!empty($oPage->css) && \TPL::assign('css', $oPage->css);
			!empty($oPage->js) && \TPL::assign('js', $oPage->js);
			\TPL::assign('title', $app->title);
			\TPL::output('info');
			exit;
		}
	}
	/**
	 * 当前用户的缺省页面
	 *
	 * 1、如果没有登记过，根据设置的进入规则获得指定页面
	 * 2、如果已经登记过，且指定了登记过访问页面，进入指定的页面
	 * 3、如果已经登记过，且没有指定登记过访问页面，进入第一个查看页
	 */
	private function _defaultPage($site, &$app, $redirect = false) {
		$user = $this->who;
		$oPage = null;

		$hasEnrolled = $this->modelApp->userEnrolled($site, $app, $user);

		// 根据登记状态确定进入页面
		if ($hasEnrolled) {
			if (empty($app->enrolled_entry_page)) {
				foreach ($app->pages as $p) {
					if ($p->type === 'V') {
						$oPage = $p;
						break;
					}
				}
			} else {
				$page = $app->enrolled_entry_page;
			}
		}

		if ($oPage === null) {
			if (!isset($page)) {
				// 根据进入规则确定进入页面
				$page = $this->checkEntryRule($site, $app, $user, $redirect);
			}

			foreach ($app->pages as $p) {
				if ($p->name === $page) {
					$oPage = $p;
					break;
				}
			}
		}

		if ($oPage === null) {
			if ($redirect === true) {
				$this->outputError('指定的页面[' . $page . ']不存在');
				exit;
			}
		}

		return $oPage;
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
	public function get_action($site, $app, $rid = null, $page = null, $ek = null, $newRecord = null) {
		$params = array();

		/* 登记活动定义 */
		$app = $this->modelApp->byId($app, array('cascaded' => 'Y'));
		$params['app'] = &$app;
		/*站点页面设置*/
		if ($app->use_site_header === 'Y' || $app->use_site_footer === 'Y') {
			$params['site'] = $this->model('site')->byId(
				$site,
				['cascaded' => 'header_page_name,footer_page_name']
			);
		}
		/*项目页面设置*/
		if ($app->use_mission_header === 'Y' || $app->use_mission_footer === 'Y') {
			if ($app->mission_id) {
				$params['mission'] = $this->model('matter\mission')->byId(
					$app->mission_id,
					['cascaded' => 'header_page_name,footer_page_name']
				);
			}
		}
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
		/* 计算打开哪个页面 */
		if (empty($page)) {
			$oPage = $this->_defaultPage($site, $app, $user);
		} else {
			foreach ($app->pages as $p) {
				if ($p->name === $page) {
					$oPage = &$p;
					break;
				}
			}
		}
		if (empty($oPage)) {
			return new \ResponseError('页面不存在');
		}
		$modelPage = $this->model('matter\enroll\page');
		$oPage = $modelPage->byId($app->id, $oPage->id, 'Y');
		$params['page'] = $oPage;

		/* 自动登记 */
		$hasEnrolled = $this->modelApp->hasEnrolled($site, $app->id, $user);
		if (!$hasEnrolled && $app->can_autoenroll === 'Y' && $oPage->autoenroll_onenter === 'Y') {
			$modelRec = $this->model('matter\enroll\record');
			$options = [
				'fields' => 'enroll_key,enroll_at',
			];
			$lastRecord = $modelRec->getLast($site, $$app->id, $user, $options);
			if (false === $lastRecord) {
				$modelRec->add($site, $app, $user, (empty($posted->referrer) ? '' : $posted->referrer));
			} else if ($lastRecord->enroll_at === '0') {
				$updated = array(
					'enroll_at' => time(),
				);
				!empty($posted->referrer) && $updated['referrer'] = $posted->referrer;
				$modelRec->update('xxt_enroll_record', $updated, "enroll_key='$lastRecord->enroll_key'");
			}
		}

		if ($app->multi_rounds === 'Y') {
			$params['activeRound'] = $this->model('matter\enroll\round')->getLast($site, $app->id);
		}

		/*登记记录*/
		if ($oPage->type === 'I' && $newRecord !== 'Y' && !empty($ek)) {
			$modelRec = $this->model('matter\enroll\record');
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