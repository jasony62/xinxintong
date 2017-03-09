<?php
namespace matter;

require_once dirname(__FILE__) . '/app_base.php';
/**
 *
 */
class enroll_model extends app_base {
	/**
	 *
	 */
	protected function table() {
		return 'xxt_enroll';
	}
	/**
	 *
	 */
	public function getTypeName() {
		return 'enroll';
	}
	/**
	 * @param string $ver 为了兼容老版本，迁移后应该去掉
	 */
	public function getEntryUrl($siteId, $id, $ver = 'NEW') {
		$url = "http://" . $_SERVER['HTTP_HOST'];

		if ($ver === 'OLD') {
			$url .= "/rest/app/enroll";
			$url .= "?mpid={$siteId}&aid=" . $id;
		} else {
			if ($siteId === 'platform') {
				$app = $this->byId($id, ['cascaded' => 'N']);
				$url .= "/rest/site/fe/matter/enroll";
				$url .= "?site={$app->siteid}&app=" . $id;
			} else {
				$url .= "/rest/site/fe/matter/enroll";
				$url .= "?site={$siteId}&app=" . $id;
			}
		}

		return $url;
	}
	/**
	 *
	 * $aid string
	 * $cascaded array []
	 */
	public function &byId($aid, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';
		$q = [
			$fields,
			'xxt_enroll',
			["id" => $aid],
		];
		if ($app = $this->query_obj_ss($q)) {
			$app->type = 'enroll';
			if (isset($app->siteid) && isset($app->id)) {
				$app->entryUrl = $this->getEntryUrl($app->siteid, $app->id);
			}
			if (isset($app->entry_rule)) {
				$app->entry_rule = json_decode($app->entry_rule);
			}
			if (isset($app->scenario_config)) {
				if (!empty($app->scenario_config)) {
					$app->scenarioConfig = json_decode($app->scenario_config);
				} else {
					$app->scenarioConfig = new \stdClass;
				}
			}
			if ($cascaded === 'Y') {
				$modelPage = \TMS_APP::M('matter\enroll\page');
				$app->pages = $modelPage->byApp($aid);
			}
		}

		return $app;
	}
	/**
	 * 返回登记活动列表
	 */
	public function &bySite($site, $page = 1, $size = 30, $mission = null, $scenario = null) {
		$result = array();

		$q = array(
			'*',
			'xxt_enroll a',
			"siteid='$site' and state<>0",
		);
		if (!empty($scenario)) {
			$q[2] .= " and scenario='$scenario'";
		}
		if (!empty($mission)) {
			$q[2] .= " and exists(select 1 from xxt_mission_matter where mission_id='$mission' and matter_type='enroll' and matter_id=a.id)";
		}
		$q2['o'] = 'a.modify_at desc';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;
		if ($a = $this->query_objs_ss($q, $q2)) {
			$result['apps'] = $a;
			$q[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q);
			$result['total'] = $total;
		}

		return $result;
	}
	/**
	 * 返回登记活动列表
	 */
	public function &byMission($mission, $scenario = null, $page = 1, $size = 30) {
		$result = new \stdClass;

		$q = [
			'*',
			'xxt_enroll',
			"state<>0 and mission_id='$mission'",
		];
		if (!empty($scenario)) {
			$q[2] .= " and scenario='$scenario'";
		}
		$q2['o'] = 'modify_at desc';
		if ($page) {
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
	 * 更新登记活动标签
	 */
	public function updateTags($aid, $tags) {
		if (empty($tags)) {
			return false;
		}
		if (is_array($tags)) {
			$tags = implode(',', $tags);
		}

		$options = array('fields' => 'tags', 'cascaded' => 'N');
		$app = $this->byId($aid, $options);
		if (empty($app->tags)) {
			$this->update('xxt_enroll', ['tags' => $tags], ["id" => $aid]);
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
				$this->update('xxt_enroll', ['tags' => $updated], ["id" => $aid]);
			}
		}

		return true;
	}
	/**
	 * @todo 应该删除
	 * 检查用户是否已经登记
	 *
	 * 如果设置轮次，只坚持当前轮次是否已经登记
	 */
	public function hasEnrolled($mpid, $aid, $user) {
		if (empty($mpid) || empty($aid) || (empty($user->openid) && empty($user->vid))) {
			return false;
		}
		$q = array(
			'count(*)',
			'xxt_enroll_record',
			"state=1 and enroll_at>0 and mpid='$mpid' and aid='$aid'",
		);
		if (!empty($user->openid)) {
			$q[2] .= " and openid='$user->openid'";
		} else if (!empty($user->vid)) {
			$q[2] .= " and vid='$user->vid'";
		} else {
			return false;
		}
		$modelRun = \TMS_APP::M('matter\enroll\round');
		if ($activeRound = $modelRun->getActive($mpid, $aid)) {
			$q[2] .= " and rid='$activeRound->rid'";
		}
		$rst = (int) $this->query_val_ss($q);

		return $rst > 0;
	}
	/**
	 * 检查用户是否已经登记
	 *
	 * 如果设置轮次，只坚持当前轮次是否已经登记
	 */
	public function userEnrolled($siteId, &$app, &$user) {
		if (empty($siteId) || empty($app) || empty($user->uid)) {
			return false;
		}
		$q = array(
			'count(*)',
			'xxt_enroll_record',
			"state=1 and enroll_at>0 and aid='{$app->id}' and userid='{$user->uid}'",
		);
		/* 当前轮次 */
		if ($app->multi_rounds === 'Y') {
			$modelRun = \TMS_APP::M('matter\enroll\round');
			if ($activeRound = $modelRun->getActive($siteId, $app->id)) {
				$q[2] .= " and rid='$activeRound->rid'";
			}
		}

		$rst = (int) $this->query_val_ss($q);

		return $rst > 0;
	}
	/**
	 * 根据邀请到的用户数量进行的排名
	 */
	public function rankByFollower($mpid, $aid, $openid) {
		$modelRec = \TMS_APP::M('matter\enroll\record');
		$user = new \stdClass;
		$user->openid = $openid;
		$last = $modelRec->getLast($aid, $user);

		$q = array(
			'count(*)',
			'xxt_enroll_record',
			"state=1 and aid='$aid' and follower_num>$last->follower_num",
		);

		$rank = (int) $this->query_val_ss($q);

		return $rank + 1;
	}
	/**
	 * 登记活动运行情况摘要
	 *
	 * @param string $siteId
	 * @param string $appId
	 *
	 * @return
	 */
	public function &opData(&$app) {
		$modelRnd = \TMS_APP::M('matter\enroll\round');
		$page = (object) ['num' => 1, 'size' => 5];
		$rounds = $modelRnd->byApp($app->siteid, $app->id, ['fields' => 'rid,title', 'page' => $page]);

		if (empty($rounds)) {
			$summary = new \stdClass;
			/* total */
			$q = [
				'count(*)',
				'xxt_enroll_record',
				['aid' => $app->id, 'state' => 1],
			];
			$summary->total = $this->query_val_ss($q);
		} else {
			$summary = [];
			$activeRound = $modelRnd->getActive($app->siteid, $app->id);
			foreach ($rounds as $round) {
				/* total */
				$q = [
					'count(*)',
					'xxt_enroll_record',
					['aid' => $app->id, 'state' => 1, 'rid' => $round->rid],
				];
				$round->total = $this->query_val_ss($q);
				if ($activeRound && $round->rid === $activeRound->rid) {
					$round->active = 'Y';
				}

				$summary[] = $round;
			}
		}

		return $summary;
	}
}