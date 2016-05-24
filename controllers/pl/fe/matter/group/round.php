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
		$rounds = $this->model('matter\group\round')->byApp($app);

		return new \ResponseData($rounds);
	}
	/**
	 *
	 */
	public function add_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$posted = $this->getPostJson();
		$r = array(
			'aid' => $app,
			'round_id' => uniqid(),
			'create_at' => time(),
			'title' => empty($posted->title) ? '新分组' : $posted->title,
			'targets' => '',
		);
		$this->model()->insert('xxt_group_round', $r, false);

		return new \ResponseData($r);
	}
	/**
	 *
	 */
	public function update_action($site, $app, $rid) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$model = $this->model();
		$nv = $this->getPostJson();

		/*data*/
		if (isset($nv->targets)) {
			$nv->targets = $model->toJson($nv->targets);
		}
		if (isset($nv->extattrs)) {
			$nv->extattrs = $model->toJson($nv->extattrs);
		}
		$rst = $model->update(
			'xxt_group_round',
			$nv,
			"aid='$app' and round_id='$rid'"
		);
		/*更新级联信息*/
		if ($rst && isset($nv->title)) {
			$model->update(
				'xxt_group_player',
				array('round_title' => $nv->title),
				"aid='$app' and round_id='$rid'"
			);
		}

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function remove_action($site, $app, $rid) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$model = $this->model();
		/**
		 * 已过已经有抽奖数据不允许删除
		 */
		$q = array(
			'count(*)',
			'xxt_group_player',
			"aid='$app' and round_id='$rid'",
		);
		if (0 < (int) $model->query_val_ss($q)) {
			return new \ResponseError('已经有分组数据，不允许删除轮次！');
		}

		$rst = $model->delete(
			'xxt_group_round',
			"aid='$app' and round_id='$rid'"
		);

		return new \ResponseData($rst);
	}
	/**
	 * 属于指定分组的人
	 */
	public function winnersGet_action($app, $rid = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$result = $this->model('matter\group\player')->winnersByRound($app, $rid);

		return new \ResponseData($result);
	}
}