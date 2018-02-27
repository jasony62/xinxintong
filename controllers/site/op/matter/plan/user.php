<?php
namespace site\op\matter\plan;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/**
 *
 */
class user extends \site\op\base {
	/**
	 * 提交过任务的用户
	 */
	public function enrollee_action($app, $page = 1, $size = 30) {
		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app, ['fields' => 'id,state,check_schemas']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$modelUsr = $this->model('matter\enroll\user');
		$options = [];
		$options['onlyEnrolled'] = 'Y';
		if (!empty($page) && !empty($size)) {
			$options['paging'] = ['page' => $page, 'size' => $size];
		}
		$data = $modelUsr->byApp($oApp, $options);

		return new \ResponseData($data);
	}
}