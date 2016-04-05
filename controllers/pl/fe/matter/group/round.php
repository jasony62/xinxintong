<?php
namespace pl\fe\matter\group;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 分组活动控制器
 */
class round extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function list_action($site, $app) {
		$rounds = $this->model('matter\group\round')->find($app);

		return new \ResponseData($rounds);
	}
	/**
	 *
	 */
	public function add_action($site, $app) {
		$r = array(
			'aid' => $app,
			'round_id' => uniqid(),
			'create_at' => time(),
			'title' => '新轮次',
			'targets' => '',
		);
		$this->model()->insert('xxt_group_round', $r, false);

		return new \ResponseData($r);
	}
	/**
	 *
	 */
	public function update_action($site, $app, $rid) {
		$model = $this->model();
		$nv = $this->getPostJson();

		if (isset($nv->targets)) {
			$nv->targets = $model->escape($nv->targets);
		}

		$rst = $model->update(
			'xxt_group_round',
			$nv,
			"aid='$app' and round_id='$rid'"
		);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function remove_action($site, $app, $rid) {
		$model = $this->model();
		/**
		 * 已过已经有抽奖数据不允许删除
		 */
		$q = array(
			'count(*)',
			'xxt_group_result',
			"aid='$app' and round_id='$rid'",
		);
		if (0 < (int) $model->query_val_ss($q)) {
			return new \ResponseError('已经有抽奖数据，不允许删除轮次！');
		}

		$rst = $model->delete(
			'xxt_group_round',
			"aid='$app' and round_id='$rid'"
		);

		return new \ResponseData($rst);
	}
}