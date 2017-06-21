<?php
namespace pl\fe\matter\mission;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 项目控制器
 */
class report extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/mission/frame');
		exit;
	}
	/**
	 * 获得项目汇总报告
	 */
	public function userAndApp_action($mission) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelMis = $this->model('matter\mission');
		$oMission = $modelMis->byId($mission);
		if ($oMission === false) {
			return new \ObjectNotFoundError();
		}

		$posted = $this->getPostJson();
		if (!isset($posted->userSource) || !isset($posted->userSource->type) || !isset($posted->userSource->id)) {
			if (isset($oMission->user_app_id) && isset($oMission->user_app_type)) {
				$userSource = new \stdClass;
				$userSource->id = $oMission->user_app_id;
				$userSource->type = $oMission->user_app_type;
			} else {
				return new \ParameterError();
			}
		} else {
			$userSource = $posted->userSource;
		}

		/* 获得用户 */
		switch ($userSource->type) {
		case 'enroll':
			$users = $this->model('matter\enroll\record')->enrollerByApp($userSource, ['fields' => 'distinct userid,nickname', 'rid' => 'all']);
			break;
		case 'signin':
			$users = $this->model('matter\signin\record')->enrollerByApp($userSource, ['fields' => 'distinct userid,nickname']);
			break;
		case 'mschema':
			$users = $this->model('site\user\member')->byMschema($userSource->id, ['fields' => 'userid,name,email,mobile']);
			foreach ($users as &$oUser) {
				$oUser->nickname = empty($oUser->name) ? (empty($oUser->email) ? $oUser->mobile : $oUser->email) : $oUser->name;
			}
			break;
		}
		if (empty($users)) {
			return new \ParameterError('没有获得项目中用户');
		}

		/* 获得项目下的活动 */
		if (empty($posted->apps)) {
			$matters = $this->model('matter\mission\matter')->byMission($mission);
			if (count($matters) === 0) {
				return new \ParameterError('没有获得项目中活动');
			}
			$apps = [];
			foreach ($matters as $oMatter) {
				if (in_array($oMatter->type, ['enroll', 'signin', 'group'])) {
					$apps[] = (object) ['id' => $oMatter->id, 'type' => $oMatter->type];
				}
			}
		} else {
			$apps = $posted->apps;
		}

		$modelRep = $this->model('matter\mission\report');
		$result = $modelRep->userAndApp($users, $apps);

		return new \ResponseData($result);
	}
	/**
	 * 导出项目汇总报告
	 */
	public function export_action($mission) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelMis = $this->model('matter\mission');
		$oMission = $modelMis->byId($mission);
		if ($oMission === false) {
			return new \ObjectNotFoundError();
		}

		if (empty($oMission->user_app_id) || empty($oMission->user_app_type)) {
			return new \ParameterError();
		}

		$userSource = new \stdClass;
		$userSource->id = $oMission->user_app_id;
		$userSource->type = $oMission->user_app_type;

		/* 获得用户 */
		switch ($userSource->type) {
		case 'enroll':
			$users = $this->model('matter\enroll\record')->enrollerByApp($userSource, ['fields' => 'distinct userid,nickname', 'rid' => 'all']);
			break;
		case 'signin':
			$users = $this->model('matter\signin\record')->enrollerByApp($userSource, ['fields' => 'distinct userid,nickname']);
			break;
		case 'mschema':
			$users = $this->model('site\user\member')->byMschema($userSource->id, ['fields' => 'userid,name,email,mobile']);
			foreach ($users as &$oUser) {
				$oUser->nickname = empty($oUser->name) ? (empty($oUser->email) ? $oUser->mobile : $oUser->email) : $oUser->name;
			}
			break;
		}
		if (empty($users)) {
			return new \ParameterError('没有获得项目中用户');
		}

		$matters = $this->model('matter\mission\matter')->byMission($mission);
		if (count($matters) === 0) {
			return new \ParameterError('没有获得项目中活动');
		}
		$apps = [];
		foreach ($matters as $oMatter) {
			if (in_array($oMatter->type, ['enroll', 'signin', 'group'])) {
				$apps[] = (object) ['id' => $oMatter->id, 'type' => $oMatter->type];
			}
		}
		$modelRep = $this->model('matter\mission\report');
		$result = $modelRep->userAndApp($users, $apps);

		/*把result导出excel文件*/
		die('export report');
		// 输出
		// header('Content-Type: application/vnd.ms-excel');
		// header('Content-Disposition: attachment;filename="' . $oMatter->title . '（汇总报告）.xlsx"');
		// header('Cache-Control: max-age=0');
		// $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		// $objWriter->save('php://output');
		// exit;
	}
}