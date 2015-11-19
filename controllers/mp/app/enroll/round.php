<?php
namespace mp\app\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 *
 */
class round extends \mp\app\app_base {
	/**
	 *
	 */
	public function index_action() {
		$this->view_action('/mp/app/enroll/detail');
	}
	/**
	 * 添加轮次
	 *
	 * $aid
	 */
	public function add_action($aid) {
		if ($lastRound = $this->model('app\enroll\round')->getLast($this->mpid, $aid)) {
			/**
			 * 检查或更新上一轮状态
			 */
			if ((int) $lastRound->state === 0) {
				return new \ResponseError("最近一个轮次（$lastRound->title）是新建状态，不允许创建新轮次");
			}

			if ((int) $lastRound->state === 1) {
				$this->model()->update(
					'xxt_enroll_round',
					array('state' => 2),
					"mpid='$this->mpid' and aid='$aid' and rid='$lastRound->rid'"
				);
			}

		}
		$posted = $this->getPostJson();

		$roundId = uniqid();
		$round = array(
			'mpid' => $this->mpid,
			'aid' => $aid,
			'rid' => $roundId,
			'creater' => \TMS_CLIENT::get_client_uid(),
			'create_at' => time(),
			'title' => $posted->title,
			'state' => $posted->state,
		);

		$this->model()->insert('xxt_enroll_round', $round, false);

		if ($lastRound === false) {
			$this->model()->update(
				'xxt_enroll',
				array('multi_rounds' => 'Y'),
				"mpid='$this->mpid' and id='$aid'"
			);
		}

		$q = array(
			'*',
			'xxt_enroll_round',
			"mpid='$this->mpid' and aid='$aid' and rid='$roundId'",
		);
		$round = $this->model()->query_obj_ss($q);

		return new \ResponseData($round);
	}
	/**
	 * 更新轮次
	 *
	 * $aid
	 * $rid
	 */
	public function update_action($aid, $rid) {
		$posted = $this->getPostJson();

		if (isset($posted->state) && (int) $posted->state === 1) {
			/**
			 * 启用一个轮次，要停用上一个轮次
			 */
			if ($lastRound = $this->model('app\enroll\round')->getLast($this->mpid, $aid)) {
				if ((int) $lastRound->state !== 2) {
					$this->model()->update(
						'xxt_enroll_round',
						array('state' => 2),
						"mpid='$this->mpid' and aid='$aid' and rid='$lastRound->rid'"
					);
				}

			}
		}

		$rst = $this->model()->update(
			'xxt_enroll_round',
			$posted,
			"mpid='$this->mpid' and aid='$aid' and rid='$rid'"
		);

		return new \ResponseData($rst);
	}
	/**
	 * 删除轮次
	 *
	 * $aid
	 * $rid
	 */
	public function remove_action($aid, $rid) {
		/**
		 * 删除轮次
		 * ??? 如果轮次已经启用？如果已经有数据呢？
		 */
		$rst = $this->model()->delete(
			'xxt_enroll_round',
			"mpid='$this->mpid' and aid='$aid' and rid='$rid'"
		);

		if (false === $this->model('app\enroll\round')->getLast($this->mpid, $aid)) {
			/**
			 * 如果不存在轮次了修改登记活动的状态标记
			 */
			$this->model()->update(
				'xxt_enroll',
				array('multi_rounds' => 'N'),
				"mpid='$this->mpid' and id='$aid'"
			);
		}

		return new \ResponseData($rst);
	}
}