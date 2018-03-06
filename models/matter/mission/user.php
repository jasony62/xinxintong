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
		if ($oUser) {
			if (property_exists($oUser, 'modify_log')) {
				$oUser->modify_log = empty($oUser->modify_log) ? [] : json_decode($oUser->modify_log);
			}
		}

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
			switch ($k) {
			case 'modify_log':
				if (!is_string($v)) {
					$oNewUsr->{$k} = json_encode($v);
				}
				break;
			default:
				$oNewUsr->{$k} = $v;
			}
		}
		$oNewUsr->id = $this->insert('xxt_mission_user', $oNewUsr, true);

		return $oNewUsr;
	}
	/**
	 * 修改用户数据
	 */
	public function modify($oBeforeData, $oUpdatedData) {
		$aDbData = [];
		foreach ($oUpdatedData as $field => $value) {
			switch ($field) {
			case 'last_enroll_at':
			case 'last_like_at':
			case 'last_like_other_at':
			case 'last_recommend_at':
				$aDbData[$field] = $value;
				break;
			case 'enroll_num':
			case 'like_num':
			case 'like_other_num':
			case 'recommend_num':
			case 'user_total_coin':
				$aDbData[$field] = (int) $oBeforeData->{$field}+$value;
				break;
			case 'modify_log':
				$oBeforeData->modify_log[] = $value;
				$aDbData['modify_log'] = json_encode($oBeforeData->modify_log);
				break;
			}
		}

		$rst = $this->update('xxt_mission_user', $aDbData, ['id' => $oBeforeData->id]);

		return $rst;
	}
	/**
	 * 删除1条记录
	 */
	public function removeRecord($missionId, $oRecord) {
		if (empty($missionId) || empty($oRecord->userid)) {
			return [false, '参数不完整'];
		}

		$rst = $this->update(
			'xxt_mission_user',
			['enroll_num' => (object) ['op' => '-=', 'pat' => 1]],
			['mission_id' => $missionId, 'userid' => $oRecord->userid, 'enroll_num' => (object) ['op' => '>', 'pat' => 0]]
		);

		return [true, $rst];
	}
	/**
	 * 恢复1条记录
	 */
	public function restoreRecord($missionId, $oRecord) {
		if (empty($missionId) || empty($oRecord->userid)) {
			return [false, '参数不完整'];
		}

		$rst = $this->update(
			'xxt_mission_user',
			['enroll_num' => (object) ['op' => '+=', 'pat' => 1]],
			['mission_id' => $missionId, 'userid' => $oRecord->userid]
		);

		return [true, $rst];
	}
	/**
	 * 参与过活动任务的用户
	 */
	public function enrolleeByMission($oMission, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
		$q = [
			$fields,
			'xxt_mission_user',
			['mission_id' => $oMission->id],
		];

		/* 筛选条件 */
		if (isset($aOptions['filter'])) {
			$oFilter = $aOptions['filter'];
			if (!empty($oFilter->by) && !empty($oFilter->keyword)) {
				$q[2][$oFilter->by] = (object) ['op' => 'like', 'pat' => '%' . $oFilter->keyword . '%'];
			}
		}
		$q2 = [];
		/* 排序规则 */
		if (!empty($aOptions['orderBy'])) {
			$q2['o'] = $aOptions['orderBy'] . ' desc';
		}

		$oUsers = $this->query_objs_ss($q, $q2);

		return $oUsers;
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
	/**
	 * 项目用户获得奖励积分
	 */
	public function awardCoin($oMission, $userid, $deltaCoin) {
		$oMisUsr = $this->byId($oMission, $userid, ['fields' => 'id,userid,nickname,user_total_coin']);
		if (false === $oMisUsr) {
			return false;
		}
		$modelMisUsr->update(
			'xxt_mission_user',
			['user_total_coin' => (int) $oMisUsr->user_total_coin + $deltaCoin],
			['id' => $oMisUsr->id]
		);

		return true;
	}
	/**
	 * 项目用户扣除奖励积分
	 */
	public function deductCoin($oMission, $userid, $deductCoin) {
		$oMisUsr = $this->byId($oMission, $userid, ['fields' => 'id,userid,nickname,user_total_coin']);
		if (false === $oMisUsr) {
			return false;
		}
		$modelMisUsr->update(
			'xxt_mission_user',
			['user_total_coin' => (int) $oMisUsr->user_total_coin - $deductCoin],
			['id' => $oMisUsr->id]
		);

		return true;
	}
}