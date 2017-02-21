<?php
namespace site\fe\matter\signin;

include_once dirname(__FILE__) . '/base.php';
/**
 * 签到活动
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
		$this->modelApp = $this->model('matter\signin');
	}
	/**
	 * 返回活动页
	 *
	 * @param string $site 因为活动有可能来源于父账号，因此需要指明活动是在哪个公众号中进行的
	 * @param string $app
	 * @param string $round 指定签到轮次
	 * @param $page 要进入活动的哪一页面
	 *
	 */
	public function index_action($site, $app, $round = null, $page = '', $ignoretime = 'N') {
		empty($site) && $this->outputError('没有指定站点ID');
		empty($app) && $this->outputError('签到活动ID为空');

		$app = $this->modelApp->byId($app, ['cascade' => 'N']);
		if ($app === false) {
			$this->outputError('指定的签到活动不存在，请检查参数是否正确');
		}
		if (!$this->afterSnsOAuth()) {
			/* 检查是否需要第三方社交帐号OAuth */
			$this->_requireSnsOAuth($site, $app);
		}
		if ($ignoretime === 'N') {
			$activeRound = $this->model('matter\signin\round')->getActive($site, $app->id);
			if (!$activeRound) {
				$this->outputError('签到还没有开始', $app->title);
			} else if (!empty($round) && $round !== $activeRound->rid) {
				$this->outputError('您签到的场次或时间不正确', $app->title);
			}
		}
		/* 计算打开哪个页面 */
		if (empty($page)) {
			/*没有指定页面*/
			$oPage = $this->_defaultPage($site, $app, true, isset($activeRound) ? $activeRound : null);
		} else {
			$oPage = $this->model('matter\signin\page')->byName($app->id, $page);
		}
		empty($oPage) && $this->outputError('没有可访问的页面');

		/* 返回签到活动页面 */
		\TPL::assign('title', $app->title);
		if ($oPage->type === 'V') {
			\TPL::output('/site/fe/matter/signin/view');
		} elseif ($oPage->type === 'I') {
			\TPL::output('/site/fe/matter/signin/signin');
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
	 * 当前用户进入的缺省页面
	 */
	private function &_defaultPage($siteId, &$app, $redirect = false, $round = null) {
		$page = $this->checkEntryRule($siteId, $app, $redirect, $round);
		$oPage = $this->model('matter\signin\page')->byName($app->id, $page);
		if (empty($oPage)) {
			if ($redirect === true) {
				$this->outputError('指定的页面[' . $page . ']不存在');
				exit;
			}
		}

		return $oPage;
	}
	/**
	 * 返回签到活动定义
	 *
	 * @param string $site
	 * @param string $app
	 * @param string $page page's name
	 *
	 */
	public function get_action($site, $app, $page = null) {
		$params = [];

		// 签到活动定义
		$signinApp = $this->modelApp->byId($app, ['cascaded' => 'N']);
		$params['app'] = &$signinApp;

		// 当前访问用户的基本信息
		$user = $this->who;
		$params['user'] = $user;

		// 当前轮次
		$activeRound = $this->model('matter\signin\round')->getActive($site, $signinApp->id);
		$params['activeRound'] = $activeRound;

		// 打开哪个页面？
		if (empty($page)) {
			$oPage = $this->_defaultPage($site, $signinApp, false, $activeRound);
		} else {
			$oPage = $this->model('matter\signin\page')->byName($signinApp->id, $page);
		}
		if (empty($oPage)) {
			return new \ResponseError('页面不存在');
		}
		$params['page'] = $oPage;

		// 站点页面设置
		if ($signinApp->use_site_header === 'Y' || $signinApp->use_site_footer === 'Y') {
			$params['site'] = $this->model('site')->byId(
				$site,
				['cascaded' => 'header_page_name,footer_page_name']
			);
		}
		// 项目页面设置
		if ($signinApp->use_mission_header === 'Y' || $signinApp->use_mission_footer === 'Y') {
			if ($signinApp->mission_id) {
				$params['mission'] = $this->model('matter\mission')->byId(
					$signinApp->mission_id,
					['cascaded' => 'header_page_name,footer_page_name']
				);
			}
		}

		// 签到记录
		$newForm = false;
		if ($oPage->type === 'I') {
			$options = [
				'fields' => '*',
				'cascaded' => 'Y',
			];
			$modelRec = $this->model('matter\signin\record');
			if (false === ($userRecord = $modelRec->byUser($user, $site, $signinApp, $options))) {
				// 如果关联了报名记录，从报名记录中获得登记信息
				if (!empty($signinApp->enroll_app_id)) {
					$userRecord = $this->_recordByEnroll($signinApp, $user);
				}
			}
			$params['record'] = $userRecord;
		}

		return new \ResponseData($params);
	}
	/**
	 * 从关联的登记活动中获得匹配的数据
	 */
	private function _recordByEnroll(&$signinApp, &$user) {
		$modelEnlRec = $this->model('matter\enroll\record');

		$records = $modelEnlRec->byUser($signinApp->enroll_app_id, $user);
		if (count($records)) {
			$signinRecord = new \stdClass;
			foreach ($records as $record) {
				if ($record->verified === 'Y') {
					$signinRecord->data = json_decode($record->data);
					return $signinRecord;
				}
			}
		}

		return false;
	}
}