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
		$modelPl = $this->model('platform');
		$platform = $modelPl->get();
		//自动更新主页页面
		if($platform->autoup_homepage === 'Y' && !empty($template)){
			$template = $modelPl->escape($template);
			$modelCode = \TMS_APP::M('code\page');
			$home_page = $modelCode->lastPublishedByName('platform', $platform->home_page_name, ['fields' => 'id,create_at']);
			
			$templateDirHtml = TMS_APP_TEMPLATE . '/pl/be/home/' . $template . '.html';
			$templateDirCss = TMS_APP_TEMPLATE . '/pl/be/home/' . $template . '.css';
			$templateDirJs = TMS_APP_TEMPLATE . '/pl/be/home/' . $template . '.js';
			$createAtTemplateHtml = filemtime($templateDirHtml);
			$createAtTemplateCss = filemtime($templateDirCss);
			$createAtTemplateJs = filemtime($templateDirJs);

			if($createAtTemplateHtml > $home_page->create_at || $createAtTemplateCss > $home_page->create_at || $createAtTemplateJs > $home_page->create_at) {
				//更新主页页面
				$current = time();
				$data = $this->_makePage('home', $template);
				$data['create_at'] = $current;
				$data['modify_at'] = $current;
				$rst = $this->model('code\page')->modify($platform->{'home_page_id'}, $data);
			}
		}

		TPL::output('/home');
		exit;
	}
	/**
	 * 通过系统内置模板生成页面
	 */
	private function &_makePage($name, $template) {
		$templateDir = TMS_APP_TEMPLATE . '/pl/be/' . $name;
		$data = array(
			'html' => file_get_contents($templateDir . '/' . $template . '.html'),
			'css' => file_get_contents($templateDir . '/' . $template . '.css'),
			'js' => file_get_contents($templateDir . '/' . $template . '.js'),
		);

		return $data;
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
		$options['page']['at']=$page;
		$options['page']['size']=$size;
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
		$options['page']['at']=$page;
		$options['page']['size']=$size;
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
		$options['page']['at']=$page;
		$options['page']['size']=$size;
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
		$options['page']['at']=$page;
		$options['page']['size']=$size;
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
		$options['page']['at']=$page;
		$options['page']['size']=$size;
		$options['type']=$type;
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