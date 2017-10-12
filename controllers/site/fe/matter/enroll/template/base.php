<?php
namespace site\fe\matter\enroll\template;

/**
 * 登记活动模板
 */
class base extends \TMS_CONTROLLER {
	/**
	 *
	 */
	protected function &getTemplateDir($scenario, $template) {
		$templateDir = TMS_APP_TEMPLATE . '/pl/fe/matter/enroll/scenario/' . $scenario . '/templates/' . $template;
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
	 * 从模板中获得页面定义
	 */
	protected function &getPage($templateDir, &$oConfig, $name) {
		$pages = $oConfig->pages;
		if (empty($pages)) {
			return false;
		}
		if (!empty($name)) {
			if ($name === 'explaination') {
				$oTarget = new \stdClass;
				$oTarget->name = 'explaination';
				$oTarget->title = '模板说明';
				$oTarget->type = 'V';
			} else {
				foreach ($pages as $tp) {
					if ($tp->name === $name) {
						$oTarget = $tp;
						break;
					}
				}
			}
		}
		if (!isset($oTarget)) {
			$oTarget = $pages[0];
		}

		$templateFile = $templateDir . '/' . $oTarget->name;
		$oTarget->html = file_exists($templateFile . '.html') ? file_get_contents($templateFile . '.html') : '';
		$oTarget->css = file_exists($templateFile . '.css') ? file_get_contents($templateFile . '.css') : '';
		$oTarget->js = file_exists($templateFile . '.css') ? file_get_contents($templateFile . '.js') : '';

		/*填充页面*/
		$matched = array();
		$pattern = '/<!-- begin: generate by schema -->.*<!-- end: generate by schema -->/s';
		if (preg_match($pattern, $oTarget->html, $matched)) {
			$modelPage = $this->model('matter\enroll\page');
			if (isset($oConfig->simpleSchema)) {
				$html = $modelPage->htmlBySimpleSchema($oConfig->simpleSchema, $matched[0]);
			} else {
				$html = $modelPage->htmlBySchema($oTarget->data_schemas, $matched[0]);
			}
			$oTarget->html = preg_replace($pattern, $html, $oTarget->html);
		}

		return $oTarget;
	}
}