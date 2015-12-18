<?php
namespace mp\app\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 *
 */
class template extends \mp\app\app_base {
	/**
	 * 返回登记活动模板
	 */
	public function list_action() {
		$templates = file_get_contents(dirname(__FILE__) . '/scenario/manifest.json');
		$templates = preg_replace('/\t|\r|\n/', '', $templates);
		$templates = json_decode($templates);

		return new \ResponseData($templates);
	}
	/**
	 *
	 */
	public function config_action($scenario, $template) {
		$templateDir = $_SERVER['DOCUMENT_ROOT'] . '/controllers/mp/app/enroll/scenario/' . $scenario . '/templates/' . $template;
		$config = file_get_contents($templateDir . '/config.json');
		$config = preg_replace('/\t|\r|\n/', '', $config);
		$config = json_decode($config);

		return new \ResponseData($config);
	}
	/**
	 *
	 */
	public function renew_action($scenario, $template, $page) {
		$config = $this->getPostJson();

		$templateDir = $_SERVER['DOCUMENT_ROOT'] . '/controllers/mp/app/enroll/scenario/' . $scenario . '/templates/' . $template;
		$html = file_get_contents($templateDir . '/' . $page . '.html');
		/*填充页面*/
		$matched = [];
		$pattern = '/<!-- begin: generate by schema -->.*<!-- end: generate by schema -->/s';
		if (preg_match($pattern, $html, $matched)) {
			$modelPage = $this->model('app\enroll\page');
			$segment = $modelPage->htmlBySimpleSchema($config->simpleSchema);
			$html = preg_replace($pattern, $segment, $html);
		}

		return new \ResponseData($html);
	}
}