<?php
namespace site\fe\matter\group;

include_once dirname(__FILE__) . '/base.php';
/**
 * 邀请
 */
class invite extends base {
	/**
	 *
	 */
	public function index_action() {
		if (!$this->groupApp) {
			$this->outputError('分组活动不存在或不可用！');
		}
		\TPL::assign('title', $this->groupApp->title);
		\TPL::output('/site/fe/matter/group/invite');
		exit;
	}
	/**
	 *
	 */
	public function join_action($team) {
		if (!$this->groupApp) {
			return new \ObjectNotFoundError();
		}

		$modelGrpTeam = $this->model('matter\group\team');
		$oTeam = $modelGrpTeam->byId($team);
		if (false === $oTeam) {
			return new \ObjectNotFoundError();
		}

		$aJoinResult = $this->join($oTeam, $this->who);
		if (false === $aJoinResult[0]) {
			return new \ParameterError($aJoinResult[1]);
		}

		return new \ResponseData('ok');
	}
}