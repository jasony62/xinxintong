<?php
namespace site\op\matter\signin;

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
			die('没有获得有效访问令牌！');
		}
		$app = $this->model('matter\signin')->byId($app);
		\TPL::assign('title', $app->title);
		\TPL::output('site/op/matter/signin/console');
		exit;
	}
	/**
	 * 返回登记记录
	 *
	 * @param string $siteid
	 * @param string $appid
	 */
	public function get_action($site, $app) {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$params = array();

		/* 登记活动定义 */
		$modelApp = $this->model('matter\signin');
		$app = $modelApp->byId($app, ['cascaded' => 'Y']);
		$params['app'] = &$app;
		/*关联登记活动*/
		if ($app->enroll_app_id) {
			$app->enrollApp = $this->model('matter\enroll')->byId($app->enroll_app_id);
		}
		/*关联分组活动*/
		if ($app->group_app_id) {
			$app->groupApp = $this->model('matter\group')->byId($app->group_app_id);
		}

		return new \ResponseData($params);
	}
	/**
	 * 登记情况汇总信息
	 */
	public function opData_action($site, $app) {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$mdoelApp = $this->model('matter\signin');
		$oApp = new \stdClass;
		$oApp->siteid = $site;
		$oApp->id = $app;
		$opData = $mdoelApp->opData($oApp);

		return new \ResponseData($opData);
	}
}