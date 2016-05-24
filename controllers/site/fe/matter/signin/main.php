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
		empty($site) && $this->outputError('没有指定站点ID');
		empty($app) && $this->outputError('签到活动ID为空');

		$app = $this->modelApp->byId($app, array('cascade' => 'Y'));
		if (!$this->afterSnsOAuth()) {
			/* 检查是否需要第三方社交帐号OAuth */
			$this->_requireSnsOAuth($site, $app);
		}
		/* 判断活动是否可用 */
		if ($app->state === '3') {
			$this->outputError('签到已经结束', $app->title);
		}
		if ($ignoretime === 'N') {
			if ($app->state === '1' || !$this->model('matter\signin\round')->getActive($site, $app->id)) {
				$this->outputError('还没有开始签到', $app->title);
			}
		}
		/* 计算打开哪个页面 */
		if (empty($page)) {
			/*没有指定页面*/
			$oPage = $this->_defaultPage($this->who, $site, $app, true);
		} else {
			foreach ($app->pages as $p) {
				if ($p->name === $page) {
					$oPage = &$p;
					break;
				}
			}
		}
		empty($oPage) && $this->outputError('没有可访问的页面');
		/* 记录日志 */
		//$this->logRead($siteid, $user, $app->id, 'signin', $app->title, '');
		/* 返回签到活动页面 */
		\TPL::assign('title', $app->title);
		if ($oPage->type === 'V') {
			\TPL::output('/site/fe/matter/signin/view');
		} else if ($oPage->type === 'S') {
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
		if ($this->userAgent() === 'wx') {
			if (isset($entryRule->wxfan)) {
				if (!isset($this->who->sns->wx)) {
					if ($wxConfig = $this->model('sns\wx')->bySite($siteid)) {
						if ($wxConfig->joined === 'Y') {
							$this->snsOAuth($wxConfig, 'wx');
						}
					}
				}
			}
			if (isset($entryRule->qyfan)) {
				if (!isset($this->who->sns->qy)) {
					if ($qyConfig = $this->model('sns\qy')->bySite($siteid)) {
						if ($qyConfig->joined === 'Y') {
							$this->snsOAuth($qyConfig, 'qy');
						}
					}
				}
			}
		} else if (isset($entryRule->yxfan) && $this->userAgent() === 'yx') {
			if (!isset($this->who->sns->yx)) {
				if ($yxConfig = $this->model('sns\yx')->bySite($siteid)) {
					if ($yxConfig->joined === 'Y') {
						$this->snsOAuth($yxConfig, 'yx');
					}
				}
			}
		}

		return false;
	}
	/**
	 * 当前用户的缺省页面
	 */
	private function &_defaultPage(&$user, $siteId, &$app, $redirect = false) {
		$page = $this->checkEntryRule($user, $siteId, $app, $redirect);
		$oPage = null;
		foreach ($app->pages as $p) {
			if ($p->name === $page) {
				$oPage = $p;
				break;
			}
		}
		if (empty($oPage)) {
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
	 * @param string $page page's name
	 */
	public function get_action($site, $app, $page = null) {
		$params = array();

		/* 签到活动定义 */
		$app = $this->modelApp->byId($app);
		$params['app'] = &$app;
		/*站点页面设置*/
		if ($app->use_site_header === 'Y' || $app->use_site_footer === 'Y') {
			$params['site'] = $this->model('site')->byId(
				$site,
				array('cascaded' => 'header_page_name,footer_page_name')
			);
		}
		/*项目页面设置*/
		if ($app->use_mission_header === 'Y' || $app->use_mission_footer === 'Y') {
			if ($app->mission_id) {
				$params['mission'] = $this->model('matter\mission')->byId(
					$app->mission_id,
					array('cascaded' => 'header_page_name,footer_page_name')
				);
			}
		}
		/* 当前访问用户的基本信息 */
		$user = $this->who;
		$params['user'] = $user;
		/* 打开哪个页面？ */
		if (empty($page)) {
			$oPage = $this->_defaultPage($user, $site, $app);
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
		$modelPage = $this->model('matter\signin\page');
		$oPage = $modelPage->byId($app->id, $oPage->id, 'Y');
		$params['page'] = $oPage;
		$params['activeRound'] = $this->model('matter\signin\round')->getActive($site, $app->id);
		/*登记记录*/
		$newForm = false;
		if ($oPage->type === 'S') {
			$options = array(
				'fields' => '*',
				'cascaded' => 'Y',
			);
			$modelRec = $this->model('matter\signin\record');
			$userRecord = $modelRec->byUser($user, $site, $app, $options);
			$params['record'] = $userRecord;
		}

		return new \ResponseData($params);
	}
}