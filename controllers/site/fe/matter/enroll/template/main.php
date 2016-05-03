<?php
namespace site\fe\matter\enroll\template;

require_once dirname(__FILE__) . "/base.php";
/**
 * 登记活动模板
 */
class main extends base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::assign('title', '模板演示');
		\TPL::output('/site/fe/matter/enroll/template');
		exit;
	}
	/**
	 * 获得指定页面的数据
	 */
	public function pageGet_action($scenario, $template, $page = '') {
		$customConfig = $this->getPostJson();
		$params = array();
		/*模版配置信息*/
		$templateDir = $this->getTemplateDir($scenario, $template);
		$initialConfig = $this->getConfig($templateDir);
		!(empty($customConfig->simpleSchema)) && $initialConfig->simpleSchema = $customConfig->simpleSchema;
		/*当前访问用户的基本信息*/
		$user = new \stdClass;
		$user->fan = new \stdClass;
		$user->fan->nickname = '演示用户';
		$params['user'] = $user;
		/*打开页面*/
		$params['page'] = $this->getPage($templateDir, $initialConfig, $page);
		if ($initialConfig->multi_rounds === 'Y') {
			$data = $this->getData($templateDir);
			$params['activeRound'] = $data->activeRound;
		}

		return new \ResponseData($params);
	}
	/**
	 * 统计登记信息
	 *
	 * 只统计radio/checkbox类型的数据项
	 *
	 * return
	 * name => array(l=>label,c=>count)
	 *
	 */
	public function statGet_action($scenario, $template) {
		$customConfig = $this->getPostJson();
		if (!empty($customConfig->simpleSchema)) {
			$schema = $this->model('matter\enroll\page')->schemaByText($customConfig->simpleSchema);
			$statistic = new \stdClass;
			foreach ($schema as &$def) {
				if ($def->type === 'single' || $def->type === 'multiple') {
					foreach ($def->ops as &$op) {
						$op->c = mt_rand(0, 10);
					}
					$statistic->{$def->id} = $def;
				}
			}
		} else {
			$templateDir = $this->getTemplateDir($scenario, $template);
			$data = $this->getData($templateDir);

			$statistic = $data->statistic;
		}

		return new \ResponseData($statistic);
	}
}