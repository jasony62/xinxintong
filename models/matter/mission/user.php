<?php
namespace matter\mission;
/**
 *
 */
class user_model extends \TMS_MODEL {
	/**
	 * 获得指定项目下指定用户的行为数据
	 */
	public function byId($oMission, $userid, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = [
			$fields,
			'xxt_mission_user',
			['mission_id' => $oMission->id, 'userid' => $userid],
		];

		$oUser = $this->query_obj_ss($q);

		return $oUser;
	}
	/**
	 * 添加一个项目用户
	 */
	public function add($oMission, $oUser, $data = []) {
		$oNewUsr = new \stdClass;
		$oNewUsr->siteid = $oMission->siteid;
		$oNewUsr->mission_id = $oMission->id;
		$oNewUsr->userid = $oUser->uid;
		$oNewUsr->group_id = empty($oUser->group_id) ? '' : $oUser->group_id;
		$oNewUsr->nickname = $this->escape($oUser->nickname);
		foreach ($data as $k => $v) {
			$oNewUsr->{$k} = $v;
		}
		$oNewUsr->id = $this->insert('xxt_mission_user', $oNewUsr, true);

		return $oNewUsr;
	}
	/**
	 *
	 */
	public function byMission(&$mission, $criteria = null, $options = null) {
		if (empty($mission->user_app_id) || empty($mission->user_app_type)) {
			return [false, '没有指定项目的用户清单活动'];
		}
		if ($mission->user_app_type !== 'enroll' && $mission->user_app_type !== 'signin') {
			return [false, '不支持的项目的用户清单活动类型'];
		}
		if ($mission->user_app_type === 'enroll') {
			$modelRec = $this->model('matter\enroll\record');
			$result = $modelRec->byApp($mission->user_app_id, $options, $criteria);
			if (!empty($result->records)) {
				/* 和登记活动关联的签到活动 */
				$modelSig = $this->model('matter\signin');
				$signinApps = $modelSig->byEnrollApp($mission->user_app_id, ['fields' => 'id,title']);
				if (count($signinApps)) {
					$mapOfSigninApps = [];
					foreach ($signinApps as $signinApp) {
						$mapOfSigninApps[$signinApp->id] = $signinApp;
					}
					$modelSigRec = $this->model('matter\signin\record');
					foreach ($result->records as &$record) {
						$signinRecords = $modelSigRec->byVerifiedEnrollKey($record->enroll_key, null, ['fields' => 'aid,signin_log']);
						if (count($signinRecords)) {
							$record->signinRecords = [];
							foreach ($signinRecords as $signinRecord) {
								if (isset($signinRecord->signin_log) && isset($mapOfSigninApps[$signinRecord->aid])) {
									$signinApp = $mapOfSigninApps[$signinRecord->aid];
									$signinRecord->signinLogs = [];
									foreach ($signinApp->rounds as $round) {
										if (isset($signinRecord->signin_log->{$round->rid})) {
											$signinLog = new \stdClass;
											$signinLog->roundTitle = $round->title;
											$signinLog->signinAt = $signinRecord->signin_log->{$round->rid};
											if (!empty($round->late_at)) {
												$signinLog->isLate = (int) $signinLog->signinAt > ((int) $round->late_at + 60);
											}
											$signinRecord->signinLogs[] = $signinLog;
										}
									}
									if (count($signinRecord->signinLogs)) {
										unset($signinRecord->signin_log);
										unset($signinRecord->aid);
										$signinRecord->app = $signinApp->title;
										$record->signinRecords[] = $signinRecord;
									}
								}
							}
						}
					}
				}
				/* 和登记活动关联的分组活动 */
				$modelGrp = $this->model('matter\group');
				$groupApps = $modelGrp->byEnrollApp($mission->user_app_id);
				if (count($groupApps)) {
					$mapOfGroupApps = [];
					foreach ($groupApps as $groupApp) {
						$mapOfGroupApps[$groupApp->id] = $groupApp;
					}
					$modelGrpPly = $this->model('matter\group\player');
					foreach ($result->records as &$record) {
						$groupRecords = $modelGrpPly->byEnrollKey($record->enroll_key, null, ['fields' => 'aid,round_title']);
						if (count($groupRecords)) {
							$record->groupRecords = [];
							foreach ($groupRecords as $groupRecord) {
								if (!empty($groupRecord->round_title)) {
									$groupRecord->app = $mapOfGroupApps[$groupRecord->aid]->title;
									unset($groupRecord->aid);
									$record->groupRecords[] = $groupRecord;
								}
							}
						}
					}
				}
			}
		} else if ($mission->user_app_type === 'signin') {
			$modelRec = $this->model('matter\signin\record');
			$result = $modelRec->byApp($mission->user_app_id, $options, $criteria);
			if (!empty($result->records)) {
				/* 和登记活动关联的分组活动 */
				$modelGrp = $this->model('matter\group');
				$groupApps = $modelGrp->bySigninApp($mission->user_app_id);
				if (count($groupApps)) {
					$mapOfGroupApps = [];
					foreach ($groupApps as $groupApp) {
						$mapOfGroupApps[$groupApp->id] = $groupApp;
					}
					//取出$mapOfGroupApps的所有健名
					$mapOfGroupAppsKeys = array_keys($mapOfGroupApps);

					$modelGrpPly = $this->model('matter\group\player');
					foreach ($result->records as &$record) {
						$groupRecords = $modelGrpPly->byEnrollKey($record->enroll_key, null, ['fields' => 'aid,round_title']);
						if (count($groupRecords)) {
							$record->groupRecords = [];
							foreach ($groupRecords as $groupRecord) {
								if (!empty($groupRecord->round_title)) {
									//因为分组活动在导入某个活动后撤销导入的活动时不会删除xxt_group_player中的数据，所以会存在有对应的数据但是没有对应的app的情况
									if (!in_array($groupRecord->aid, $mapOfGroupAppsKeys)) {
										continue;
									}
									$groupRecord->app = $mapOfGroupApps[$groupRecord->aid]->title;
									unset($groupRecord->aid);
									$record->groupRecords[] = $groupRecord;
								}
							}
						}
					}
				}
			}
		}

		return [true, $result];
	}
}