<?php
namespace site\op\matter\plan;

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
		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app, ['fields' => 'id,state,siteid,mission_id,mission_phase_id,title,summary,pic,check_schemas,jump_delayed']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		\TPL::assign('title', $oApp->title);
		\TPL::output('site/op/matter/plan/console');
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

		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app, ['fields' => 'id,state,siteid,mission_id,mission_phase_id,title,summary,pic,check_schemas,jump_delayed']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		return new \ResponseData($oApp);
	}
}