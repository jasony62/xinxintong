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
	public function index_action($site, $template = 'basic') {
		if (empty($site)) {
			$this->outputInfo('参数错误');
		}
		$modelSite = $this->model('site');
		$oSite = $modelSite->byId(
			$site,
			['fields' => 'id,name,home_page_id,home_page_name,autoup_homepage']
		);
		if (false === $oSite) {
			$this->outputInfo('指定的对象不存在');
		}
		//自动更新主页页面
		if ($oSite->autoup_homepage === 'Y' && !empty($template)) {
			$template = $modelSite->escape($template);

			$modelCode = $this->model('code\page');
			$home_page = $modelCode->lastPublishedByName($oSite->id, $oSite->home_page_name, ['fields' => 'id,create_at']);

			$templateDir = file_exists(TMS_APP_TEMPLATE . '/pl/fe/site/page/home') ? TMS_APP_TEMPLATE : TMS_APP_TEMPLATE_DEFAULT;
			$templateDir .= '/pl/fe/site/page/home';

			$templateDirHtml = $templateDir . '/' . $template . '.html';
			$templateDirCss = $templateDir . '/' . $template . '.css';
			$templateDirJs = $templateDir . '/' . $template . '.js';
			$createAtTemplateHtml = filemtime($templateDirHtml);
			$createAtTemplateCss = filemtime($templateDirCss);
			$createAtTemplateJs = filemtime($templateDirJs);

			if ($home_page === false || ($createAtTemplateHtml > $home_page->create_at || $createAtTemplateCss > $home_page->create_at || $createAtTemplateJs > $home_page->create_at)) {
				//更新主页页面
				$current = time();
				$data = $this->_makePage($oSite->id, 'home', $template);
				$data['create_at'] = $current;
				$data['modify_at'] = $current;
				$rst = $this->model('code\page')->modify($oSite->{'home_page_id'}, $data);
			}
		}

		\TPL::assign('title', $oSite->name);
		\TPL::output('/site/home');
		exit;
	}
	/**
	 *
	 */
	private function &_makePage($site, $page, $template) {
		$templateDir = file_exists(TMS_APP_TEMPLATE . '/pl/fe/site/page/home') ? TMS_APP_TEMPLATE : TMS_APP_TEMPLATE_DEFAULT;
		$templateDir .= '/pl/fe/site/page/home';

		$data = array(
			'html' => file_get_contents($templateDir . '/' . $template . '.html'),
			'css' => file_get_contents($templateDir . '/' . $template . '.css'),
			'js' => file_get_contents($templateDir . '/' . $template . '.js'),
		);

		return $data;
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
			/* 团队首页地址 */
			$oSite->homeUrl = APP_PROTOCOL . APP_HTTP_HOST . '/rest/site/home?site=' . $oSite->id;
			/* 轮播图片 */
			if (!empty($oSite->home_carousel)) {
				$oSite->home_carousel = json_decode($oSite->home_carousel);
			}
			/* 团队群二维码 */
			if (!empty($oSite->home_qrcode_group)) {
				$oSite->home_qrcode_group = json_decode($oSite->home_qrcode_group);
			}
			/* 用户注册信息 */
			$modelWay = $this->model('site\fe\way');
			$siteUser = $modelWay->who($site);
			$cookieRegUser = $modelWay->getCookieRegUser();
			if ($cookieRegUser) {
				if (isset($cookieRegUser->loginExpire)) {
					$siteUser->unionid = $cookieRegUser->unionid;
				}
			}
			/* 团队是否已经被当前用户关注 */
			$oSite->_subscribed = 'N';
			if (!empty($siteUser->unionid)) {
				$modelSite = $this->model('site');
				if ($rel = $modelSite->isSubscribed($siteUser->unionid, $oSite->id)) {
					if ($rel->subscribe_at > 0) {
						$oSite->_subscribed = 'Y';
					}
				}
			}
			/*关注此团队的团队数*/
			$q = [
				'count(*)',
				'xxt_site_friend',
				"siteid='$site' and subscribe_at<>0",
			];
			$oSite->subFriend_num = $modelSite->query_val_ss($q);
			/*关注此团队的个人数*/
			$q[1] = 'xxt_site_subscriber';
			$oSite->subUser_num = $modelSite->query_val_ss($q);
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
	public function listChannel_action($site, $homeGroup = null) {
		$modelSp = $this->model('site\page');
		$options = ['home_group' => $homeGroup];
		$hcs = $modelSp->homeChannelBySite($site, $options);

		return new \ResponseData($hcs);
	}
}