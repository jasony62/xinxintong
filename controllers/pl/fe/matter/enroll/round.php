<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记活动主控制器
 */
class round extends \pl\fe\matter\base {
	/**
	 * 添加轮次
	 *
	 * $app
	 */
	public function add_action($site, $app) {
		$modelRnd = $this->model('matter\enroll\round');
		if ($lastRound = $modelRnd->getLast($site, $app)) {
			/**
			 * 检查或更新上一轮状态
			 */
			if ((int) $lastRound->state === 0) {
				return new \ResponseError("最近一个轮次（$lastRound->title）是新建状态，不允许创建新轮次");
			}

			if ((int) $lastRound->state === 1) {
				$modelRnd->update(
					'xxt_enroll_round',
					array('state' => 2),
					"siteid='$site' and aid='$app' and rid='$lastRound->rid'"
				);
			}

		}
		$posted = $this->getPostJson();

		$roundId = uniqid();
		$round = array(
			'siteid' => $site,
			'aid' => $app,
			'rid' => $roundId,
			'creater' => \TMS_CLIENT::get_client_uid(),
			'create_at' => time(),
			'title' => $posted->title,
			'state' => $posted->state,
		);

		$modelRnd->insert('xxt_enroll_round', $round, false);

		if ($lastRound === false) {
			$modelRnd->update(
				'xxt_enroll',
				array('multi_rounds' => 'Y'),
				"siteid='$site' and id='$app'"
			);
		}

		$q = array(
			'*',
			'xxt_enroll_round',
			"siteid='$site' and aid='$app' and rid='$roundId'",
		);
		$round = $modelRnd->query_obj_ss($q);

		return new \ResponseData($round);
	}
	/**
	 * 更新轮次
	 *
	 * $app
	 * $rid
	 */
	public function update_action($site, $app, $rid) {
		$modelRnd = $this->model('matter\enroll\round');
		$posted = $this->getPostJson();

		if (isset($posted->state) && (int) $posted->state === 1) {
			/**
			 * 启用一个轮次，要停用上一个轮次
			 */
			if ($lastRound = $modelRnd->getLast($site, $app)) {
				if ((int) $lastRound->state !== 2) {
					$modelRnd->update(
						'xxt_enroll_round',
						array('state' => 2),
						"siteid='$site' and aid='$app' and rid='$lastRound->rid'"
					);
				}
			}
		}

		$rst = $modelRnd->update(
			'xxt_enroll_round',
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
		$modelRnd = $this->model('matter\enroll\round');
		/**
		 * 删除轮次
		 * ??? 如果轮次已经启用？如果已经有数据呢？
		 */
		$rst = $modelRnd->delete(
			'xxt_enroll_round',
			"siteid='$site' and aid='$app' and rid='$rid'"
		);

		if (false === $modelRnd->getLast($site, $app)) {
			/**
			 * 如果不存在轮次了修改登记活动的状态标记
			 */
			$modelRnd->update(
				'xxt_enroll',
				array('multi_rounds' => 'N'),
				"siteid='$site' and id='$app'"
			);
		}

		return new \ResponseData($rst);
	}
}