<?php
namespace site\op\matter\enroll;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/**
 * 登记活动报表
 */
class report extends \site\op\base {
	/**
	 * 返回视图
	 */
	public function index_action($app) {
		if (!$this->checkAccessToken()) {
			header('HTTP/1.0 500 parameter error:accessToken is invalid.');
			die('没有获得有效访问令牌！');
		}

		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);

		\TPL::assign('title', $oApp->title);
		\TPL::output('/site/op/matter/enroll/report');
		exit;
	}
	/**
	 * 统计登记信息
	 *
	 * 只统计single/multiple类型的数据项
	 *
	 * return
	 * name => array(l=>label,c=>count)
	 *
	 */
	public function get_action($site, $app, $rid = null, $renewCache = 'Y') {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		$stat = $this->model('matter\enroll\record')->getStat($oApp, $rid, $renewCache);

		$result = new \stdClass;
		$result->app = $oApp;
		$result->stat = $stat;

		return new \ResponseData($result);
	}
}