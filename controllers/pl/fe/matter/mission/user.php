<?php
namespace pl\fe\matter\mission;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 项目控制器
 */
class user extends \pl\fe\matter\base {
	private $_modelUsr;
	/**
	 *
	 */
	public function __construct() {
		$this->_modelMis = $this->model('matter\mission');
	}
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/mission/frame');
		exit;
	}
	/**
	 * 获取项目中用户的行为数据
	 */
	public function list_action($mission, $page = null, $size = null) {
		$result = new \stdClass;
		$q = [
			'*',
			'xxt_mission_user',
			"mission_id={$mission}",
		];
		$q2 = ['o' => 'first_act_at'];
		if ($page && $size) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}
		$result->users = $this->_modelMis->query_objs_ss($q, $q2);
		if ($page && $size) {
			$q[0] = 'count(*)';
			$result->total = (int) $this->_modelMis->query_val_ss($q);
		} else {
			$result->total = count($result->users);
		}

		return new \ResponseData($result);
	}
	/**
	 * 从项目下的各种活动中提取用户数据
	 *
	 * @param int $id
	 */
	public function extract_action($mission) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		/* 检查权限 */
		$modelAcl = $this->model('matter\mission\acl');
		if (false === ($acl = $modelAcl->byCoworker($mission, $user->id))) {
			return new \ResponseError('项目不存在');
		}
		$mission = $this->_modelMis->byId($mission);

		/* 清空已有数据 */
		$this->_modelMis->delete('xxt_mission_user', "mission_id={$mission->id}");
		/* 指定了用户名单 */
		if (!empty($mission->user_app_id)) {
			$modelEnl = $this->model('matter\enroll');
			$userApp = $modelEnl->byId($mission->user_app_id);
			$qEnrollRcords = [
				'userid,nickname,enroll_at,enroll_key',
				'xxt_enroll_record',
				"state=1 and aid='{$mission->user_app_id}'",
			];
			$this->_extractFromOneEnroll($mission, $userApp, $qEnrollRcords, $modelEnl);
		}
		/* 从登记活动中提取 */
		$this->_extractFromEnroll($mission);
		/* 从签到活动中提取 */
		$this->_extractFromSignin($mission);
		/* 从分组活动中提取 */
		$this->_extractFromGroup($mission);

		return new \ResponseData('ok');
	}
	/**
	 *
	 */
	private function &_queryUser($missionId, $userId) {
		$q = [
			'*',
			'xxt_mission_user',
			"mission_id='{$missionId}' and userid='{$userId}'",
		];
		$user = $this->_modelMis->query_obj_ss($q);

		return $user;
	}
	/**
	 * 从登记活动中提取
	 */
	private function _extractFromEnroll(&$mission) {
		$modelEnl = $this->model('matter\enroll');
		$enrollApps = $modelEnl->byMission($mission->id, null, null);
		$qEnrollRcords = [
			'userid,nickname,enroll_at,enroll_key',
			'xxt_enroll_record',
		];
		foreach ($enrollApps->apps as $app) {
			if ($mission->user_app_id && $mission->user_app_id === $app->id) {
				continue;
			}
			$qEnrollRcords[2] = "state=1 and aid='{$app->id}'";
			$this->_extractFromOneEnroll($mission, $app, $qEnrollRcords, $modelEnl);
		}

		return true;
	}
	/**
	 *
	 */
	private function _extractFromOneEnroll(&$mission, &$app, &$qEnrollRcords, &$modelEnl) {
		$records = $modelEnl->query_objs_ss($qEnrollRcords);
		foreach ($records as $record) {
			if (empty($record->userid)) {
				continue;
			}
			if ($user = $this->_queryUser($mission->id, $record->userid)) {
				$enrollAct = json_decode($user->enroll_act);
				if (isset($enrollAct->{$app->id}) && isset($enrollAct->{$app->id}->{$record->enroll_key})) {
					/* 已经抓取过 */
					if ((int) $enrollAct->{$app->id}->{$record->enroll_key}->at !== $record->enroll_at) {
						/* 修改过登记记录 */
						$enrollAct->{$app->id}->{$record->enroll_key}->at = $record->enroll_at;
						$newAction = new \stdClass;
						$newAction->enroll_act = json_encode($enrollAct);
						$this->_modelMis->update('xxt_mission_user', $newAction, "id={$user->id}");
					}
				} else {
					/* 未抓取过的登记记录 */
					$newAction = new \stdClass;
					($user->first_act_at > $record->enroll_at) && $newAction->first_act_at = $record->enroll_at;
					($user->last_act_at < $record->enroll_at) && $newAction->last_act_at = $record->enroll_at;
					!isset($enrollAct->{$app->id}) && $enrollAct->{$app->id} = new \stdClass;
					$enrollAct->{$app->id}->{$record->enroll_key} = new \stdClass;
					$enrollAct->{$app->id}->{$record->enroll_key}->at = $record->enroll_at;
					$newAction->enroll_act = json_encode($enrollAct);
					$this->_modelMis->update('xxt_mission_user', $newAction, "id={$user->id}");
				}
			} else if (empty($mission->user_app_id) || $mission->user_app_id === $app->id) {
				/* 没有指定用户名单，或者是用户名单的应用，的情况下，添加新用户 */
				$newAction = new \stdClass;
				$newAction->siteid = $mission->siteid;
				$newAction->mission_id = $mission->id;
				$newAction->userid = $record->userid;
				$newAction->nickname = $record->nickname;
				$newAction->first_act_at = $record->enroll_at;
				$newAction->last_act_at = $record->enroll_at;
				$newAction->enroll_act = '{"' . $app->id . '":{"' . $record->enroll_key . '":{"at":' . $record->enroll_at . '}}}';

				$this->_modelMis->insert('xxt_mission_user', $newAction, false);
			}
		}
	}
	/**
	 * 从登记活动中提取
	 */
	private function _extractFromSignin(&$mission) {
		$modelSig = $this->model('matter\signin');
		$signinApps = (object) $modelSig->byMission($mission->id, null, null);
		$qSigninRecords = [
			'userid,nickname,enroll_key,enroll_at,signin_num,signin_log',
			'xxt_signin_record',
		];
		foreach ($signinApps->apps as $app) {
			$qSigninRecords[2] = "state=1 and aid='{$app->id}'";
			$records = $modelSig->query_objs_ss($qSigninRecords);
			foreach ($records as $record) {
				if (empty($record->userid)) {
					continue;
				}
				if ($user = $this->_queryUser($mission->id, $record->userid)) {
					$signinAct = empty($user->signin_act) ? new \stdClass : json_decode($user->signin_act);
					if (isset($signinAct->{$app->id}) && isset($signinAct->{$app->id}->{$record->enroll_key})) {
						/* 修改过登记记录 */
						$signinAct->{$app->id}->{$record->enroll_key}->num = $record->signin_num;
						$signinAct->{$app->id}->{$record->enroll_key}->log = json_decode($record->signin_log);
						$newAction = new \stdClass;
						$newAction->signin_act = json_encode($signinAct);
						$this->_modelMis->update('xxt_mission_user', $newAction, "id={$user->id}");
					} else {
						/* 未抓取过的登记记录 */
						$newAction = new \stdClass;
						($user->first_act_at > $record->enroll_at) && $newAction->first_act_at = $record->enroll_at;
						($user->last_act_at < $record->enroll_at) && $newAction->last_act_at = $record->enroll_at;
						!isset($signinAct->{$app->id}) && $signinAct->{$app->id} = new \stdClass;
						$signinAct->{$app->id}->{$record->enroll_key} = new \stdClass;
						$signinAct->{$app->id}->{$record->enroll_key}->num = $record->signin_num;
						$signinAct->{$app->id}->{$record->enroll_key}->log = json_decode($record->signin_log);
						$newAction->signin_act = json_encode($signinAct);
						$this->_modelMis->update('xxt_mission_user', $newAction, "id={$user->id}");
					}
				} else if (empty($mission->user_app_id)) {
					/* 没有指定用户名单的情况下，添加新用户 */
					$newAction = new \stdClass;
					$newAction->siteid = $mission->siteid;
					$newAction->mission_id = $mission->id;
					$newAction->userid = $record->userid;
					$newAction->nickname = $record->nickname;
					$newAction->first_act_at = $record->enroll_at;
					$newAction->last_act_at = $record->enroll_at;
					$newAction->signin_act = '{"' . $app->id . '":{"' . $record->enroll_key . '":{"num":' . $record->signin_num . ',"log":' . $record->signin_log . '}}}';

					$this->_modelMis->insert('xxt_mission_user', $newAction, false);
				}
			}
		}

		return true;
	}
	/**
	 * 从分组活动中提取
	 */
	private function _extractFromGroup(&$mission) {
		$modelGrp = $this->model('matter\group');
		$groupApps = (object) $modelGrp->byMission($mission->id, null, null);
		$qGroupRecords = [
			'userid,nickname,enroll_key,enroll_at,round_id,round_title',
			'xxt_group_player',
		];
		foreach ($groupApps->apps as $app) {
			$qGroupRecords[2] = "state=1 and aid='{$app->id}'";
			$records = $modelGrp->query_objs_ss($qGroupRecords);
			foreach ($records as $record) {
				if (empty($record->userid)) {
					continue;
				}
				if ($user = $this->_queryUser($mission->id, $record->userid)) {
					$groupAct = empty($user->group_act) ? new \stdClass : json_decode($user->group_act);
					if (isset($groupAct->{$app->id}) && isset($groupAct->{$app->id}->{$record->enroll_key})) {
						/* 修改过登记记录 */
						$groupAct->{$app->id}->{$record->enroll_key}->round = $record->round_title;
						$newAction = new \stdClass;
						$newAction->group_act = $modelGrp::toJson($groupAct);
						$this->_modelMis->update('xxt_mission_user', $newAction, "id={$user->id}");
					} else {
						/* 未抓取过的登记记录 */
						$newAction = new \stdClass;
						($user->first_act_at > $record->enroll_at) && $newAction->first_act_at = $record->enroll_at;
						($user->last_act_at < $record->enroll_at) && $newAction->last_act_at = $record->enroll_at;
						!isset($groupAct->{$app->id}) && $groupAct->{$app->id} = new \stdClass;
						$groupAct->{$app->id}->{$record->enroll_key} = new \stdClass;
						$groupAct->{$app->id}->{$record->enroll_key}->round = $record->round_title;
						$newAction->group_act = $modelGrp::toJson($groupAct);
						$this->_modelMis->update('xxt_mission_user', $newAction, "id={$user->id}");
					}
				} else if (empty($mission->user_app_id)) {
					/* 没有指定用户名单的情况下，添加新用户 */
					$newAction = new \stdClass;
					$newAction->siteid = $mission->siteid;
					$newAction->mission_id = $mission->id;
					$newAction->userid = $record->userid;
					$newAction->nickname = $record->nickname;
					$newAction->first_act_at = $record->enroll_at;
					$newAction->last_act_at = $record->enroll_at;
					$newAction->group_act = '{"' . $app->id . '":{"' . $record->enroll_key . '":{"round":' . $record->round_title . '}}}';

					$this->_modelMis->insert('xxt_mission_user', $newAction, false);
				}
			}
		}

		return true;
	}
}