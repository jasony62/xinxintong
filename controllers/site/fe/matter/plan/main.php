<?php
namespace site\fe\matter\plan;

include_once dirname(__FILE__) . '/base.php';
/**
 * 计划活动
 */
class main extends base {
	/**
	 *
	 */
	public function index_action($app) {
		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app, ['fields' => 'id,siteid,title,entry_rule']);

		$this->checkEntryRule($oApp, true);

		if ($oApp) {
			\TPL::assign('title', $oApp->title);
		} else {
			\TPL::assign('title', '任务计划活动');
		}
		\TPL::output('/site/fe/matter/plan/task');
		exit;
	}
	/**
	 *
	 */
	public function get_action($app) {
		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app, ['fields' => 'id,siteid,mission_id,mission_phase_id,title,summary,pic,check_schemas,jump_delayed']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		$modelSchTsk = $this->model('matter\plan\schema\task');
		$tasks = $modelSchTsk->byApp($oApp->id, ['fields' => 'id,task_seq,title,jump_delayed,auto_verify,can_patch,as_placeholder']);
		$oApp->tasks = $tasks;

		$modelUsrTsk = $this->model('matter\plan\task');
		$oApp->lastUserTask = $modelUsrTsk->lastByApp($this->who, $oApp, ['fields' => 'id,task_schema_id,verified,born_at']);
		$oApp->nowTaskSchema = $modelUsrTsk->nowSchemaByApp($this->who, $oApp);

		$result = new \stdClass;
		$result->app = $oApp;

		return new \ResponseData($result);
	}
	/**
	 *
	 */
	public function nowTask_action($app) {
		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app, ['fields' => 'id,siteid,mission_id,mission_phase_id,title,summary,pic,check_schemas,jump_delayed']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$modelUsrTsk = $this->model('matter\plan\task');
		$oNowTaskSchema = $modelUsrTsk->nowSchemaByApp($this->who, $oApp);

		return new \ResponseData($oNowTaskSchema);
	}
}