<?php
namespace pl\fe\matter\lottery;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 抽奖活动控制器
 */
class plate extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/lottery/frame');
		exit;
	}
	/**
	 * 获得转盘设置信息
	 */
	public function get_action($app) {
		$q = array(
			'*',
			'xxt_lottery_plate',
			"lid='$app'",
		);
		$p = $this->model()->query_obj_ss($q);

		return new \ResponseData($p);
	}
	/**
	 * 设置转盘槽位的奖项
	 */
	public function update_action($app) {
		$r = $this->getPostJson();

		$rst = $this->model()->update(
			'xxt_lottery_plate',
			$r,
			"lid='$app'"
		);

		return new \ResponseData($rst);
	}
}