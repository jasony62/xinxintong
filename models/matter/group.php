<?php
namespace matter;

require_once dirname(__FILE__) . '/app_base.php';
/**
 *
 */
class group_model extends app_base {
	/**
	 *
	 */
	protected function table() {
		return 'xxt_group';
	}
	/**
	 *
	 */
	public function getTypeName() {
		return 'group';
	}
	/**
	 *
	 * $aid string
	 * $options array
	 */
	public function &byId($aid, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';
		$q = [
			$fields,
			'xxt_group',
			['id' => $aid],
		];
		if (isset($options['where'])) {
			foreach ($options['where'] as $key => $value) {
				$q[2][$key] = $value;
			}
		}

		if ($app = $this->query_obj_ss($q)) {
			$app->type = 'group';
			if ($cascaded === 'Y') {
				$rounds = $this->model('matter\group\round')->byApp($aid);
				$app->rounds = $rounds;
			}
			if ($fields === '*' || false !== strpos($fields, 'data_schemas')) {
				if (!empty($app->data_schemas)) {
					$app->dataSchemas = json_decode($app->data_schemas);
				} else {
					$app->dataSchemas = [];
				}
			}
			if ($fields === '*' || false !== strpos($fields, 'group_rule')) {
				if (!empty($app->group_rule)) {
					$app->groupRule = json_decode($app->group_rule);
				} else {
					$app->groupRule = new \stdClass;
				}
			}
			if(!empty($app->matter_mg_tag)){
				$app->matter_mg_tag = json_decode($app->matter_mg_tag);
			}
		}

		return $app;
	}
	/**
	 * 返回项目下的分组活动
	 */
	public function &byMission($mission, $scenario = null, $page = null, $size = null) {
		$result = new \stdClass;

		$q = [
			'*',
			'xxt_group',
			"state=1 and mission_id='$mission'",
		];
		if (!empty($scenario)) {
			$q[2] .= " and scenario='$scenario'";
		}
		$q2['o'] = 'modify_at desc';
		if ($page && $size) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}

		$result->apps = $this->query_objs_ss($q, $q2);
		if ($page && $size) {
			$q[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q);
			$result->total = $total;
		} else {
			$result->total = count($result->apps);
		}

		return $result;
	}
	/**
	 * 和登记活动关联的分组活动
	 */
	public function byEnrollApp($enrollAppId, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = [
			$fields,
			'xxt_group',
			['source_app' => '{"id":"' . $enrollAppId . '","type":"enroll"}'],
		];
		$apps = $this->query_objs_ss($q);

		return $apps;
	}
	/**
	 * 和登记活动关联的分组活动
	 */
	public function bySigninApp($signinAppId, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = [
			$fields,
			'xxt_group',
			['source_app' => '{"id":"' . $signinAppId . '","type":"signin"}'],
		];
		$apps = $this->query_objs_ss($q);

		return $apps;
	}
	/**
	 * 更新登记活动标签
	 */
	public function updateTags($aid, $tags) {
		if (empty($tags)) {
			return false;
		}

		$options = array('fields' => 'tags', 'cascaded' => 'N');
		$app = $this->byId($aid, $options);
		if (empty($app->tags)) {
			$this->update('xxt_group', array('tags' => $tags), "id='$aid'");
		} else {
			$existent = explode(',', $app->tags);
			$checked = explode(',', $tags);
			$updated = array();
			foreach ($checked as $c) {
				if (!in_array($c, $existent)) {
					$updated[] = $c;
				}
			}
			if (count($updated)) {
				$updated = array_merge($existent, $updated);
				$updated = implode(',', $updated);
				$this->update('xxt_group', array('tags' => $updated), "id='$aid'");
			}
		}
		return true;
	}
	/**
	 *
	 */
	public function execute($appId) {
		$app = \TMS_APP::M('matter\group')->byId($appId);

		$modelRnd = \TMS_APP::M('matter\group\round');
		$rst = $modelRnd->clean($appId);
		$rounds = $modelRnd->byApp($appId);
		if (empty($rounds)) {
			return [false, '没有指定分组'];
		}

		$modelPly = \TMS_APP::M('matter\group\player');
		$players = $modelPly->pendings($appId);

		$lenOfRounds = count($rounds);
		$lenOfPlayers = count($players);
		$spaceOfRound = ceil($lenOfPlayers / $lenOfRounds);
		$hasSpace = true;
		$current = time();
		$submittedWinners = [];

		while (count($players) && $hasSpace) {
			$hasSpace = false;
			foreach ($rounds as &$round) {
				!isset($round->winners) && $round->winners = [];
				is_string($round->targets) && $round->targets = json_decode($round->targets);
				$round->times == 0 && ($round->times = $spaceOfRound);
				if ($round->times > count($round->winners)) {
					$winner4Round = $this->_getWinner4Round($round, $players);
					$winner4Round->round_id = $round->round_id;
					$submittedWinners[] = $winner4Round;
					/*保存结果*/
					$winner = array(
						'round_id' => $round->round_id,
						'round_title' => $round->title,
						'draw_at' => $current,
					);
					$modelPly->update('xxt_group_player', $winner, "aid='$appId' and enroll_key='{$winner4Round->enroll_key}'");
					/*轮次是否还可以继续放用户*/
					if ($round->times > count($round->winners)) {
						$hasSpace = true;
					}
				}
				if (count($players) === 0) {
					break;
				}
			}
		}

		return [true, $submittedWinners];
	}
	/**
	 *
	 */
	private function _getWinner4Round(&$round, &$players) {
		$steps = rand(0, 10);
		$matchedPos = $startPos = $steps % count($players);
		$winner = $players[$startPos];

		$target = $round->targets ? $round->targets[count($round->winners) % count($round->targets)] : false;
		if ($target) {
			/* 设置了用户抽取规则 */
			if (count(get_object_vars($target)) > 0) {
				/* 检查是否匹配规则 */
				$matched = $this->_matched($winner, $target);
				while (!$matched) {
					$matchedPos++;
					if ($matchedPos === count($players)) {
						$matchedPos = 0;
					}
					$winner = $players[$matchedPos];
					if ($matchedPos === $startPos) {
						/*比较了所有的候选者，没有匹配的*/
						break;
					} else {
						/*下一个候选者*/
						$matched = $this->_matched($winner, $target);
					}
				}
			}
		}
		$round->winners[] = $winner;

		/* 从候选者中去掉 */
		array_splice($players, $matchedPos, 1);

		return $winner;
	}
	/**
	 *
	 */
	private function _matched($candidate, $target) {
		if (!$candidate) {
			return false;
		}

		if (count(get_object_vars($target)) === 0) {
			return true;
		}

		foreach ($target as $k => $v) {
			if (isset($candidate->data->{$k}) && $candidate->data->{$k} === $v) {
				return true;
			}
		}

		return false;
	}
	/**
	 *
	 */
	public function &opData($app) {
		$options = ['cascade' => 'playerCount'];
		$rounds = $this->model('matter\group\round')->byApp($app->id, $options);

		return $rounds;
	}
	/**
	 * 指定用户的行为报告
	 */
	public function reportByUser($oApp, $oUser) {
		$modelPly = $this->model('matter\group\player');

		$result = $modelPly->byUser($oApp, $oUser->userid, ['fields' => 'id,round_id,round_title']);

		return $result;
	}
}