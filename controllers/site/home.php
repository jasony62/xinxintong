<?php
namespace site;

require_once dirname(__FILE__) . '/base.php';
/**
 * 团队站点首页访问控制器
 */
class home extends base {
	/**
	 *
	 */
	public function index_action($site) {
		$modelSite = $this->model('site');
		$site = $modelSite->byId(
			$site,
			['fields' => 'name']
		);
		if ($site) {
			\TPL::assign('title', $site->name);
			\TPL::output('/site/home');
			exit;
		} else {
			$this->outputInfo('指定的对象不存在');
		}
	}
	/**
	 * 获得团队站点的定义
	 *
	 * @param string $site
	 */
	public function get_action($site) {
		$modelSite = $this->model('site');

		$oSite = $modelSite->byId(
			$site,
			['fields' => '*', 'cascaded' => 'home_page_name']
		);
		if ($oSite) {
			/* 轮播图片 */
			if (!empty($oSite->home_carousel)) {
				$oSite->home_carousel = json_decode($oSite->home_carousel);
			}
			$modelWay = $this->model('site\fe\way');
			$siteUser = $modelWay->who($site);
			/* 团队是否已经被当前用户关注 */
			$oSite->_subscribed = 'N';
			if (!empty($siteUser->loginExpire)) {
				$modelSite = $this->model('site');
				if ($rel = $modelSite->isSubscribed($siteUser->uid, $oSite->id)) {
					if ($rel->subscribe_at > 0) {
						$oSite->_subscribed = 'Y';
					}
				}
			}
		}

		return new \ResponseData($oSite);
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
}