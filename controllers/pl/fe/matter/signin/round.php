<?php
namespace pl\fe\matter\signin;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 签到活动主控制器
 */
class round extends \pl\fe\matter\base {
	/**
	 * 添加轮次
	 *
	 * $app
	 */
	public function add_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$posted = $this->getPostJson();

		$modelRnd = $this->model('matter\signin\round');
		if ($lastRound = $modelRnd->getLast($site, $app)) {
			/* 检查或更新上一轮状态 */
			if ((int) $lastRound->state === 0) {
				return new \ResponseError("最近一个轮次（$lastRound->title）是新建状态，不允许创建新轮次");
			}
			if ((int) $lastRound->state === 1 && (int) $posted->state === 1) {
				$modelRnd->update(
					'xxt_signin_round',
					array('state' => 2),
					"siteid='$site' and aid='$app' and rid='$lastRound->rid'"
				);
			}
		}
		/* 创建新轮次 */
		$roundId = uniqid();
		$round = array(
			'siteid' => $site,
			'aid' => $app,
			'rid' => $roundId,
			'creater' => $user->id,
			'create_at' => time(),
			'title' => $posted->title,
			'state' => $posted->state,
		);
		$modelRnd->insert('xxt_signin_round', $round, false);

		$q = array(
			'*',
			'xxt_signin_round',
			"siteid='$site' and aid='$app' and rid='$roundId'",
		);
		$round = $modelRnd->query_obj_ss($q);
		if ((int) $round->state === 1) {
			$modelRnd->update(
				'xxt_signin',
				array('active_round' => $round->rid),
				"siteid='$site' and id='$app'"
			);
		}

		return new \ResponseData($round);
	}
	/**
	 * 更新轮次
	 *
	 * $app
	 * $rid
	 */
	public function update_action($site, $app, $rid) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelRnd = $this->model('matter\signin\round');
		$posted = $this->getPostJson();

		if (isset($posted->state) && (int) $posted->state === 1) {
			/* 启用一个轮次，要停用上一个轮次 */
			if ($lastRound = $modelRnd->getLast($site, $app)) {
				if ((int) $lastRound->state !== 2) {
					$modelRnd->update(
						'xxt_signin_round',
						array('state' => 2),
						"siteid='$site' and aid='$app' and rid='$lastRound->rid'"
					);
				}
			}
			/* 更新签到活动的状态 */
			$modelRnd->update(
				'xxt_signin',
				array('active_round' => $rid),
				"siteid='$site' and id='$app'"
			);
		}

		$rst = $modelRnd->update(
			'xxt_signin_round',
			$posted,
			"siteid='$site' and aid='$app' and rid='$rid'"
		);

		return new \ResponseData($rst);
	}
	/**
	 * 删除轮次
	 *
	 * $app
	 * $rid
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

		if (false === $modelRnd->getLast($site, $app)) {
			/**
			 * 如果不存在轮次了修改签到活动的状态标记
			 */
			$modelRnd->update(
				'xxt_signin',
				array('multi_rounds' => 'N'),
				"siteid='$site' and id='$app'"
			);
		}

		return new \ResponseData($rst);
	}
}