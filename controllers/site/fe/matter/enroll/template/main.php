<?php
namespace site\fe\matter\enroll\template;

require_once dirname(__FILE__) . "/base.php";
/**
 * 记录活动模板
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
		$oCustomConfig = $this->getPostJson();
		$params = array();
		/*模版配置信息*/
		$templateDir = $this->getTemplateDir($scenario, $template);
		$initialConfig = $this->getConfig($templateDir);
		!(empty($oCustomConfig->simpleSchema)) && $initialConfig->simpleSchema = $oCustomConfig->simpleSchema;
		/*当前访问用户的基本信息*/
		$oUser = new \stdClass;
		$oUser->nickname = '演示用户';
		$params['user'] = $oUser;
		/*打开页面*/
		$params['page'] = $this->getPage($templateDir, $initialConfig, $page);
		$data = $this->getData($templateDir);
		isset($data->activeRound) && $params['activeRound'] = $data->activeRound;

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
		$oCustomConfig = $this->getPostJson();
		if (!empty($oCustomConfig->simpleSchema)) {
			$schema = $this->model('matter\enroll\page')->schemaByText($oCustomConfig->simpleSchema);
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
			if (isset($data->statistic)) {
				$statistic = $data->statistic;
			}
		}

		return new \ResponseData($statistic);
	}
}