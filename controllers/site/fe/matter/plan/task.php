<?php
namespace site\fe\matter\plan;

include_once dirname(__FILE__) . '/base.php';
/**
 * 计划活动
 */
class task extends base {
	/**
	 *
	 */
	public function get_action($task) {
		$modelSchTsk = $this->model('matter\plan\schema\task');
		$task = $modelSchTsk->escape($task);
		$oTaskSchema = $modelSchTsk->byId($task, ['fields' => 'id,title,task_seq,jump_delayed,can_patch']);
		if (false === $oTaskSchema) {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->who;

		$modelUsrTsk = $this->model('matter\plan\task');
		$oTaskSchema->userTask = $modelUsrTsk->bySchema($oUser, $oTaskSchema, ['fields' => 'id,verified,comment,born_at,patch_at,first_enroll_at,last_enroll_at,data,supplement']);

		return new \ResponseData($oTaskSchema);
	}
	/**
	 * 获得指定活动下已经完成的任务
	 */
	public function listByUser_action($app, $withMock = 'Y') {
		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app, ['fields' => 'id,state,notweekend']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->who;

		$modelUsrTsk = $this->model('matter\plan\task');
		$oResult = $modelUsrTsk->byUser($oApp, $oUser, ['fields' => 'id,task_schema_id,task_seq,born_at,patch_at,first_enroll_at,last_enroll_at,verified,comment']);
		if ($withMock === 'Y') {
			$modelSchTsk = $this->model('matter\plan\schema\task');
			$lastSeq = $modelSchTsk->lastSeq($oApp->id);
			$tasks = $oResult->tasks;
			if (count($tasks)) {
				$oResult->mocks = [];
				$oPrevTask = null;
				foreach ($tasks as $oTask) {
					if ($oPrevTask && ($oTask->task_seq - $oPrevTask->task_seq > 1)) {
						$mocks = $modelSchTsk->bornMock($oApp, $oUser, $oPrevTask->task_seq + 1, $oTask->task_seq - 1, $oPrevTask->born_at);
						$oResult->mocks = array_merge($oResult->mocks, $mocks);
					}
					$oPrevTask = $oTask;
				}
				if ($oPrevTask->task_seq < $lastSeq) {
					$mocks = $modelSchTsk->bornMock($oApp, $oUser, $oPrevTask->task_seq + 1, $lastSeq, $oPrevTask->born_at);
					$oResult->mocks = array_merge($oResult->mocks, $mocks);
				}
			} else {
				$startAt = $modelUsrTsk->getStartAt($oApp, $oUser);
				$oResult->mocks = $modelSchTsk->bornMock($oApp, $oUser, 1, $lastSeq, $startAt);
			}
		}

		return new \ResponseData($oResult);
	}
	/**
	 * 分段上传文件
	 *
	 * @param string $app
	 * @param string $submitKey
	 *
	 */
	public function uploadFile_action($app, $submitkey = '') {
		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app, ['fields' => 'id,siteid,state']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		if (empty($submitkey)) {
			$submitkey = $this->who->uid;
		}
		/**
		 * 分块上传文件
		 */
		$dest = '/plan/' . $oApp->id . '/' . $submitkey . '_' . $_POST['resumableFilename'];
		$oResumable = $this->model('fs/resumable', $oApp->siteid, $dest, '_user');
		$oResumable->handleRequest($_POST);

		return new \ResponseData('ok');
	}
	/**
	 * 提交任务数据
	 */
	public function submit_action($task) {
		$taskSchmId = $this->escape($task);
		$modelSchTsk = $this->model('matter\plan\schema\task');

		$oTaskSchema = $modelSchTsk->byId($taskSchmId, ['fields' => 'id,siteid,aid,title,task_seq,born_mode,born_offset,auto_verify,can_patch']);
		if (false === $oTaskSchema) {
			return new \ObjectNotFoundError();
		}

		$oApp = $this->model('matter\plan')->byId($oTaskSchema->aid, ['fields' => 'id,siteid,title,summary,entry_rule,jump_delayed,auto_verify,can_patch,check_schemas,mission_id']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$current = time();
		$oUser = clone $this->who;

		/* 用户的分组信息 */
		if ($oGroup = $this->getUserGroup($oApp)) {
			$oUser->group_id = $oGroup->team_id;
			$oUser->group_title = $oGroup->team_title;
		} else {
			$oUser->group_id = '';
			$oUser->group_title = '';
		}

		$bNewTask = false;
		$modelUsrTsk = $this->model('matter\plan\task');
		$oUsrTask = $modelUsrTsk->bySchema($oUser, $oTaskSchema);
		if (false === $oUsrTask) {
			$aResult = $modelUsrTsk->create($oUser, $oApp, $oTaskSchema);
			if (false === $aResult[0]) {
				return new \ResponseError($aResult[1]);
			}
			$oUsrTask = $aResult[1];
			$bNewTask = true;
		}
		if ($oUsrTask->born_at > $current) {
			return new \ResponseError('任务还没有开始，不能提交数据');
		}

		$oActionsById = new \stdClass;
		foreach ($oTaskSchema->actions as $oAction) {
			$oActionsById->{$oAction->id} = $oAction;
		}

		$modelUsrAct = $this->model('matter\plan\action');
		$oPosted = $this->getPostJson();
		/**
		 * 处理提交的任务数据
		 */
		$oTaskData = $oPosted->data;
		$oUsrTask->last_enroll_at = $current;
		$oCheckData = new \stdClass;
		$oScoreData = new \stdClass;
		$fScoreSum = 0; // 所有任务的累积得分
		foreach ($oTaskData as $actionId => $oActionData) {
			$oAction = $oActionsById->{$actionId};
			$oAction->siteid = $oTaskSchema->siteid; // 保存数据时需要这个参数
			if (count($oApp->checkSchemas)) {
				$oAction->checkSchemas = array_merge($oAction->checkSchemas, $oApp->checkSchemas);
			}
			$oResult = $modelUsrAct->setData($oUser, $oAction, $oUsrTask, $oActionData);
			$oCheckData->{$actionId} = $oResult->dbData;
			$oScoreData->{$actionId} = $oResult->score;
			$fScoreSum += $oResult->score->sum;
		}
		/**
		 * 提交补充说明
		 */
		$aUpdated = [];
		if (isset($oPosted->supplement) && count(get_object_vars($oPosted->supplement))) {
			$oTaskSupl = $oPosted->supplement;
			foreach ($oTaskSupl as $actionId => $oActionSupl) {
				$oAction = $oActionsById->{$actionId};
				$modelUsrAct->setSupplement($oUser, $oAction, $oUsrTask, $oActionSupl);
			}
			$aUpdated['supplement'] = $modelUsrAct->escape($modelUsrAct->toJson($oTaskSupl));
		}
		/**
		 * 更新任务状态
		 */
		$aUpdated['last_enroll_at'] = $oUsrTask->last_enroll_at;
		$aUpdated['data'] = $modelUsrTsk->escape($modelUsrTsk->toJson($oCheckData));
		$aUpdated['score'] = $modelUsrTsk->escape($modelUsrTsk->toJson($oScoreData));
		if (isset($oUser->group_id) && $oUser->group_id !== $oUsrTask->group_id) {
			$aUpdated['group_id'] = $oUser->group_id;
		}
		if ($bNewTask) {
			$aUpdated['first_enroll_at'] = $oUsrTask->last_enroll_at;
		}
		if ($oTaskSchema->auto_verify === 'Y' || ($oTaskSchema->auto_verify === 'U' && $oApp->auto_verify === 'Y')) {
			$oUsrTask->verified = $aUpdated['verified'] = 'Y';
		} else {
			$oUsrTask->verified = $aUpdated['verified'] = 'N';
		}
		$rst = $modelUsrTsk->update(
			'xxt_plan_task',
			$aUpdated,
			['id' => $oUsrTask->id]
		);

		/* 更新用户数据 */
		$aUsrData = ['last_enroll_at' => $oUsrTask->last_enroll_at, 'score' => $fScoreSum];
		if ($bNewTask) {
			$aUsrData['task_num'] = 1;
			// 用户首次填写增加积分
			$aUsrData['coinAct'] = 'site.matter.plan.submit';
		}

		$modelPUser = $this->model('matter\plan\user')->setOnlyWriteDbConn(true);
		$modelPUser->createOrUpdate($oApp, $oUser, $aUsrData);

		/* 记录操作日志 */
		$operation = new \stdClass;
		$operation->name = $bNewTask ? 'submit' : 'updateData';
		$oCheckData->task_schema_id = $taskSchmId;
		$operation->data = $oCheckData;
		$this->_logUserOp($oApp, $operation, $oUser);

		return new \ResponseData($oUsrTask);
	}
	/**
	 * 记录用户提交日志
	 *
	 * @param object $app
	 *
	 */
	private function _logUserOp($oApp, $operation, $user) {
		$modelLog = $this->model('matter\log');

		$logUser = new \stdClass;
		$logUser->userid = $user->uid;
		$logUser->nickname = $user->nickname;

		$client = new \stdClass;
		$client->agent = $_SERVER['HTTP_USER_AGENT'];
		$client->ip = $this->client_ip();

		$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

		$logid = $modelLog->addUserMatterOp($oApp->siteid, $logUser, $oApp, $operation, $client, $referer);

		return $logid;
	}
}