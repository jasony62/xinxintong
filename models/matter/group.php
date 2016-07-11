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
	 * $cascaded array []
	 */
	public function &byId($aid, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';
		$q = array(
			$fields,
			'xxt_group',
			"id='$aid'",
		);
		if ($app = $this->query_obj_ss($q)) {
			if ($cascaded === 'Y') {
			}
		}

		return $app;
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

		$players = \TMS_APP::M('matter\group\player')->pendings($appId);

		$lenOfRounds = count($rounds);
		$lenOfPlayers = count($players);
		$spaceOfRound = ceil($lenOfPlayers / $lenOfRounds);
		$hasSpace = true;
		$submittedWinners = [];

		while (count($players) && $hasSpace) {
			$hasSpace = false;
			foreach ($rounds as &$round) {
				!isset($round->winners) && $round->winners = [];
				$round->times == 0 && ($round->times = $spaceOfRound);
				if ($round->times > count($round->winners)) {
					$winner4Round = $this->getWinner4Round($round);
					$winner4Round->round_id = $round->round_id;
					$submittedWinners[] = $winner4Round;
					if ($round->times > count($round->winners)) {
						$hasSpace = true;
					}
				}
			}
		}
	}
	/**
	 *
	 */
	private function getWinner4Round(&$round, &$players) {
		$target = $round->targets ? $round->targets[count($round->winners) % count($round->targets)] : false;
		$steps = 10;
		$matchedPos = $startPos = $steps % count($players);
		die('ppp:' . $matchedPos);
		$winner = $players[$startPos];
		if ($target) {
			/* 设置了用户抽取规则 */
		}
		$round->winners[] = $winner;
		$players->splice($matchedPos, 1);

		return $winner;
	}
}