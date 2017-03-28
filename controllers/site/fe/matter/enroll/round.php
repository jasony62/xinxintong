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

		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		$modelRun = $this->model('matter\enroll\round');
		$options = [
			'fields' => 'rid,title',
			'state' => '1,2',
		];
		$rounds = $modelRun->byApp($oApp, $options);

		return new \ResponseData($rounds);
	}
}