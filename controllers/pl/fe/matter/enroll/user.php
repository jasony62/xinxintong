<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记活动用户
 */
class user extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 提交过登记记录的用户
	 */
	public function enrollee_action($app, $page = 1, $size = 30) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

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
	 * 根据通讯录返回用户完成情况
	 */
	public function byMschema_action($app, $mschema) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$modelEnl = $this->model('site\user\memberschema');
		$oMschema = $modelEnl->byId($mschema, ['cascaded' => 'N']);
		if (false === $oMschema) {
			return new \ObjectNotFoundError();
		}

		$modelUsr = $this->model('matter\enroll\user');
		$result = $modelUsr->enrolleeByMschema($oApp, $oMschema);

		return new \ResponseData($result);
	}
	/**
	 * 发表过评论的用户
	 */
	public function remarker_action($app, $page = 1, $size = 30) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

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