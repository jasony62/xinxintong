<?php
namespace pl\fe\matter\group;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 分组活动控制器
 */
class round extends \pl\fe\matter\base {
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
	public function list_action($app, $cascade = '', $roundType = 'T') {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$aOptions = [
			'cascade' => $cascade,
			'round_type' => $roundType,
		];
		$rounds = $this->model('matter\group\round')->byApp($app, $aOptions);

		return new \ResponseData($rounds);
	}
	/**
	 *
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
		$aNewRound = [
			'aid' => $app,
			'round_id' => uniqid(),
			'create_at' => time(),
			'title' => empty($oPosted->title) ? '新分组' : $oPosted->title,
			'times' => 1,
			'round_type' => empty($oPosted->round_type) ? 'T' : (in_array($oPosted->round_type, ['T', 'R']) ? $oPosted->round_type : 'T'),
			'targets' => '',
		];

		$modelApp->insert('xxt_group_round', $aNewRound, false);

		return new \ResponseData($aNewRound);
	}
	/**
	 *
	 */
	public function update_action($rid) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}
		$modelRnd = $this->model('matter\group\round');

		$oRound = $modelRnd->byId($rid);
		if (false === $oRound) {
			return new \ObjectNotFoundError();
		}
		$oPosted = $this->getPostJson();
		if (isset($oPosted->round_type) && $oPosted->round_type !== $oRound->round_type) {
			/**
			 * 已过已经有分组用户不允许删除
			 */
			$roundUserCnt = 0;
			$modelPly = $this->model('matter\group\player');
			switch ($oRound->round_type) {
			case 'T':
				$roundUserCnt = $modelPly->countByRound($oRound->aid, $oRound->round_id);
				break;
			case 'R':
				$roundUserCnt = $modelPly->countByRoleRound($oRound->aid, $oRound->round_id);
				break;
			}
			if ($roundUserCnt > 0) {
				return new \ResponseError('已经有分组数据，不允许删除轮次！');
			}
		}

		if (isset($oPosted->targets)) {
			$oPosted->targets = $model->toJson($oPosted->targets);
		}
		if (isset($oPosted->extattrs)) {
			$oPosted->extattrs = $model->toJson($oPosted->extattrs);
		}
		$rst = $modelRnd->update(
			'xxt_group_round',
			$oPosted,
			['aid' => $oRound->aid, 'round_id' => $oRound->round_id]
		);
		$oRound = $modelRnd->byId($rid);

		/* 更新级联信息 */
		if ($rst && isset($oPosted->title) && $oRound->round_type === 'T') {
			$modelRnd->update(
				'xxt_group_player',
				['round_title' => $oPosted->title],
				['aid' => $oRound->aid, 'round_id' => $oRound->round_id]
			);
		}

		return new \ResponseData($oRound);
	}
	/**
	 *
	 */
	public function remove_action($app, $rid) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$model = $this->model();
		/**
		 * 已过已经有分组用户不允许删除
		 */
		$q = [
			'count(*)',
			'xxt_group_player',
			['aid' => $app, 'round_id' => $rid, 'state' => 1],
		];
		if (0 < (int) $model->query_val_ss($q)) {
			return new \ResponseError('已经有分组数据，不允许删除轮次！');
		}

		$rst = $model->delete(
			'xxt_group_round',
			['aid' => $app, 'round_id' => $rid]
		);

		return new \ResponseData($rst);
	}
	/**
	 * 属于指定分组的人
	 * $roundType 分组类型 “T” 团队分组，"R" 角色分组
	 */
	public function winnersGet_action($app, $rid = null, $roundType = 'T') {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}
		if ($roundType === 'R') {
			$oResult = $this->model('matter\group\player')->byRoleRound($app, $rid);
		} else {
			$oResult = $this->model('matter\group\player')->byRound($app, $rid);
		}

		return new \ResponseData($oResult);
	}
}