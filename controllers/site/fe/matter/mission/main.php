<?php
namespace site\fe\matter\mission;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 项目
 */
class main extends \site\fe\matter\base {
	/**
	 *
	 */
	public function index_action($mission) {
		$oMission = $this->model('matter\mission')->byId($mission, ['fields' => 'siteid,id,title,entry_rule']);
		if (false === $oMission) {
			return new \ObjectNotFoundError();
		}

		/* 检查是否需要第三方社交帐号OAuth */
		if (!$this->afterSnsOAuth()) {
			$this->requireSnsOAuth($oMission);
		}

		$this->checkEntryRule($oMission, true);

		\TPL::output('/site/fe/matter/mission/main');
		exit;
	}
	/**
	 *
	 */
	public function board_action($mission) {
		$oMission = $this->model('matter\mission')->byId($mission, ['fields' => 'siteid,id,title,entry_rule']);
		if (false === $oMission) {
			return new \ObjectNotFoundError();
		}

		/* 检查是否需要第三方社交帐号OAuth */
		if (!$this->afterSnsOAuth()) {
			$this->requireSnsOAuth($oMission);
		}

		$this->checkEntryRule($oMission, true);

		\TPL::output('/site/fe/matter/mission/board');
		exit;
	}
	/**
	 * 获得指定的任务
	 *
	 * @param int $id
	 */
	public function get_action($mission) {
		$oUser = $this->who;

		$oMission = $this->model('matter\mission')->byId($mission, ['fields' => 'id,title,summary,pic,user_app_id,user_app_type']);
		if (false === $oMission) {
			return new \ObjectNotFoundError();
		}
		$oMission->user = $oUser;
		/**
		 * 如果项目指定了分组活动作为项目的用户名单，获得当前用户所属的分组，是否为组长，及同组成员
		 */
		if ($oMission->user_app_type === 'group') {
			$modelGrpUsr = $this->model('matter\group\player');
			$oGrpApp = (object) ['id' => $oMission->user_app_id];
			$oGrpUsr = $modelGrpUsr->byUser($oGrpApp, $oUser->uid, ['fields' => 'is_leader,round_id,round_title,userid,nickname', 'onlyOne' => true]);
			if ($oGrpUsr) {
				$others = $modelGrpUsr->byRound($oMission->user_app_id, $oGrpUsr->round_id, ['fields' => 'is_leader,userid,nickname']);
				$oMission->groupUser = $oGrpUsr;
				$oMission->groupOthers = [];
				foreach ($others as $other) {
					if ($other->userid !== $oGrpUsr->userid) {
						$oMission->groupOthers[] = $other;
					}
				}
			}
		}

		return new \ResponseData($oMission);
	}
	/**
	 * 获得用户在项目中的行为记录
	 */
	public function userTrack_action($mission, $user = null) {
		$oMission = $this->model('matter\mission')->byId($mission, ['fields' => 'id,title,summary,pic,user_app_id,user_app_type']);
		if (false === $oMission) {
			return new \ObjectNotFoundError();
		}

		if (empty($user) || $this->who->uid === $user) {
			$oUser = $this->who;
		} else {
			/**
			 * 获得指定用户的数据
			 * 1、项目指定了分组活动作为用户名单；
			 * 2、当前用户和指定用户在一个分组中；
			 * 3、当前用户是分组的组长；
			 * 4、只能查看指定为这个分组用户参与的活动。
			 */
			if ($oMission->user_app_type !== 'group') {
				return new \ParameterError('只有指定了分组活动作为用户名单的项目才能查看同组成员的数据');
			}
			$modelGrpUsr = $this->model('matter\group\player');
			$oGrpApp = (object) ['id' => $oMission->user_app_id];
			$oGrpLeader = $modelGrpUsr->byUser($oGrpApp, $this->who->uid, ['fields' => 'is_leader,round_id', 'onlyOne' => true]);
			if (false === $oGrpLeader || $oGrpLeader->is_leader !== 'Y') {
				return new \ParameterError('只有组长才能查看组内成员的数据');
			}
			$oGrpUser = $modelGrpUsr->byUser($oGrpApp, $user, ['fields' => 'round_id', 'onlyOne' => true]);
			if (false === $oGrpUser || $oGrpLeader->round_id !== $oGrpUser->round_id) {
				return new \ParameterError('只能查看同组内成员的数据');
			}
			$oUser = (object) ['uid' => $user];
		}
		$modelMisMat = $this->model('matter\mission\matter');

		$mattersByUser = [];
		$mattersByMis = $modelMisMat->byMission($oMission->id, null, ['is_public' => 'Y', 'byTime' => 'running']);
		if (count($mattersByMis)) {
			foreach ($mattersByMis as $oMatter) {
				if (!in_array($oMatter->type, ['enroll', 'signin', 'plan', 'article', 'memberschema'])) {
					continue;
				}
				if (isset($oGrpLeader)) {
					/* 只能查看分配给分组的活动数据 */
					if ($oMatter->type !== 'enroll') {
						continue;
					}
					if ($this->getDeepValue($oMatter->entryRule, 'group.round.id') !== $oGrpLeader->round_id) {
						continue;
					}
				}
				if ($oMatter->type === 'enroll') {
					/* 用户身份是否匹配活动进入规则 */
					if ($this->getDeepValue($oMatter->entryRule, 'scope.group') === 'Y') {
						$bMatched = false;
						$oEntryRule = $oMatter->entryRule;
						if (isset($oEntryRule->group->id)) {
							$oGroupApp = $oEntryRule->group;
							$oGroupUsr = $this->model('matter\group\user')->byUser($oGroupApp, $oUser->uid, ['fields' => 'round_id,round_title,role_rounds', 'onlyOne' => true]);
							if ($oGroupUsr) {
								if (isset($oGroupApp->round->id)) {
									if ($oGroupUsr->round_id === $oGroupApp->round->id) {
										$bMatched = true;
									} else if (count($oGroupUsr->role_rounds) && in_array($oGroupApp->round->id, $oGroupUsr->role_rounds)) {
										$bMatched = true;
									}
								} else {
									$bMatched = true;
								}
							}
						}
						if (false === $bMatched) {
							continue;
						}
					}

					if (!isset($modelEnlUsr)) {
						$modelEnlUsr = $this->model('matter\enroll\user');
					}
					$oUserData = $modelEnlUsr->byId($oMatter, $oUser->uid);

					/* 清除不必要的数据 */
					unset($oUserData->siteid);
					unset($oUserData->aid);
					unset($oUserData->userid);
					unset($oUserData->id);

					$oMatter->user = $oUserData;
				} else if ($oMatter->type === 'signin') {
					if (!isset($modelSigRec)) {
						$modelSigRec = $this->model('matter\signin\record');
					}
					$oApp = new \stdClass;
					$oApp->id = $oMatter->id;
					$oMatter->record = $modelSigRec->byUser($oUser, $oApp);
				}

				/* 清理不必要的数据 */
				unset($oMatter->siteid);
				unset($oMatter->data_schemas);
				unset($oMatter->pages);
				unset($oMatter->create_at);
				unset($oMatter->creater_name);
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
	 * 获得用户在项目中的行为记录
	 */
	public function recordList_action($mission) {
		$modelMisMat = $this->model('matter\mission\matter');
		$matters = $modelMisMat->byMission($mission, null, ['is_public' => 'Y']);
		if (count($matters)) {
			foreach ($matters as &$matter) {
				if ($matter->type === 'enroll') {
					if (!isset($modelEnlRec)) {
						$modelEnlRec = $this->model('matter\enroll\record');
					}
					$matter->records = [];
					$records = $modelEnlRec->byUser($matter, $this->who);
					foreach ($records as $record) {
						$matter->records[] = $record;
					}
				} else if ($matter->type === 'signin') {
					if (!isset($modelSigRec)) {
						$modelSigRec = $this->model('matter\signin\record');
					}
					$oApp = new \stdClass;
					$oApp->id = $matter->siteid;
					$matter->record = $modelSigRec->byUser($this->who, $oApp);
				} else if ($matter->type === 'group') {
					if (!isset($modelGrpRec)) {
						$modelGrpRec = $this->model('matter\group\player');
					}
					$matter->records = [];
					$records = $modelGrpRec->byUser($matter, $this->who->uid);
					foreach ($records as $record) {
						!empty($record->data) && $record->data = json_decode($record->data);
						$matter->records[] = $record;
					}
				}
			}
		}

		return new \ResponseData($matters);
	}

	/**
	 * 获得用户在项目中的行为记录
	 */
	public function recordList2_action($mission) {
		$result = new \stdClass;

		$appIds = [];
		$modelEnlRec = $this->model('matter\enroll\record');
		$records = $modelEnlRec->byMission($mission, ['userid' => $this->who->uid]);
		if (count($records)) {
			$result->enroll = new \stdClass;
			$result->enroll->records = $records;
			foreach ($records as $record) {
				$appIds[$record->aid] = true;
			}
			$appIds = array_keys($appIds);
			$modelApp = $this->model('matter\enroll');
			$result->enroll->apps = [];
			foreach ($appIds as $appId) {
				$app = $modelApp->byId($appId, ['fields' => 'id,siteid,title,summary,pic,data_schemas', 'cascaded' => 'N']);
				$app->data_schemas = json_decode($app->data_schemas);
				$result->enroll->apps[] = $app;
			}
		}

		$appIds = [];
		$modelSigRec = $this->model('matter\signin\record');
		$records = $modelSigRec->byMission($mission, ['userid' => $this->who->uid]);
		if (count($records)) {
			$result->signin = new \stdClass;
			$result->signin->records = $records;
			foreach ($records as $record) {
				$appIds[$record->aid] = true;
			}
			$appIds = array_keys($appIds);
			$modelApp = $this->model('matter\signin');
			$modelSigRnd = $this->model('matter\signin\round');
			$result->signin->apps = [];
			foreach ($appIds as $appId) {
				$app = $modelApp->byId($appId, ['fields' => 'id,siteid,title,summary,pic,data_schemas', 'cascaded' => 'N']);
				$app->data_schemas = json_decode($app->data_schemas);
				$app->rounds = $modelSigRnd->byApp($appId);
				$result->signin->apps[] = $app;
			}
		}

		$appIds = [];
		$modelGrpRec = $this->model('matter\group\player');
		$records = $modelGrpRec->byMission($mission, ['userid' => $this->who->uid]);
		if (count($records)) {
			$result->group = new \stdClass;
			$result->group->records = $records;
			foreach ($records as $record) {
				$appIds[$record->aid] = true;
			}
			$appIds = array_keys($appIds);
			$modelApp = $this->model('matter\group');
			$result->group->apps = [];
			foreach ($appIds as $appId) {
				$app = $modelApp->byId($appId, ['fields' => 'id,title,summary', 'cascaded' => 'N']);
				$result->group->apps[] = $app;
			}
		}

		return new \ResponseData($result);
	}
}