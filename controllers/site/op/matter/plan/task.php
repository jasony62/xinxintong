<?php
namespace site\op\matter\plan;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/**
 *
 */
class task extends \site\op\base {
	/**
	 * 返回视图
	 */
	public function index_action($app) {
		if (!$this->checkAccessToken()) {
			header('HTTP/1.0 500 parameter error:accessToken is invalid.');
			die('没有获得有效访问令牌！');
		}

		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app, ['fields' => 'id,state,siteid,mission_id,mission_phase_id,title,summary,pic,check_schemas,jump_delayed']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		\TPL::assign('title', $oApp->title);
		\TPL::output('/site/op/matter/plan/console');
		exit;
	}
	/**
	 *
	 */
	public function list_action($site, $app, $page = 1, $size = 30) {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app, ['fields' => 'id,state,check_schemas']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		// 数据过滤条件
		$oCriteria = $this->getPostJson();

		$modelTsk = $this->model('matter\plan\task');
		$aOptions = ['fields' => 'id,born_at,patch_at,userid,group_id,nickname,verified,comment,first_enroll_at,last_enroll_at,task_schema_id,task_seq,data,score'];
		if (!empty($page) && !empty($size)) {
			$aOptions['paging'] = ['page' => $page, 'size' => $size];
		}
		$oResult = $modelTsk->byApp($oApp, $aOptions, $oCriteria);

		return new \ResponseData($oResult);
	}
	/**
	 * 选中记录通过审核
	 */
	public function batchVerify_action($app) {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app, ['fields' => 'id,state,siteid,title,summary,pic']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$updatedCount = 0;
		$taskIds = $this->getPostJson();
		if (!empty($taskIds)) {
			$modelTsk = $this->model('matter\plan\task');
			foreach ($taskIds as $taskId) {
				$rst = $modelTsk->update('xxt_plan_task', ['verified' => 'Y'], ['aid' => $oApp->id, 'id' => $taskId]);
				if ($rst === 1) {
					$updatedCount++;
				}
			}
		}

		// $this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'verify.batch', $taskIds);

		return new \ResponseData($updatedCount);
	}
	/**
	 * 所有记录通过审核
	 */
	public function verifyAll_action($app) {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app, ['fields' => 'id,state,siteid,title,summary,pic']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$rst = $modelApp->update(
			'xxt_plan_task',
			['verified' => 'Y'],
			['aid' => $oApp->id]
		);

		// 记录操作日志
		// $this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'verify.all');

		return new \ResponseData($rst);
	}
	/*
		*
	*/
	public function listSchema_action($app, $checkSchmId, $taskSchmId = '', $actSchmId = '', $page = '', $size = '') {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app, ['fields' => 'id,state,check_schemas']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		if (!empty($taskSchmId) && !empty($actSchmId)) {
			$taskSchema = $this->model('matter\plan\schema\task')->byId($taskSchmId);
			if ($taskSchema === false || $taskSchema->aid !== $oApp->id) {
				return new \ResponseError('指定的任务不匹配或不存在！');
			}

			$actions = [];
			foreach ($taskSchema->actions as $action) {
				$actions[$action->id] = $action;
			}

			if (!empty($actions)) {
				if (!isset($actions[$actSchmId])) {
					return new \ResponseError('指定的行动项不匹配或不存在！');
				}
				foreach ($actions[$actSchmId]->checkSchemas as $acSchm) {
					$oApp->checkSchemas[] = $acSchm;
				}
			}
		}

		$modelTsk = $this->model('matter\plan\task');
		$aOptions = ['fields' => 'id,born_at,patch_at,userid,group_id,nickname,verified,comment,first_enroll_at,last_enroll_at,task_schema_id,task_seq,data,score'];

		if (!empty($page) && !empty($size)) {
			$aOptions['paging'] = ['page' => $page, 'size' => $size];
		}
		$oResult = $modelTsk->listSchema($oApp, $checkSchmId, $taskSchmId, $actSchmId, $aOptions);

		return new \ResponseData($oResult);
	}
	/**
	 * 删除一条登记信息
	 */
	public function remove_action($site, $app, $taskId) {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		// $rst = $this->model('matter\plan\task')->remove($app, $taskId);

		// 记录操作日志
		// $app = $this->model('matter\plan')->byId($app);
		// $app->type = 'plan';
		// $this->model('matter\log')->matterOp($site, $user, $app, 'removeTask', $key);

		return new \ResponseData('loading');
	}
	/**
	 * 计算指定登记项所有记录的合计
	 * 若不指定登记项，则返回活动中所有数值型登记项的合集
	 * 若指定的登记项不是数值型，返回0
	 */
	public function sum4Schema_action($site, $app, $taskSchmId = '', $actSchmId = '') {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		// 记录活动
		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app, ['fields' => 'id,state,check_schemas']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		if (!empty($taskSchmId) && !empty($actSchmId)) {
			$taskSchema = $this->model('matter\plan\schema\task')->byId($taskSchmId);
			if ($taskSchema === false || $taskSchema->aid !== $oApp->id) {
				return new \ResponseError('指定的任务不匹配或不存在！');
			}

			$actions = [];
			foreach ($taskSchema->actions as $action) {
				$actions[$action->id] = $action;
			}

			if (!empty($actions)) {
				if (!isset($actions[$actSchmId])) {
					return new \ResponseError('指定的行动项不匹配或不存在！');
				}
				foreach ($actions[$actSchmId]->checkSchemas as $acSchm) {
					$oApp->checkSchemas[] = $acSchm;
				}
			}
		}

		// 查询结果
		$mdoelRec = $this->model('matter\plan\task');
		$result = $mdoelRec->sum4Schema($oApp, $taskSchmId, $actSchmId);

		return new \ResponseData($result);
	}
}