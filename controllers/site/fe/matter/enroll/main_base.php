<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 记录活动
 */
class main_base extends base {
	/**
	 *
	 */
	protected $modelApp;
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->modelApp = $this->model('matter\enroll');
	}
	/**
	 *
	 */
	protected function _outputPage($app, $page = '', $rid = '', $ek = null, $topic = null, $ignoretime = 'N') {
		$oApp = $this->modelApp->byId($app, ['cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			$this->outputError('指定的记录活动不存在，请检查参数是否正确');
		}
		if (empty($oApp->appRound)) {
			$this->outputError('【' . $oApp->title . '】没有可用的填写轮次，请检查');
		}

		// 处理输出页面信息
		$outputPageInfo = $this->_getOutputPageInfo($oApp, $page, $rid, $ek, $topic, $ignoretime);
		if ($outputPageInfo[0] === false) {
			$this->outputError($outputPageInfo[1]);
		}
		$outputPage = $outputPageInfo[1];
		$page = $outputPage->name;
		// 页面是否开放
		if (!$this->_checkOpenRule($oApp, $page)) {
			$this->outputError('页面未开放, 请联系系统管理员');
		}

		/* 检查是否需要第三方社交帐号OAuth */
		if (!$this->afterSnsOAuth()) {
			$this->requireSnsOAuth($oApp);
		}

		$oUser = $this->who;
		// 检查进入活动规则
		$this->checkEntryRule($oApp, true, $oUser, $page);
		// 记录日志
		if (in_array($page, ['topic', 'repos', 'cowork'])) {
			$this->_pageReadlog($oApp, $page, $rid, $ek, $topic);
		}

		/* 返回记录活动页面 */
		$customViewName = TMS_APP_VIEW_NAME;
		if (isset($oUser->unionid)) {
			$oAccount = $this->model('account')->byId($oUser->unionid, ['cascaded' => ['group']]);
			if (isset($oAccount->group->view_name) && $oAccount->group->view_name !== TMS_APP_VIEW_NAME) {
				$customViewName = $oAccount->group->view_name;
			}
		}

		\TPL::assign('title', $outputPage->title);
		\TPL::output($outputPage->url, ['customViewName' => $customViewName]);
		exit;
	}
	/**
	 *
	 */
	private function _checkOpenRule($oApp, $page) {
		switch ($page) {
			case 'kanban':
				if (empty($oApp->scenarioConfig->can_kanban) || $oApp->scenarioConfig->can_kanban !== 'Y') {
					return false;
				}
				break;
			case 'event':
				if (empty($oApp->scenarioConfig->can_action) || $oApp->scenarioConfig->can_action !== 'Y') {
					return false;
				}
				break;
			case 'stat':
				if (empty($oApp->scenarioConfig->can_stat) || $oApp->scenarioConfig->can_stat !== 'Y') {
					return false;
				}
				break;
			case 'rank':
				if (empty($oApp->scenarioConfig->can_rank) || $oApp->scenarioConfig->can_rank !== 'Y') {
					return false;
				}
				break;
		}

		return true;
	}
	/**
	 * 处理输出页面信息
	 */
	private function _getOutputPageInfo($oApp, $page, $rid, $ek, $topic, $ignoretime) {
		$outputTitle = '';
		$outputUrl = '/site/fe/matter/enroll/';
		if (empty($page)) {
			/* 计算打开哪个页面 */
			$oOpenPage = $this->_defaultPage($oApp, $rid, true, $ignoretime);
			$page = $oOpenPage->name;
		}
		if (in_array($page, ['cowork', 'share', 'score', 'repos', 'topic'])) {
			if ($page === 'topic' && empty($topic)) {
				return [false, '参数不完整，无法访问专题页'];
			}
			if (in_array($page, ['topic', 'share']) && !empty($topic)) {
				$modelTop = $this->model('matter\enroll\topic', $oApp);
				$oTopic = $modelTop->byId($topic, ['fields' => 'id,state,title']);
				if ($oTopic && $oTopic->state === '1') {
					$outputTitle = $oTopic->title . '|';
				} else {
					return [false, '专题页已删除'];
				}
			} else if (in_array($page, ['cowork', 'share']) && !empty($ek)) {
				$modelRec = $this->model('matter\enroll\record');
				$oRecord = $modelRec->byId($ek, ['fields' => 'id,state']);
				if ($oRecord && $oRecord->state === '1') {
					$outputTitle = '记录' . $oRecord->id . '|';
				} else {
					return [false, '记录已删除'];
				}
			}
			$outputUrl .= $page;
		} else if (in_array($page, ['task', 'kanban', 'event'])) {
			$outputUrl .= 'activities';
		} else if (in_array($page, ['rank', 'votes', 'marks', 'stat'])) {
			$outputUrl .= 'summary';
		} else if (in_array($page, ['user', 'favor'])) {
			$outputUrl .= 'people';
		} else {
			$oOpenPage = $this->model('matter\enroll\page')->byName($oApp, $page);
			if (empty($oOpenPage)) {
				return [false, '没有可访问的页面'];
			}
			$page = $oOpenPage->name;
			if ($oOpenPage->type === 'I') {
				$outputUrl .= 'input';
			} else if ($oOpenPage->type === 'V') {
				$outputUrl .= 'view';
			} else {
				return [false, '没有可访问的页面'];
			}
		}

		$outputPage = new \stdClass;
		$outputPage->name = $page;
		$outputPage->title = $outputTitle . $oApp->title;
		$outputPage->url = $outputUrl;

		return [true, $outputPage];
	}
	/**
	 * 当前用户的缺省页面
	 *
	 * 1、如果没有登记过，根据设置的进入规则获得指定页面
	 * 2、如果已经登记过，且指定了登记过访问页面，进入指定的页面
	 * 3、如果已经登记过，且没有指定登记过访问页面，进入第一个查看页
	 */
	protected function _defaultPage($oApp, $rid = '', $redirect = false, $ignoretime = 'N') {
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
			$userEnrolled = $modelRec->lastByUser($oApp, $oUser, ['state' => '1', 'rid' => $rid]);
			if ($userEnrolled) {
				if (empty($oApp->enrolled_entry_page)) {
					$pages = $modelPage->byApp($oApp->id);
					foreach ($pages as $p) {
						if ($p->type === 'V') {
							$oOpenPage = $modelPage->byId($oApp, $p->id);
							break;
						}
					}
				} else {
					$oOpenPage = $modelPage->byName($oApp, $oApp->enrolled_entry_page);
				}
			}
		}

		if ($oOpenPage === null) {
			// 根据进入规则确定进入页面
			$aResult = $this->checkEntryRule($oApp, $redirect);
			if (true === $aResult[0]) {
				if (!empty($aResult[1])) {
					$oOpenPage = $modelPage->byName($oApp, $aResult[1]);
				}
			}
		}

		if ($oOpenPage === null) {
			if ($redirect === true) {
				$this->outputError('没有获得活动的默认页面，请联系活动管理员解决。');
			}
		}

		return $oOpenPage;
	}
	/**
	 *
	 */
	protected function _pageReadlog($oApp, $page, $rid = '', $ek = null, $topic = null) {
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
			$creater = $this->model('matter\enroll\topic', $oApp)->byId($topic, ['fields' => 'userid uid,nickname']);
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
	 * 记录活动是否可用
	 *
	 * @param object $app 记录活动
	 */
	protected function _isValid(&$oApp) {
		$tipPage = false;
		$current = time();
		if ($oApp->start_at != 0 && $current < $oApp->start_at) {
			if (empty($oApp->before_start_page)) {
				return [false, '【' . $oApp->title . '】没有开始'];
			} else {
				$tipPage = $oApp->before_start_page;
			}
		} else if ($oApp->end_at != 0 && $current > $oApp->end_at) {
			if (empty($oApp->after_end_page)) {
				return [false, '【' . $oApp->title . '】已经结束'];
			} else {
				$tipPage = $oApp->after_end_page;
			}
		}
		if ($tipPage !== false) {
			$modelPage = $this->model('matter\enroll\page');
			$oOpenPage = $modelPage->byName($oApp, $tipPage);
			return [false, $oOpenPage];
		}

		return [true];
	}
}