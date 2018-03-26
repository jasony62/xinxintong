<?php
namespace site\op\matter\enroll;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/**
 *
 */
class user extends \site\op\base {
	/**
	 * 提交过登记记录的用户
	 */
	public function enrollee_action($app, $page = 1, $size = 30) {
		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$modelUsr = $this->model('matter\enroll\user');
		$result = $modelUsr->enrolleeByApp($oApp, $page, $size);

		return new \ResponseData($result);
	}
	/**
	 * 发表过留言的用户
	 */
	public function remarker_action($app, $page = 1, $size = 30) {
		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$modelUsr = $this->model('matter\enroll\user');
		$result = $modelUsr->remarkerByApp($oApp, $page, $size);

		return new \ResponseData($result);
	}
}