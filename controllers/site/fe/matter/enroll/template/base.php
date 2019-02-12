<?php
namespace site\fe\matter\enroll\template;

/**
 * 记录活动模板
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
	protected function getPage($templateDir, &$oConfig, $name) {
		if (empty($oConfig->pages) && $name !== 'explaination') {
			return false;
		}

		if ($name === 'explaination') {
			$oTarget = new \stdClass;
			$oTarget->name = 'explaination';
			$oTarget->title = '模板说明';
			$oTarget->type = 'V';
		} else {
			$pages = $oConfig->pages;
			foreach ($pages as $tp) {
				if ($tp->name === $name) {
					$oTarget = $tp;
					break;
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

		$oTarget->html = $this->model('matter\enroll\page')->compileHtml($oTarget->type, $oTarget->html, isset($oTarget->data_schemas) ? $oTarget->data_schemas : []);

		return $oTarget;
	}
}