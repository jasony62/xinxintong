<?php
/**
 * 平台首页
 */
class home extends TMS_CONTROLLER {
	/**
	 *
	 */
	public function get_access_rule() {
		$ruleAction = [
			'rule_type' => 'black',
		];

		return $ruleAction;
	}
	/**
	 *
	 */
	public function index_action($template = 'basic') {
		$current = time();
		$modelPl = $this->model('platform');
		$platform = $modelPl->get();
		$modelCode = \TMS_APP::M('code\page');
		$template = $modelPl->escape($template);
		//自动更新主页页面
		if ($platform->autoup_homepage === 'Y' && !empty($template)) {
			$home_page = $modelCode->lastPublishedByName('platform', $platform->home_page_name, ['fields' => 'id,create_at']);
			$templatePageMTimes = $this->_getPageMTime('home', $template);

			if ($templatePageMTimes['upAtHtml'] > $home_page->create_at || $templatePageMTimes['upAtCss'] > $home_page->create_at || $templatePageMTimes['upAtJs'] > $home_page->create_at) {
				//更新主页页面
				$data = $this->_makePage('home', $template);
				$data['create_at'] = $current;
				$data['modify_at'] = $current;
				$rst = $this->model('code\page')->modify($platform->{'home_page_id'}, $data);
			}
			$home_page = '';
			$data = '';
		}
		if ($platform->autoup_sitepage === 'Y' && !empty($template)) {
			$site_page = $modelCode->lastPublishedByName('platform', $platform->site_page_name, ['fields' => 'id,create_at']);
			$templatePageMTimes = $this->_getPageMTime('site', $template);

			if ($templatePageMTimes['upAtHtml'] > $site_page->create_at || $templatePageMTimes['upAtCss'] > $site_page->create_at || $templatePageMTimes['upAtJs'] > $site_page->create_at) {
				//更新主页页面
				$data = $this->_makePage('site', $template);
				$data['create_at'] = $current;
				$data['modify_at'] = $current;
				$rst = $this->model('code\page')->modify($platform->{'site_page_id'}, $data);
			}
			$site_page = '';
			$data = '';
		}
		if ($platform->autoup_templatepage === 'Y' && !empty($template)) {
			$template_page = $modelCode->lastPublishedByName('platform', $platform->template_page_name, ['fields' => 'id,create_at']);
			$templatePageMTimes = $this->_getPageMTime('template', $template);

			if ($templatePageMTimes['upAtHtml'] > $template_page->create_at || $templatePageMTimes['upAtCss'] > $template_page->create_at || $templatePageMTimes['upAtJs'] > $template_page->create_at) {
				//更新主页页面
				$data = $this->_makePage('template', $template);
				$data['create_at'] = $current;
				$data['modify_at'] = $current;
				$rst = $this->model('code\page')->modify($platform->{'template_page_id'}, $data);
			}
			$template_page = '';
			$data = '';
		}

		TPL::output('/home');
		exit;
	}
	/**
	 * 通过系统内置模板生成页面
	 */
	private function &_makePage($name, $template) {
		if (file_exists(TMS_APP_TEMPLATE . '/pl/be/' . $name)) {
			$templateDir = TMS_APP_TEMPLATE . '/pl/be/' . $name;
		} else {
			$templateDir = TMS_APP_TEMPLATE_DEFAULT . '/pl/be/' . $name;
		}
		$data = array(
			'html' => file_get_contents($templateDir . '/' . $template . '.html'),
			'css' => file_get_contents($templateDir . '/' . $template . '.css'),
			'js' => file_get_contents($templateDir . '/' . $template . '.js'),
		);

		return $data;
	}
	/**
	 * 获得系统内置模板的修改时间
	 */
	private function &_getPageMTime($name, $template) {
		if (file_exists(TMS_APP_TEMPLATE . '/pl/be/' . $name)) {
			$templateDir = TMS_APP_TEMPLATE . '/pl/be/' . $name;
		} else {
			$templateDir = TMS_APP_TEMPLATE_DEFAULT . '/pl/be/' . $name;
		}
		$templatePageMTimes = array(
			'upAtHtml' => filemtime($templateDir . '/' . $template . '.html'),
			'upAtCss' => filemtime($templateDir . '/' . $template . '.css'),
			'upAtJs' => filemtime($templateDir . '/' . $template . '.js'),
		);

		return $templatePageMTimes;
	}
	/**
	 *
	 */
	public function get_action() {
		$platform = $this->model('platform')->get(['cascaded' => 'home_page,template_page,site_page']);
		if (!empty($platform->home_carousel)) {
			$platform->home_carousel = json_decode($platform->home_carousel);
		}
		if (!empty($platform->home_nav)) {
			$platform->home_nav = json_decode($platform->home_nav);
		}

		$param = [
			'platform' => $platform,
		];

		return new \ResponseData($param);
	}
	/**
	 * 首页推荐团队列表
	 *
	 * @param string $userType 站点用户还是团队管理员用户
	 *
	 */
	public function listSite_action($page = 1, $size = 8) {
		$modelHome = $this->model('site\home');

		$options = [];
		$options['page']['at'] = $page;
		$options['page']['size'] = $size;
		$result = $modelHome->atHome($options);
		if ($result->total) {
			$modelWay = $this->model('site\fe\way');
			$siteUser = $modelWay->who('platform');
			if (isset($siteUser->loginExpire)) {
				$modelSite = $this->model('site');
				foreach ($result->sites as &$site) {
					if ($rel = $modelSite->isSubscribed($siteUser->uid, $site->siteid)) {
						$site->_subscribed = $rel->subscribe_at ? 'Y' : 'N';
					}
				}
			}
		}

		return new \ResponseData($result);
	}
	/**
	 *
	 */
	public function listTemplate_action() {
		$modelTpl = $this->model('matter\template');

		$templates = $modelTpl->atHome();

		return new \ResponseData($templates);
	}
	/**
	 *
	 */
	public function listApp_action($page = 1, $size = 8) {
		$modelHome = $this->model('matter\home');

		$options = [];
		$options['page']['at'] = $page;
		$options['page']['size'] = $size;
		$result = $modelHome->atHome($options);
		if (count($result->matters)) {
			foreach ($result->matters as &$matter) {
				$matter->url = $this->model('matter\\' . $matter->matter_type)->getEntryUrl($matter->siteid, $matter->matter_id);
			}
		}

		return new \ResponseData($result);
	}
	/**
	 *
	 */
	public function listChannel_action($page = 1, $size = 8) {
		$modelHome = $this->model('matter\home');

		$options = [];
		$options['page']['at'] = $page;
		$options['page']['size'] = $size;
		$result = $modelHome->atHomeChannel($options);
		if (count($result->matters)) {
			foreach ($result->matters as &$matter) {
				$matter->url = $this->model('matter\channel')->getEntryUrl($matter->siteid, $matter->matter_id);
			}
		}

		return new \ResponseData($result);
	}
	/**
	 *
	 */
	public function listArticle_action($page = 1, $size = 8) {
		$modelHome = $this->model('matter\home');

		$options = [];
		$options['page']['at'] = $page;
		$options['page']['size'] = $size;
		$result = $modelHome->atHomeArticle($options);
		if (count($result->matters)) {
			foreach ($result->matters as &$matter) {
				$matter->url = $this->model('matter\article')->getEntryUrl($matter->siteid, $matter->matter_id);
			}
		}

		return new \ResponseData($result);
	}
	/**
	 *获取置顶活动
	 */
	public function listMatterTop_action($type = 'article', $page = 1, $size = 3) {
		$modelHome = $this->model('matter\home');
		$options = [];
		$options['page']['at'] = $page;
		$options['page']['size'] = $size;
		$options['type'] = $type;
		$result = $modelHome->atHomeTop($options);
		if (count($result->matters)) {
			foreach ($result->matters as &$matter) {
				$matter->url = $this->model('matter\\' . $matter->matter_type)->getEntryUrl($matter->siteid, $matter->matter_id);
			}
		}

		return new \ResponseData($result);
	}
	/**
	 * 获得当前登录账号的用户信息
	 */
	private function &_accountUser() {
		$account = \TMS_CLIENT::account();
		if ($account) {
			$user = new \stdClass;
			$user->id = $account->uid;
			$user->name = $account->nickname;
			$user->src = 'A';

		} else {
			$user = false;
		}
		return $user;
	}
}