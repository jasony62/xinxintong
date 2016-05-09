<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 *
 */
class template extends \pl\fe\matter\base {
	/**
	 * 返回登记活动模板
	 */
	public function list_action() {
		$templates = file_get_contents(TMS_APP_TEMPLATE . '/pl/fe/matter/enroll/scenario/manifest.json');
		$templates = preg_replace('/\t|\r|\n/', '', $templates);
		$templates = json_decode($templates);

		return new \ResponseData($templates);
	}
	/**
	 *
	 */
	public function config_action($scenario, $template) {
		$templateDir = TMS_APP_TEMPLATE . '/pl/fe/matter/enroll/scenario/' . $scenario . '/templates/' . $template;
		$config = file_get_contents($templateDir . '/config.json');
		$config = preg_replace('/\t|\r|\n/', '', $config);
		$config = json_decode($config);

		return new \ResponseData($config);
	}
}