<?php
namespace site\fe\matter\group;

include_once dirname(__FILE__) . '/base.php';
/**
 * 分组活动用户
 */
class user extends base {
	/**
	 *
	 */
	public function get_action($app) {
		$oApp = $this->model('matter\group')->byId($app, ['cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oUser = clone $this->who;

		$modelGrpRec = $this->model('matter\group\record');

		$records = $modelGrpRec->byUser($oApp, $oUser->uid, ['fields' => 'id,nickname,team_id,is_leader,role_teams']);
		$oUser->records = $records;

		return new \ResponseData($oUser);
	}
}