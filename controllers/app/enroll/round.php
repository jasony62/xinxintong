<?php
namespace app\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动轮次
 */
class round extends base {
	/**
	 *
	 * @param string $mpid
	 * @param string $aid
	 */
	public function list_action($mpid, $aid) {
		$modelRun = $this->model('app\enroll\round');
		$options = array(
			'fields' => 'rid,title',
			'state' => '1,2',
		);
		$rounds = $modelRun->byApp($mpid, $aid, $options);

		return new \ResponseData($rounds);
	}
}