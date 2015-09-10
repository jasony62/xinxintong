<?php
namespace app\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动轮次
 */
class round extends base {
	/**
	 *
	 * $mpid
	 * $aid
	 */
	public function list_action($mpid, $aid) {
		$modelRun = $this->model('app\enroll\round');
		$rounds = $modelRun->byEnroll($mpid, $aid);

		return new \ResponseData($rounds);
	}
}