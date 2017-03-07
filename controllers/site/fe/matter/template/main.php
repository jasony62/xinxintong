<?php
namespace site\fe\matter\template;

/**
 * 模版
 */
class main extends \TMS_CONTROLLER {
	/**
	 *
	 */
	public function get_access_rule() {
		$ruleAction['rule_type'] = 'black';

		return $ruleAction;
	}
	/**
	 *
	 */
	public function index_action($template) {
		\TPL::output('/site/fe/matter/template/main');
		exit;
	}
	/**
	 *
	 */
	public function get_action($template, $vid=null) {
		$template = $this->model('matter\template')->byId($template, $vid);

		return new \ResponseData($template);
	}
}