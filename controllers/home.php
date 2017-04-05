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
	public function index_action() {
		TPL::output('/home');
		exit;
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
	public function listSite_action() {
		$modelHome = $this->model('site\home');
		$result = $modelHome->atHome();
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
	public function listApp_action() {
		$modelHome = $this->model('matter\home');

		$result = $modelHome->atHome();
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
	public function listArticle_action() {
		$modelHome = $this->model('matter\home');

		$result = $modelHome->atHomeArticle();
		if (count($result->matters)) {
			foreach ($result->matters as &$matter) {
				$matter->url = $this->model('matter\article')->getEntryUrl($matter->siteid, $matter->matter_id);
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