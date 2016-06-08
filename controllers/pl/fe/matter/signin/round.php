<?php
namespace pl\fe\matter\signin;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 签到活动轮次控制器
 */
class round extends \pl\fe\matter\base {
	/**
	 * 批量添加轮次
	 *
	 * @param string $app
	 */
	public function batch_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelRnd = $this->model('matter\signin\round');
		$posted = $this->getPostJson();

		if ($posted->overwrite === 'Y') {
			/*删除已有的轮次*/
			$rst = $modelRnd->delete(
				'xxt_signin_round',
				"siteid='$site' and aid='$app'"
			);
		}
		/*计算创建的数量*/
		$startAt = getdate($posted->start_at);
		$startDay = mktime(0, 0, 0, $startAt['mon'], $startAt['mday'], $startAt['year']);
		$first = array(3600 * 7, 3600 * 2);
		$second = array(3600 * 12, 3600 * 2);
		$roundStartAt = $startDay;

		/*创建轮次*/
		$i = 1; //轮次的编号
		$d = 0; //日期的编号
		$rounds = array();
		while ($roundStartAt < $posted->end_at) {
			$roundStartAt += $first[0];
			if ($roundStartAt > $posted->start_at && $roundStartAt < $posted->end_at) {
				/* 创建新轮次 */
				$roundId = uniqid();
				$round = array(
					'siteid' => $site,
					'aid' => $app,
					'rid' => $roundId,
					'creater' => $user->id,
					'create_at' => time(),
					'title' => isset($posted->title) ? $posted->title : "轮次{$i}",
					'start_at' => $roundStartAt,
					'end_at' => $roundStartAt + $first[1],
				);
				$modelRnd->insert('xxt_signin_round', $round, false);

				$newRnd = $modelRnd->byId($roundId);

				$rounds[] = $newRnd;

				$i++;
			}
			if ($posted->timesOfDay == 2) {
				$roundStartAt = $roundStartAt - $first[0] + $second[0];
				if ($roundStartAt > $posted->start_at && $roundStartAt < $posted->end_at) {
					/* 创建新轮次 */
					$roundId = uniqid();
					$round = array(
						'siteid' => $site,
						'aid' => $app,
						'rid' => $roundId,
						'creater' => $user->id,
						'create_at' => time(),
						'title' => isset($posted->title) ? $posted->title : "轮次{$i}",
						'start_at' => $roundStartAt,
						'end_at' => $roundStartAt + $first[1],
					);
					$modelRnd->insert('xxt_signin_round', $round, false);

					$newRnd = $modelRnd->byId($roundId);

					$rounds[] = $newRnd;

					$i++;
				}
			}
			$d++;
			$roundStartAt = $startDay + (86400 * $d);
		}

		return new \ResponseData($rounds);
	}
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