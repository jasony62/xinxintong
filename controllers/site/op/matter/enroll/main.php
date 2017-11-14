<?php
namespace site\op\matter\enroll;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/**
 *
 */
class main extends \site\op\base {
	/**
	 *
	 */
	public function index_action($app) {
		if (!$this->checkAccessToken()) {
			header('HTTP/1.0 500 parameter error:accessToken is invalid.');
			die('提供的令牌无效，或者令牌已经过期！');
		}
		$oApp = $this->model('matter\enroll')->byId($app);
		\TPL::assign('title', $oApp->title);
		\TPL::output('site/op/matter/enroll/console');
		exit;
	}
	/**
	 *
	 *
	 * @param string $appid
	 */
	public function get_action($app) {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'Y']);

		return new \ResponseData($oApp);
	}
}