<?php
namespace pl\fe\matter\group;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 分组活动控制器
 */
class team extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action($id) {
		\TPL::output('/pl/fe/matter/group/frame');
		exit;
	}
	/**
	 * 获得分组活动下的轮次（分组）
	 *
	 * @param string $app
	 * @param string $cascade 返回的结果中包含哪些级联。逗号分隔的字符串。支持：playerCount
	 *
	 */
	public function list_action($app, $cascade = '', $teamType = 'T') {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$aOptions = [
			'cascade' => $cascade,
			'team_type' => $teamType,
		];
		$teams = $this->model('matter\group\team')->byApp($app, $aOptions);

		return new \ResponseData($teams);
	}
	/**
	 * 添加分组
	 */
	public function add_action($app) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\group');
		$oApp = $modelApp->byId($app);
		if (false === $oApp && $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oPosted = $this->getPostJson();
		$aNewTeam = [
			'aid' => $app,
			'team_id' => uniqid(),
			'create_at' => time(),
			'title' => empty($oPosted->title) ? '新分组' : $oPosted->title,
			'times' => 1,
			'team_type' => empty($oPosted->team_type) ? 'T' : (in_array($oPosted->team_type, ['T', 'R']) ? $oPosted->team_type : 'T'),
			'targets' => '',
		];

		$modelApp->insert('xxt_group_team', $aNewTeam, false);

		return new \ResponseData($aNewTeam);
	}
	/**
	 * 更新分组
	 */
	public function update_action($tid) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}
		$modelTeam = $this->model('matter\group\team');

		$oTeam = $modelTeam->byId($tid);
		if (false === $oTeam) {
			return new \ObjectNotFoundError();
		}
		$oPosted = $this->getPostJson();
		if (isset($oPosted->team_type) && $oPosted->team_type !== $oTeam->team_type) {
			/* 已过已经有分组记录不允许修改类型 */
			$teamRecCnt = 0;
			$modelGrpRec = $this->model('matter\group\record');
			$teamRecCnt = $modelGrpRec->countByTeam($oTeam->team_id);
			if (false !== $teamRecCnt && $teamRecCnt > 0) {
				return new \ResponseError('已经有分组数据，不允许修改分组类型！');
			}
		}

		if (isset($oPosted->targets)) {
			$oPosted->targets = $model->toJson($oPosted->targets);
		}
		if (isset($oPosted->extattrs)) {
			$oPosted->extattrs = $model->toJson($oPosted->extattrs);
		}
		$rst = $modelTeam->update(
			'xxt_group_team',
			$oPosted,
			['aid' => $oTeam->aid, 'team_id' => $oTeam->team_id]
		);
		$oTeam = $modelTeam->byId($tid);

		/* 更新级联信息 */
		if ($rst && isset($oPosted->title) && $oTeam->team_type === 'T') {
			$modelTeam->update(
				'xxt_group_record',
				['team_title' => $oPosted->title],
				['aid' => $oTeam->aid, 'team_id' => $oTeam->team_id]
			);
		}

		return new \ResponseData($oTeam);
	}
	/**
	 * 删除一个分组
	 */
	public function remove_action($tid) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$modelTeam = $this->model('matter\group\team');

		$oTeam = $modelTeam->byId($tid);
		if (false === $oTeam) {
			return new \ObjectNotFoundError();
		}
		/**
		 * 已过已经有分组用户不允许删除
		 */
		$q = [
			'count(*)',
			'xxt_group_record',
			['team_id' => $tid, 'state' => 1],
		];
		if (0 < (int) $modelTeam->query_val_ss($q)) {
			return new \ResponseError('已经有分组数据，不允许删除分组！');
		}

		$rst = $modelTeam->delete(
			'xxt_group_team',
			['team_id' => $tid]
		);

		return new \ResponseData($rst);
	}
}