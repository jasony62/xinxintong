<?php
namespace app\enroll\template;

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
		\TPL::output('/app/enroll/template');
		exit;
	}
	/**
	 *
	 */
	public function pageGet_action($scenario, $template, $page = '') {
		$params = array();
		/*模版配置信息*/
		$templateDir = $this->getTemplateDir($scenario, $template);
		$config = $this->getConfig($templateDir);
		/*当前访问用户的基本信息*/
		$user = new \stdClass;
		$user->fan = new \stdClass;
		$user->fan->nickname = '演示用户';
		$params['user'] = $user;
		/*打开页面*/
		$params['page'] = $this->getPage($templateDir, $config, $page);
		if ($config->multi_rounds === 'Y') {
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
		$templateDir = $this->getTemplateDir($scenario, $template);
		$data = $this->getData($templateDir);

		$statistic = $data->statistic;

		return new \ResponseData($statistic);
	}
}