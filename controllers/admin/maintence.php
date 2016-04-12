<?php
namespace admin;
/**
 * 系统维护
 */
class maintence extends \TMS_CONTROLLER {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 *
	 */
	public function do_action() {
		$modelPage = $this->model('app\enroll\page');
		$q = array('*', 'xxt_enroll', "state=1");
		$apps = $modelPage->query_objs_ss($q);
		foreach ($apps as $app) {
			$schemas = $modelPage->schemaByApp($app->id);
			$schemas = $modelPage->toJson($schemas);
			$modelPage->update('xxt_enroll', array('data_schemas' => $schemas), "id='{$app->id}'");
			$modelPage->update('xxt_enroll_page', array('data_schemas' => $schemas), "aid='{$app->id}' and type='I'");
		}
		return new \ResponseData('ok');
	}
}