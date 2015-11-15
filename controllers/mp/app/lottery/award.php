<?php
namespace mp\app\lottery;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 *
 */
class award extends \mp\app\app_base {
	/**
	 *
	 */
	public function index_action() {
		$this->view_action('/mp/app/lottery/detail');
	}
	/**
	 * 添加奖项
	 */
	public function add_action($mpid, $lottery) {
		$a = array(
			'mpid' => $mpid,
			'lid' => $lottery,
			'aid' => uniqid(),
			'title' => '新增奖项',
			'pic' => '',
			'type' => 0,
			'period' => 'A',
			'quantity' => 0,
			'prob' => 0,
		);
		$this->model()->insert('xxt_lottery_award', $a, false);

		return new \ResponseData($a);
	}
	/**
	 * 批量生成奖项
	 *
	 * @param string $mpid
	 * @param string $lottery
	 */
	public function batch_action($mpid, $lottery) {
		$option = $this->getPostJson();
		$awards = array();
		$title = empty($option->award->title) ? '' : $option->award->title;
		for ($i = 1, $l = $option->quantity; $i <= $l; $i++) {
			$a = array(
				'mpid' => $mpid,
				'lid' => $lottery,
				'aid' => uniqid(),
				'title' => $title . $i,
				'pic' => '',
				'type' => $option->award->type,
				'period' => $option->award->period,
				'quantity' => $option->award->quantity,
				'prob' => $option->award->prob,
				'greeting' => $option->award->greeting,
			);
			$this->model()->insert('xxt_lottery_award', $a, false);
			$awards[] = $a;
		}

		return new \ResponseData($awards);
	}
	/**
	 * 设置奖项的属性
	 *
	 * $aid award's id.
	 */
	public function update_action($award) {
		$nv = $this->getPostJson();
		$model = $this->model();

		if (isset($nv->description)) {
			$nv->description = $model->escape($nv->description);
		}
		if (isset($nv->greeting)) {
			$nv->greeting = $model->escape($nv->greeting);
		}

		$rst = $model->update('xxt_lottery_award', (array) $nv, "aid='$award'");

		return new \ResponseData($rst);
	}
	/**
	 * 删除奖项
	 *
	 * 如果已经有人中奖，就不允许删除奖项
	 */
	public function remove_action($award) {
		$model = $this->model();
		/**
		 * 检查是否已经有中奖记录
		 */
		$q = array(
			'count(*)',
			'xxt_lottery_log',
			"aid='$award'",
		);
		$cnt = $model->query_val_ss($q);
		if ($cnt > 0) {
			return new \ComplianceError('已经有中奖记录，奖项不允许被删除！');
		}

		$rst = $model->delete('xxt_lottery_award', "aid='$award'");

		return new \ResponseData($rst);
	}
}