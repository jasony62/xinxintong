<?php
namespace site\fe\matter\group;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 活动
 */
class base extends \site\fe\matter\base {
	/**
	 * 分组活动
	 */
	protected $groupApp;
	/**
	 * 获得分组活动定义
	 */
	public function __construct() {
		parent::__construct();
		if (!empty($_GET['app'])) {
			$oApp = $this->model('matter\group')->byId($_GET['app']);
			if ($oApp && $oApp->state !== '1') {
				$oApp = false;
			}
			$this->groupApp = $oApp;
		}
	}
	/**
	 * 加入团队
	 */
	protected function join($oTeam, $oUser, $aOptions = []) {
		// 是否为组长
		$isLeader = isset($aOptions['isLeader']) ? (in_array($aOptions['isLeader'], ['Y', 'N']) ? $aOptions['isLeader'] : 'N') : 'N';

		$modelGrpRec = $this->model('matter\group\record');
		if ($modelGrpRec->isInTeam($oTeam->team_id, $oUser->uid)) {
			return [false, '用户已经在团队中，不允许重复加入'];
		}

		$current = time();
		$ek = $modelGrpRec->genKey($this->groupApp->siteid, $this->groupApp->id);
		$oGrpUser = new \stdClass;
		$oGrpUser->enroll_key = $ek;
		$oGrpUser->enroll_at = $current;
		$oGrpUser->team_id = $oTeam->team_id;
		$oGrpUser->team_title = $oTeam->title;
		$oGrpUser->is_leader = $isLeader;

		$modelGrpRec->enroll($this->groupApp, $this->who, $oGrpUser);
		// $aResult = $modelGrpRec->setData($this->groupApp, $ek, $oPosted->data);
		// if (false === $aResult[0]) {
		// 	return new \ResponseError($aResult[1]);
		// }
		// $oGrpUser->data = json_decode($aResult[1]);
		// $oGrpUser->role_teams = [];

		$modelGrpRec->joinGroup($this->groupApp->id, $oTeam, $ek);

		return [true, $ek];
	}
}