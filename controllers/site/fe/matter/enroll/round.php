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
	public function list_action($site, $app, $page = 1, $size = 10) {

		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		$modelRun = $this->model('matter\enroll\round');
		$options = [
			'fields' => 'rid,title',
			'state' => '1,2',
		];

		$oPage = new \stdClass;
		$oPage->num = $page;
		$oPage->size = $size;
		$options['page'] = $oPage;

		$result = $modelRun->byApp($oApp, $options);

		return new \ResponseData($result);
	}
}