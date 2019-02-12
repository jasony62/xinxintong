<?php
namespace site\fe\matter\group;

include_once dirname(__FILE__) . '/base.php';
/**
 * 团队
 */
class team extends base {
	/**
	 *
	 */
	public function index_action() {
		if (!$this->groupApp) {
			$this->outputError('分组活动不存在或不可用！');
		}
		\TPL::assign('title', $this->groupApp->title);
		\TPL::output('/site/fe/matter/group/team');
		exit;
	}
	/**
	 *
	 */
	public function get_action($team) {
		if (!$this->groupApp) {
			return new \ObjectNotFoundError();
		}

		$oNewTeam = $this->model('matter\group\team')->byId($team);

		return new \ResponseData($oNewTeam);
	}
	/**
	 *
	 */
	public function list_action() {
		if (!$this->groupApp) {
			return new \ObjectNotFoundError();
		}

		$modelGrpTeam = $this->model('matter\group\team');
		$teams = $modelGrpTeam->byApp($this->groupApp->id, ['teamType' => 'T']);

		return new \ResponseData($teams);
	}
	/**
	 * 创建一个团队
	 * 创建人作为团队的组长
	 */
	public function add_action() {
		if (!$this->groupApp) {
			return new \ObjectNotFoundError();
		}

		$modelGrpTeam = $this->model('matter\group\team');

		$oPosted = $this->getPostJson();
		$current = time();
		$aNewTeam = [
			'aid' => $this->groupApp->id,
			'team_id' => uniqid(),
			'create_at' => $current,
			'creator' => $this->who->uid,
			'creator_name' => $this->who->nickname,
			'title' => empty($oPosted->title) ? $this->who->nickname . '的团队' : $oPosted->title,
			'times' => 1,
			'team_type' => 'T',
			'targets' => '',
		];

		$modelGrpTeam->insert('xxt_group_team', $aNewTeam, false);

		$oNewTeam = $modelGrpTeam->byId($aNewTeam['team_id']);

		$aJoinResult = $this->join($oNewTeam, $this->who, ['isLeader' => 'Y']);
		if (false === $aJoinResult[0]) {
			return new \ParameterError($aJoinResult[1]);
		}

		return new \ResponseData($aNewTeam);
	}
}