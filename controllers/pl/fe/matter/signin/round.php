<?php
namespace pl\fe\matter\signin;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 签到活动轮次控制器
 */
class round extends \pl\fe\matter\base {
	/**
	 * 添加轮次
	 *
	 * @param string $app
	 */
	public function add_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$posted = $this->getPostJson();
		$modelRnd = $this->model('matter\signin\round');
		/* 创建新轮次 */
		$roundId = uniqid();
		$round = array(
			'siteid' => $site,
			'aid' => $app,
			'rid' => $roundId,
			'creater' => $user->id,
			'create_at' => time(),
			'title' => isset($posted->title) ? $posted->title : '新轮次',
			'start_at' => isset($posted->start_at) ? $posted->start_at : 0,
			'end_at' => isset($posted->end_at) ? $posted->end_at : 0,
		);
		$modelRnd->insert('xxt_signin_round', $round, false);

		$newRnd = $modelRnd->byId($roundId);

		return new \ResponseData($newRnd);
	}
	/**
	 * 更新轮次
	 *
	 * @param string $app
	 * @param string $rid
	 */
	public function update_action($site, $app, $rid) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelRnd = $this->model('matter\signin\round');
		$posted = $this->getPostJson();

		$rst = $modelRnd->update(
			'xxt_signin_round',
			$posted,
			"siteid='$site' and aid='$app' and rid='$rid'"
		);

		$newRnd = $modelRnd->byId($rid);

		return new \ResponseData($newRnd);
	}
	/**
	 * 删除轮次
	 *
	 * @param string $app
	 * @param string $rid
	 */
	public function remove_action($site, $app, $rid) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelRnd = $this->model('matter\signin\round');
		/**
		 * 删除轮次
		 * ??? 如果轮次已经启用？如果已经有数据呢？
		 */
		$rst = $modelRnd->delete(
			'xxt_signin_round',
			"siteid='$site' and aid='$app' and rid='$rid'"
		);

		return new \ResponseData($rst);
	}
}