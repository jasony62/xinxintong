<?php
namespace site\op\matter\signin;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/**
 *
 */
class main extends \site\op\base {
	/**
	 *
	 */
	public function index_action($app) {
		$app = $this->model('matter\signin')->byId($app);
		\TPL::assign('title', $app->title);
		\TPL::output('site/op/matter/signin/console');
		exit;
	}
	/**
	 * 返回登记记录
	 *
	 * @param string $siteid
	 * @param string $appid
	 */
	public function get_action($site, $app) {
		$params = array();

		/* 登记活动定义 */
		$modelApp = $this->model('matter\signin');
		$app = $modelApp->byId($app, array('cascaded' => 'Y'), 'Y');
		$params['app'] = &$app;
		/* 页面定义 */
		$templateDir = TMS_APP_TEMPLATE . '/site/op/matter/signin';
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
		$templateDir = TMS_APP_TEMPLATE . '/site/op/matter/signin';
		$templateName = $templateDir . '/basic';

		$page = array(
			'html' => file_get_contents($templateName . '.html'),
			'css' => file_get_contents($templateName . '.css'),
			'js' => file_get_contents($templateName . '.js'),
		);

		return new \ResponseData($page);
	}
}