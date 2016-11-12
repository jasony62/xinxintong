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
	 * 从项目下的各种活动中提取用户数据
	 *
	 * @param int $id
	 */
	public function extract_action($id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		/* 检查权限 */
		$modelAcl = $this->model('matter\mission\acl');
		if (false === ($acl = $modelAcl->byCoworker($id, $user->id))) {
			return new \ResponseError('项目不存在');
		}
		$mission = $this->_modelMis->byId($id);
		/* 从登记活动中提取 */
		$this->_extractFromEnroll($mission);
		/* 从签到活动中提取 */
		$this->_extractFromSignin($mission);

		return new \ResponseData('ok');
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
			$qEnrollRcords[2] = "state=1 and aid='{$app->id}'";
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
						/* 未抓去过的登记记录 */
						$newAction = new \stdClass;
						($user->first_act_at > $record->enroll_at) && $newAction->first_act_at = $record->enroll_at;
						($user->last_act_at < $record->enroll_at) && $newAction->last_act_at = $record->enroll_at;
						!isset($enrollAct->{$app->id}) && $enrollAct->{$app->id} = new \stdClass;
						$enrollAct->{$app->id}->{$record->enroll_key} = new \stdClass;
						$enrollAct->{$app->id}->{$record->enroll_key}->at = $record->enroll_at;
						$newAction->enroll_act = json_encode($enrollAct);
						$this->_modelMis->update('xxt_mission_user', $newAction, "id={$user->id}");
					}
				} else {
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

		return true;
	}
	/**
	 * 从登记活动中提取
	 */
	private function _extractFromSignin(&$mission) {
		$modelSig = $this->model('matter\signin');
		$signinApps = (object) $modelSig->byMission($mission->id, null, null);
		$qSigninRcords = [
			'userid,nickname,enroll_key,enroll_at,signin_num,signin_log',
			'xxt_signin_record',
		];
		foreach ($signinApps->apps as $app) {
			$qSigninRcords[2] = "state=1 and aid='{$app->id}'";
			$records = $modelSig->query_objs_ss($qSigninRcords);
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
						/* 未抓去过的登记记录 */
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
				} else {
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
}