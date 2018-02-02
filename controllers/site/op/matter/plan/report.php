<?php
namespace site\op\matter\plan;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/**
 * 登记活动报表
 */
class report extends \site\op\base {
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
		\TPL::output('/site/op/matter/plan/report');
		exit;
	}
	/**
	 * 统计登记信息
	 *
	 * 只统计single/multiple类型的数据项
	 *
	 * return
	 * name => array(l=>label,c=>count)
	 *
	 */
	public function get_action($site, $app, $taskSchmId = '', $actSchmId = '', $renewCache = 'Y') {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		
		// 如果指定了行动项需要获取行动项中得题目
		if (!empty($taskSchmId) && !empty($actSchmId)) {
			$modelTkSchm = $this->model('matter\plan\schema\task');
			$modelActSchm = $this->model('matter\plan\schema\action');

			$task = $modelTkSchm->byId($taskSchmId);
			$oApp->task = $task;
			if ($task === false) {
				return new \ResponseError('指定得任务不存在');
			}
			foreach ($task->actions as $action) {
				if ($action->id === $actSchmId) {
					foreach ($action->checkSchemas as $actSchm) {
						$oApp->checkSchemas[] = $actSchm;
					}
					break;
				}
			}
		}

		$stat = $this->model('matter\plan\task')->getStat($oApp, $taskSchmId, $actSchmId, $renewCache);

		$result = new \stdClass;
		$result->app = $oApp;
		$result->stat = $stat;

		return new \ResponseData($result);
	}
}