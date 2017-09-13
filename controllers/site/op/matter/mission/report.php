<?php
namespace site\op\matter\mission;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/*
 * 项目控制器
 */
class report extends \site\op\base {
	/**
	 * 项目下的素材信息
	 */
	public function matterList_action($mission) {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$modelMis = $this->model('matter\mission\matter');

		$mattersByUser = [];
		$mattersByMis = $modelMis->byMission($mission, null, ['is_public' => 'Y']);
		if (count($mattersByMis)) {
			foreach ($mattersByMis as $oMatter) {
				if (!in_array($oMatter->type, ['enroll', 'signin', 'article'])) {
					continue;
				}
				if ($oMatter->type === 'enroll') {
					if (!isset($modelEnl)) {
						$modelEnl = $this->model('matter\enroll');
					}
					$oMatter->opData = $modelEnl->opData($oMatter, true);
				} else if ($oMatter->type === 'signin') {
					if (!isset($modelSig)) {
						$modelSig = $this->model('matter\signin');
					}
					$oMatter->opData = $modelSig->opData($oMatter, false);
				}
				/* 清理不必要的数据 */
				unset($oMatter->siteid);
				unset($oMatter->data_schemas);
				unset($oMatter->pages);
				unset($oMatter->create_at);
				unset($oMatter->creater_name);
				unset($oMatter->dataTags);
				unset($oMatter->opUrl);
				unset($oMatter->op_short_url_code);
				unset($oMatter->rpUrl);
				unset($oMatter->rp_short_url_code);
				unset($oMatter->is_public);
				unset($oMatter->rounds);

				$mattersByUser[] = $oMatter;
			}
		}

		return new \ResponseData($mattersByUser);
	}
	/**
	 * 项目下的用户信息
	 */
	public function userList_action($mission) {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$modelMis = $this->model('matter\mission');
		$oMission = $modelMis->byId($mission);
		if ($oMission === false) {
			return new \ObjectNotFoundError();
		}

		if (isset($oMission->user_app_id) && isset($oMission->user_app_type)) {
			$oUserSource = new \stdClass;
			$oUserSource->id = $oMission->user_app_id;
			$oUserSource->type = $oMission->user_app_type;
		} else {
			return new \ParameterError();
		}

		/* 获得用户 */
		switch ($oUserSource->type) {
		case 'enroll':
			$users = $this->model('matter\enroll\record')->enrolleeByApp($oUserSource, ['fields' => 'distinct userid,nickname', 'rid' => 'all']);
			break;
		case 'signin':
			$users = $this->model('matter\signin\record')->enrolleeByApp($oUserSource, ['fields' => 'distinct userid,nickname']);
			break;
		case 'mschema':
			$users = $this->model('site\user\member')->byMschema($oUserSource->id, ['fields' => 'userid,name,email,mobile']);
			foreach ($users as &$oUser) {
				$oUser->nickname = empty($oUser->name) ? (empty($oUser->email) ? $oUser->mobile : $oUser->email) : $oUser->name;
			}
			break;
		}

		if (empty($users)) {
			return new \ParameterError('没有获得项目中用户');
		}

		return new \ResponseData($users);
	}
	/**
	 * 获得用户在项目中的行为记录
	 */
	public function userTrack_action($mission, $user) {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$oWho = new \stdClass;
		$oWho->uid = $user;

		$modelMis = $this->model('matter\mission\matter');

		$mattersByUser = [];
		$mattersByMis = $modelMis->byMission($mission, null, ['is_public' => 'Y']);
		if (count($mattersByMis)) {
			foreach ($mattersByMis as $oMatter) {
				if (!in_array($oMatter->type, ['enroll', 'signin', 'article'])) {
					continue;
				}
				if ($oMatter->type === 'enroll') {
					if (!isset($modelEnlUsr)) {
						$modelEnlUsr = $this->model('matter\enroll\user');
					}
					$oUser = $modelEnlUsr->byId($oMatter, $oWho->uid);

					/* 清除不必要的数据 */
					unset($oUser->siteid);
					unset($oUser->aid);
					unset($oUser->userid);
					unset($oUser->id);

					$oMatter->user = $oUser;
				} else if ($oMatter->type === 'signin') {
					if (!isset($modelSigRec)) {
						$modelSigRec = $this->model('matter\signin\record');
					}
					$oApp = new \stdClass;
					$oApp->id = $oMatter->id;
					$oMatter->record = $modelSigRec->byUser($oWho, $oApp);
				}

				/* 清理不必要的数据 */
				unset($oMatter->siteid);
				unset($oMatter->data_schemas);
				unset($oMatter->pages);
				unset($oMatter->create_at);
				unset($oMatter->creater_name);
				unset($oMatter->dataTags);
				unset($oMatter->opUrl);
				unset($oMatter->op_short_url_code);
				unset($oMatter->rpUrl);
				unset($oMatter->rp_short_url_code);
				unset($oMatter->is_public);

				$mattersByUser[] = $oMatter;
			}
		}

		return new \ResponseData($mattersByUser);
	}
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
			$users = $this->model('matter\enroll\record')->enrolleeByApp($userSource, ['fields' => 'distinct userid,nickname', 'rid' => 'all']);
			break;
		case 'signin':
			$users = $this->model('matter\signin\record')->enrolleeByApp($userSource, ['fields' => 'distinct userid,nickname']);
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
					$oApp = new \stdClass;
					$oApp->id = $appId;
					$oUser = new \stdClass;
					$oUser->userid = $user;
					$modelEnlRec = $this->model('matter\enroll\record');
					$result = $modelEnlRec->byUser($oApp, $oUser);
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