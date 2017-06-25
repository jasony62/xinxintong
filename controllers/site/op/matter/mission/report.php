<?php
namespace site\op\matter\mission;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/*
 * 项目控制器
 */
class report extends \site\op\base {
	/**
	 * 获得项目汇总报告
	 * 如果用户指定了查询参数，保存查询参数
	 */
	public function userAndApp_action($mission) {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
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
			/* 如果没有指定 */
			$matters = $this->model('matter\mission\matter')->byMission($oMission->id);
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
	 * 获得指定用户在项目中的行为记录
	 */
	public function recordByUser_action($mission, $user, $app = '') {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$result = new \stdClass;
		if (empty($app)) {
			$modelEnlRec = $this->model('matter\enroll\record');
			$records = $modelEnlRec->byMission($mission, ['userid' => $user]);
			$result->enroll = $records;

			$modelSigRec = $this->model('matter\signin\record');
			$records = $modelSigRec->byMission($mission, ['userid' => $user]);
			$result->signin = $records;

			$modelGrpRec = $this->model('matter\group\player');
			$records = $modelGrpRec->byMission($mission, ['userid' => $user]);
			$result->group = $records;
		} else {
			list($appType, $appId) = explode(',', $app);
			if (isset($appId) && isset($appType)) {
				switch ($appType) {
				case 'enroll':
					$oUser = new \stdClass;
					$oUser->userid = $user;
					$modelEnlRec = $this->model('matter\enroll\record');
					$result = $modelEnlRec->byUser($appId, $oUser);
					break;
				case 'signin':
					$oApp = new \stdClass;
					$oApp->id = $appId;
					$oUser = new \stdClass;
					$oUser->userid = $user;
					$modelSigRec = $this->model('matter\signin\record');
					$result = $modelSigRec->byUser($oUser, $oApp);
					break;
				case 'group':
					$oApp = new \stdClass;
					$oApp->id = $appId;
					$modelGrpRec = $this->model('matter\group\player');
					$result = $modelGrpRec->byUser($oApp, $user);
					break;
				}
			}
		}

		return new \ResponseData($result);
	}
}