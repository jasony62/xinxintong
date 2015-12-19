<?php
namespace app\enroll\template;

/**
 * 登记活动模板
 */
class base extends \TMS_CONTROLLER {
	/**
	 *
	 */
	protected function &getTemplateDir($scenario, $template) {
		$templateDir = $_SERVER['DOCUMENT_ROOT'] . '/controllers/mp/app/enroll/scenario/' . $scenario . '/templates/' . $template;
		return $templateDir;
	}
	/**
	 *
	 */
	protected function &getConfig($templateDir) {
		$config = file_get_contents($templateDir . '/config.json');
		$config = preg_replace('/\t|\r|\n/', '', $config);
		$config = json_decode($config);
		return $config;
	}
	/**
	 *
	 */
	protected function &getData($templateDir) {
		$data = file_get_contents($templateDir . '/data.json');
		$data = preg_replace('/\t|\r|\n/', '', $data);
		$data = json_decode($data);

		return $data;
	}
	/**
	 * 从模板中获得定义
	 */
	protected function &getPage($templateDir, &$config, $name) {
		$pages = $config->pages;
		if (empty($pages)) {
			return false;
		}
		$target = $pages[0];
		if (!empty($name)) {
			foreach ($pages as $tp) {
				if ($tp->name === $name) {
					$target = $tp;
					break;
				}
			}
		}
		$target->html = file_get_contents($templateDir . '/' . $target->name . '.html');
		$target->css = file_get_contents($templateDir . '/' . $target->name . '.css');
		$target->js = file_get_contents($templateDir . '/' . $target->name . '.js');
		/*填充页面*/
		$matched = array();
		$pattern = '/<!-- begin: generate by schema -->.*<!-- end: generate by schema -->/s';
		if (preg_match($pattern, $target->html, $matched)) {
			$modelPage = $this->model('app\enroll\page');
			if (isset($config->simpleSchema)) {
				$html = $modelPage->htmlBySimpleSchema($config->simpleSchema, $matched[0]);
			} else {
				$html = $modelPage->htmlBySchema($config->schema, $matched[0]);
			}
			$target->html = preg_replace($pattern, $html, $target->html);
		}
		return $target;
	}
}