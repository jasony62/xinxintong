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
		$app = $this->model('app\enroll')->byId($app);
		\TPL::assign('title', $app->title);
		\TPL::output('site/op/matter/enroll/console');
		exit;
	}
	/**
	 * 获得页面定义
	 */
	public function pageGet_action() {
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