<?php
namespace site;
/**
 * 站点首页访问控制器
 */
class home extends \TMS_CONTROLLER {
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
	public function index_action($site) {
		$modelSite = $this->model('site');
		$site = $modelSite->byId(
			$site,
			['fields' => 'name']
		);
		\TPL::assign('title', $site->name);
		\TPL::output('/site/home');
		exit;
	}
	/**
	 * 获得页面定义
	 */
	public function get_action($site) {
		$modelSite = $this->model('site');

		$site = $modelSite->byId(
			$site,
			['fields' => '*', 'cascaded' => 'home_page_name']
		);
		if ($site) {
			/* 轮播图片 */
			if (!empty($site->home_carousel)) {
				$site->home_carousel = json_decode($site->home_carousel);
			}
			/* 当前用户关注状态 */
			if ($user = $this->_accountUser()) {
				$mySites = $modelSite->byUser($user->id);
				foreach ($mySites as $mySite) {
					if ($modelSite->isSubscribedBySite($site->id, $mySite->id)) {
						$site->_subscribed = 'Y';
						break;
					}
				}
			}
		}

		return new \ResponseData($site);
	}
	/**
	 *
	 */
	public function listTemplate_action($site) {
		$modelTpl = $this->model('matter\template');

		$templates = $modelTpl->atSiteHome($site);

		return new \ResponseData($templates);
	}
	/**
	 *
	 */
	public function listChannel_action($site) {
		$modelSp = $this->model('site\page');
		$hcs = $modelSp->homeChannelBySite($site);

		return new \ResponseData($hcs);
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