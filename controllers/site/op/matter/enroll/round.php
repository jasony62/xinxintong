<?php
namespace site\op\matter\enroll;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/*
 * 登记活动主控制器
 */
class round extends \site\op\base {
	/**
	 * 返回指定登记活动下的轮次
	 *
	 * @param string $app app's id
	 *
	 */
	public function list_action($app, $page = 1, $size = 10) {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$modelRnd = $this->model('matter\enroll\round');

		/* 先检查是否要根据定时规则生成轮次 */
		$modelRnd->getActive($oApp);

		$oPage = new \stdClass;
		$oPage->num = $page;
		$oPage->size = $size;

		$result = $modelRnd->byApp($oApp, ['page' => $oPage]);

		return new \ResponseData($result);
	}
	
}