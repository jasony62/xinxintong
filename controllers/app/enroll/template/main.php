<?php
namespace app\enroll\template;
/**
 * 登记活动模板
 */
class main extends \TMS_CONTROLLER {
	/**
	 *
	 */
	public function index_action() {
		\TPL::assign('title', '模板演示');
		\TPL::output('/app/enroll/template');
		exit;
	}
	/**
	 *
	 */
	public function get_action($scenario, $template, $page = '') {
		$params = array();
		/*当前访问用户的基本信息*/
		$user = new \stdClass;
		$user->fan = new \stdClass;
		$user->fan->nickname = '演示用户';
		$params['user'] = $user;
		/*打开页面*/
		$params['page'] = $this->_getPage($scenario, $template, $page);
		/*登记记录*/
		$record = new \stdClass;
		$params['record'] = $record;

		return new \ResponseData($params);
	}
	/**
	 * 从模板中获得定义
	 */
	private function &_getPage($scenario, $template, $name) {
		$templateDir = $_SERVER['DOCUMENT_ROOT'] . '/controllers/mp/app/enroll/scenario/' . $scenario . '/templates/' . $template;
		$config = file_get_contents($templateDir . '/config.js');
		$config = preg_replace('/\t|\r|\n/', '', $config);
		$config = json_decode($config);
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

		return $target;
	}
}