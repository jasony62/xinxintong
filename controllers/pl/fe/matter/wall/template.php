<?php
namespace pl\fe\matter\wall;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 *
 */
class template extends \pl\fe\matter\base {
	/**
	 * 返回信息墙模板
	 */
	public function list_action() {
		$templates = file_get_contents(TMS_APP_TEMPLATE . '/site/op/matter/wall/scenario/manifest.json');
		$templates = preg_replace('/\t|\r|\n/', '', $templates);
		$templates = json_decode($templates);

		return new \ResponseData($templates);
	}
	/**
	 *
	 */
	public function config_action($scenario, $template) {
		$templateDir = TMS_APP_TEMPLATE . '/site/op/matter/wall/scenario/' . $scenario . '/templates/' . $template;
		$oConfig = null;
		$oConfig = file_get_contents($templateDir . '/config.json');
		$oConfig = preg_replace('/\t|\r|\n/', '', $oConfig);
		$oConfig = json_decode($oConfig);
		if (json_last_error()) {
			$oConfig = json_last_error_msg();
			return new \ResponseError($oConfig);
		}

		return new \ResponseData($oConfig);
	}
}