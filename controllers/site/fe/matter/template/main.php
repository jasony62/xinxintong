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
		if (false === ($template = $this->model('matter\template')->byId($template, null, ['cascaded'=>'N']))) {
			die('指定的模板不存在，请检查参数是否正确');
		}
		if(empty($template->pub_version)){
			die('模板已下架');
		}

		\TPL::output('/site/fe/matter/template/main');
		exit;
	}
	/**
	 *
	 */
	public function get_action($template, $vid=null) {
		if (false === ($template = $this->model('matter\template')->byId($template, $vid)) ) {
			die('指定的模板不存在，请检查参数是否正确');
		}

		return new \ResponseData($template);
	}
}