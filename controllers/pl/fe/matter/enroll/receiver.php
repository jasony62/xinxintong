<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记活动控制器
 */
class receiver extends \pl\fe\matter\base {
	/**
	 * 事件通知接收人
	 *
	 * @param string site
	 * @param string $app
	 */
	public function list_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelRev = $this->model('matter\enroll\receiver');

		$receivers = $modelRev->byApp($site, $app);

		return new \ResponseData($receivers);
	}
	/**
	 *
	 */
	public function afterJoin_action($site, $app, $timestamp = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		if (empty($timestamp)) {
			return new \ResponseData([]);
		}

		$modelRev = $this->model('matter\enroll\receiver');

		$receivers = $modelRev->afterJoin($site, $app, $timestamp);

		return new \ResponseData($receivers);
	}
	/**
	 * 删除轮次
	 *
	 * @param string site
	 * @param string $app
	 * @param string $receiver
	 */
	public function remove_action($site, $app, $receiver) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model()->delete(
			'xxt_enroll_receiver',
			"siteid='$site' and aid='$app' and userid='$receiver'"
		);

		return new \ResponseData($rst);
	}
}