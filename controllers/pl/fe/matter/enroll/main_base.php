<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/main_base.php';
/*
 * 记录活动主控制器
 */
abstract class main_base extends \pl\fe\matter\main_base {
	/**
	 * 返回视图
	 */
	public function index_action($id) {
		if (empty($id)) {
			die('无效参数');
		}
		$aAccess = $this->accessControlUser('enroll', $id);
		if ($aAccess[0] === false) {
			die($aAccess[1]);
		}

		$oAccount = $aAccess[1];
		$oAccount = $this->model('account')->byId($oAccount->id, ['cascaded' => ['group']]);
		if (isset($oAccount->group->view_name) && $oAccount->group->view_name !== TMS_APP_VIEW_NAME) {
			\TPL::output('/pl/fe/matter/enroll/frame', ['customViewName' => $oAccount->group->view_name]);
		} else {
			\TPL::output('/pl/fe/matter/enroll/frame');
		}
		exit;
	}
	/**
	 * 解除和项目的关联
	 */
	public function quitMission_action($app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\\' . $this->getMatterType())->setOnlyWriteDbConn(true);
		$oApp = $modelApp->byId($app);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$oUpdatedApp = $modelApp->quitMission($oApp);
		if (false === $oUpdatedApp[0]) {
			return new \ResponseError($oUpdatedApp[1]);
		}
		$oUpdatedApp = $oUpdatedApp[1];

		/* 从项目中移除 */
		if (!empty($oApp->mission_id)) {
			$modelMis = $this->model('matter\mission');
			$modelMis->removeMatter($oApp->mission_id, $oApp);
		}

		/* 保存页面更新 */
		if (isset($oUpdatedApp->pages)) {
			$modelPg = $this->model('matter\\' . $this->getMatterType() . '\\page');
			foreach ($oUpdatedApp->pages as $oPage) {
				$modelPg->modify($oPage, ['data_schemas', 'html']);
			}
			unset($oUpdatedApp->pages);
		}
		/* 保存活动更新 */
		if ($oApp = $modelApp->modify($oUser, $oApp, $oUpdatedApp)) {
			// 记录操作日志并更新信息
			$this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'U', $oUpdatedApp);
		}

		return new \ResponseData($oApp);
	}
}