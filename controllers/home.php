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

		$param = [
			'platform' => $platform,
		];

		return new \ResponseData($param);
	}
	/**
	 *
	 */
	public function listSite_action() {
		$modelHome = $this->model('site\home');

		$result = $modelHome->atHome();

		return new \ResponseData($result);
	}
	/**
	 *
	 */
	public function listTemplate_action() {
		$modelTpl = $this->model('template\shop');

		$templates = $modelTpl->atHome();

		return new \ResponseData($templates);
	}
	/**
	 *
	 */
	public function listApp_action() {
		$modelHome = $this->model('matter\home');

		$result = $modelHome->atHome();

		return new \ResponseData($result);
	}
	/**
	 *
	 */
	public function listArticle_action() {
		$modelHome = $this->model('matter\home');

		$result = $modelHome->atHomeArticle();

		return new \ResponseData($result);
	}
}