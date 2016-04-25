<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动轮次
 */
class round extends base {
	/**
	 *
	 * @param string $site
	 * @param string $app
	 */
	public function list_action($site, $app) {
		$modelRun = $this->model('matter\enroll\round');
		$options = array(
			'fields' => 'rid,title',
			'state' => '1,2',
		);
		$rounds = $modelRun->byApp($site, $app, $options);

		return new \ResponseData($rounds);
	}
}