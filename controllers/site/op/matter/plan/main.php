<?php
namespace site\op\matter\plan;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/**
 *
 */
class main extends \site\op\base {
	/**
	 *
	 */
	public function index_action($app) {
		if (!$this->checkAccessToken()) {
			header('HTTP/1.0 500 parameter error:accessToken is invalid.');
			die('提供的令牌无效，或者令牌已经过期！');
		}
		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app, ['fields' => 'id,state,siteid,mission_id,mission_phase_id,title,summary,pic,check_schemas,jump_delayed']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		\TPL::assign('title', $oApp->title);
		\TPL::output('site/op/matter/plan/console');
		exit;
	}
	/**
	 *
	 *
	 * @param string $appid
	 */
	public function get_action($app) {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$modelApp = $this->model('matter\plan');
		$oApp = $modelApp->byId($app);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		/*包含的所有任务*/
		$oApp->taskSchemas = $this->model('matter\plan\schema\task')->byApp($oApp->id, ['fields' => 'id,title']);
		/* 指定分组活动访问 */
		$oEntryRule = $oApp->entryRule;
		if (isset($oEntryRule->scope->group) && $oEntryRule->scope->group === 'Y') {
			if (isset($oEntryRule->group)) {
				$oRuleApp = $oEntryRule->group;
				if (!empty($oRuleApp->id)) {
					$oGroupApp = $this->model('matter\group')->byId($oRuleApp->id, ['fields' => 'title', 'cascaded' => 'Y']);
					if ($oGroupApp) {
						$oRuleApp->title = $oGroupApp->title;
						if (!empty($oRuleApp->team->id)) {
							$oGrpTeam = $this->model('matter\group\team')->byId($oRuleApp->team->id, ['fields' => 'title']);
							if ($oGrpTeam) {
								$oRuleApp->team->title = $oGrpTeam->title;
							}
						}
						$oApp->groupApp = $oGroupApp;
						$oApp->oRuleApp = $oRuleApp;
					}
				}
			}
		}

		return new \ResponseData($oApp);
	}
}