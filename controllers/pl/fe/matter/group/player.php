<?php
namespace pl\fe\matter\group;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 分组活动控制器
 */
class player extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/group/frame');
		exit;
	}
	/**
	 *
	 */
	public function list_action($site, $app) {
		$modelGrp = $this->model('matter\group');
		$modelPlayer = $this->model('matter\group\player');

		$app = $modelGrp->byId($app);
		$result = $modelPlayer->find($site, $app);

		return new \ResponseData($result);
	}
}