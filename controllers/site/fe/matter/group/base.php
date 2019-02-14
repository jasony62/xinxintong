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
	 * 分组活动
	 */
	protected $team;
	/**
	 * 获得分组活动定义
	 */
	public function __construct() {
		parent::__construct();
		if (!empty($_GET['app'])) {
			$appId = $this->escape($_GET['app']);
			$oApp = $this->model('matter\group')->byId($appId);
			if ($oApp && $oApp->state !== '1') {
				$oApp = false;
			}
			$this->groupApp = $oApp;
		}
		if (!empty($_GET['team'])) {
			$teamId = $this->escape($_GET['team']);
			$oTeam = $this->model('matter\group\team')->byId($teamId);
			$this->team = $oTeam;
		}
	}
	/**
	 * 加入团队
	 */
	protected function join($oTeam, $oUser, $oData, $aOptions = []) {
		// 是否为组长
		$isLeader = isset($aOptions['isLeader']) ? (in_array($aOptions['isLeader'], ['Y', 'N']) ? $aOptions['isLeader'] : 'N') : 'N';

		$modelGrpRec = $this->model('matter\group\record');
		if ($modelGrpRec->isInTeam($oTeam->team_id, $oUser->uid)) {
			return [false, '用户已经在团队中，不允许重复加入'];
		}

		$current = time();
		$ek = $modelGrpRec->genKey($this->groupApp->siteid, $this->groupApp->id);
		$oGrpMem = new \stdClass;
		$oGrpMem->enroll_key = $ek;
		$oGrpMem->enroll_at = $current;
		$oGrpMem->team_id = $oTeam->team_id;
		$oGrpMem->team_title = $oTeam->title;
		$oGrpMem->is_leader = $isLeader;

		$modelGrpRec->enroll($this->groupApp, $this->who, $oGrpMem);
		$aResult = $modelGrpRec->setData($this->groupApp, $ek, $oData);
		if (false === $aResult[0]) {
			return new \ResponseError($aResult[1]);
		}
		$oGrpMem->data = json_decode($aResult[1]);
		//$oGrpMem->role_teams = [];

		$modelGrpRec->joinGroup($this->groupApp->id, $oTeam, $ek);

		return [true, $ek];
	}
}