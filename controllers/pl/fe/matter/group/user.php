<?php
namespace pl\fe\matter\group;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 分组活动控制器
 */
class user extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/group/frame');
		exit;
	}
	/**
	 * 返回分组用户数据
	 */
	public function list_action($app) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$modelGrp = $this->model('matter\group');

		$oApp = $modelGrp->byId($app);
		if (false === $oApp && $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oPosted = $this->getPostJson();

		$aOptions = [];
		if (isset($oPosted->roleRoundId)) {
			$aOptions['roleRoundId'] = $modelPlayer->escape($oPosted->roleRoundId);
		}
		if (isset($oPosted->roundId)) {
			$aOptions['roundId'] = $modelPlayer->escape($oPosted->roundId);
		}
		if (!empty($oPosted->kw) && !empty($oPosted->by)) {
			$aOptions[$oPosted->by] = $oPosted->kw;
		}

		$modelGrpUsr = $this->model('matter\group\user');
		$oResult = $modelGrpUsr->byApp($oApp, $aOptions);

		return new \ResponseData($oResult);
	}
}