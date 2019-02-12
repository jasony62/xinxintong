<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 *
 */
class template extends \pl\fe\matter\base {
	/**
	 * 返回记录活动模板
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
		$oConfig = file_get_contents($templateDir . '/config.json');
		$oConfig = preg_replace('/\t|\r|\n/', '', $oConfig);
		$oConfig = json_decode($oConfig);
		if (json_last_error()) {
			$oConfig = json_last_error_msg();
			return new \ResponseError($oConfig);
		}

		if (file_exists($templateDir . '/explaination.html')) {
			$oExplPage = new \stdClass;
			$oExplPage->name = 'explaination';
			$oExplPage->title = '模板说明';
			$oExplPage->type = 'I';
			array_splice($oConfig->pages, 0, 0, [$oExplPage]);
		}

		return new \ResponseData($oConfig);
	}
}