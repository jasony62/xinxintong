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
	 *
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
			return new \ParameterError();
		}

		/* 获得用户 */
		$userSource = $posted->userSource;
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
}