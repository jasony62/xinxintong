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
		$oTeam = $oPosted->team;
		$oMember = $oPosted->member;
		$current = time();
		$aNewTeam = [
			'aid' => $this->groupApp->id,
			'team_id' => uniqid(),
			'create_at' => $current,
			'creator' => $this->who->uid,
			'creator_name' => $this->who->nickname,
			'title' => empty($oTeam->title) ? $this->who->nickname . '的团队' : $oTeam->title,
			'times' => 1,
			'team_type' => 'T',
			'targets' => '',
		];

		$modelGrpTeam->insert('xxt_group_team', $aNewTeam, false);

		$oNewTeam = $modelGrpTeam->byId($aNewTeam['team_id']);

		$aJoinResult = $this->join($oNewTeam, $this->who, $oMember, ['isLeader' => 'Y']);
		if (false === $aJoinResult[0]) {
			return new \ParameterError($aJoinResult[1]);
		}

		return new \ResponseData($aNewTeam);
	}
	/**
	 * 更新
	 */
	public function update_action($team) {
		if (!$this->groupApp) {
			return new \ObjectNotFoundError();
		}
		if (!($oTeam = $this->team)) {
			return new \ObjectNotFoundError();
		}
		if ($oTeam->creator !== $this->who->uid) {
			return new \ResponseError('只允许团队的创建人设置团队');
		}

		$oPosted = $this->getPostJson();

		$aUpdated = [];
		foreach ($oPosted as $prop => $val) {
			switch ($prop) {
			case 'title':
			case 'summary':
				$aUpdated[$prop] = $this->escape($val);
				break;
			}
		}
		if (empty($aUpdated)) {
			return new \ResponseError('没有指定有效的更新数据');
		}

		$modelGrpTeam = $this->model('matter\group\team');
		$modelGrpTeam->update('xxt_group_team', $aUpdated, ['team_id' => $oTeam->team_id]);

		$oTeam = $modelGrpTeam->byId($oTeam->team_id);

		return new \ResponseData($oTeam);
	}
	/**
	 *
	 */
	public function quit_action($team, $ek) {
		if (!$this->groupApp) {
			return new \ObjectNotFoundError();
		}
		if (!$this->team) {
			return new \ObjectNotFoundError();
		}
		if ($this->team->creator !== $this->who->uid) {
			return new \ResponseError('只允许团队的创建人移出成员');
		}

		$modelGrpMem = $this->model('matter\group\record');
		$rst = $modelGrpMem->remove($this->groupApp->id, $ek);

		return new \ResponseData($rst);
	}
}