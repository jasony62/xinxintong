<?php
namespace site\op\matter\enroll;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/**
 *
 */
class main extends \site\op\base {
	/**
	 *
	 */
	public function index_action($app) {
		if (!$this->checkAccessToken()) {
			header('HTTP/1.0 500 parameter error:accessToken is invalid.');
			die('提供的令牌无效，或者令牌已经过期！');
		}
		$oApp = $this->model('matter\enroll')->byId($app);
		\TPL::assign('title', $oApp->title);
		\TPL::output('site/op/matter/enroll/console');
		exit;
	}
	/**
	 *
	 *
	 * @param string $siteid
	 * @param string $appid
	 */
	public function get_action($site, $app) {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$params = array();
		/* 登记活动定义 */
		$modelApp = $this->model('matter\enroll');
		$app = $modelApp->byId($app, array('cascaded' => 'Y'), 'Y');
		$params['app'] = &$app;
		/* 页面定义 */
		$templateDir = TMS_APP_TEMPLATE . '/site/op/matter/enroll';
		$templateName = $templateDir . '/basic';

		$page = array(
			'html' => file_get_contents($templateName . '.html'),
			'css' => file_get_contents($templateName . '.css'),
			'js' => file_get_contents($templateName . '.js'),
		);
		$params['page'] = &$page;

		return new \ResponseData($params);
	}
	/**
	 * 获得页面定义
	 */
	public function pageGet_action() {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$templateDir = TMS_APP_TEMPLATE . '/site/op/matter/enroll';
		$templateName = $templateDir . '/basic';

		$page = array(
			'html' => file_get_contents($templateName . '.html'),
			'css' => file_get_contents($templateName . '.css'),
			'js' => file_get_contents($templateName . '.js'),
		);

		return new \ResponseData($page);
	}
}