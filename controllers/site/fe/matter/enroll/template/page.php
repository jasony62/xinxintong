<?php
namespace site\fe\matter\enroll\template;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动数据定义
 */
class page extends base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 获得登记项定义
	 */
	public function schemaGet_action($scenario, $template) {
		$templateDir = $this->getTemplateDir($scenario, $template);
		$config = $this->getConfig($templateDir);

		$schema = $this->model('app\enroll\page')->schemaByText($config->simpleSchema);

		return new \ResponseData($schema);
	}
}