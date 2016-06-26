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
	 *
	 */
	public function getEntryUrl($siteId, $id) {
		$url = "http://" . $_SERVER['HTTP_HOST'];
		$url .= "/rest/site/fe/matter/enroll";
		$url .= "?site={$siteId}&app=" . $id;

		return $url;
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
			'xxt_enroll',
			"id='$aid'",
		);
		if ($app = $this->query_obj_ss($q)) {
			if (isset($app->entry_rule)) {
				$app->entry_rule = json_decode($app->entry_rule);
			}
			if (!empty($app->scenario_config)) {
				$app->scenarioConfig = json_decode($app->scenario_config);
			} else {
				$app->scenarioConfig = new \stdClass;
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
	 * 更新登记活动标签
	 */
	public function updateTags($aid, $tags) {
		if (empty($tags)) {
			return false;
		}

		$options = array('fields' => 'tags', 'cascaded' => 'N');
		$app = $this->byId($aid, $options);
		if (empty($app->tags)) {
			$this->update('xxt_enroll', array('tags' => $tags), "id='$aid'");
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
				$this->update('xxt_enroll', array('tags' => $updated), "id='$aid'");
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
	 * 活动报名名单
	 *
	 * 1、如果活动仅限会员报名，那么要叠加会员信息
	 * 2、如果报名的表单中有扩展信息，那么要提取扩展信息
	 *
	 * $mpid
	 * $aid
	 * $options
	 * --creater openid
	 * --visitor openid
	 * --page
	 * --size
	 * --rid 轮次id
	 * --kw 检索关键词
	 * --by 检索字段
	 *
	 *
	 * return
	 * [0] 数据列表
	 * [1] 数据总条数
	 * [2] 数据项的定义
	 */
	public function participants($siteId, $appId, $options = null) {
		if ($options) {
			is_array($options) && $options = (object) $options;
			$rid = null;
			if (!empty($options->rid)) {
				if ($options->rid === 'ALL') {
					$rid = null;
				} else if (!empty($options->rid)) {
					$rid = $options->rid;
				}
			} else if ($activeRound = \TMS_APP::M('matter\enroll\round')->getActive($siteId, $appId)) {
				$rid = $activeRound->rid;
			}

			$kw = isset($options->kw) ? $options->kw : null;
			$by = isset($options->by) ? $options->by : null;
		}

		$w = "e.siteid='$siteId' and aid='$appId' and userid<>''";
		/**
		 * 按轮次过滤
		 */
		!empty($rid) && $w .= " and e.rid='$rid'";
		/**
		 * 按标签过滤
		 */
		if (!empty($options->tags)) {
			$aTags = explode(',', $options->tags);
			foreach ($aTags as $tag) {
				$w .= "and concat(',',e.tags,',') like '%,$tag,%'";
			}
		}
		/**
		 * 进行过登记的人
		 */
		$q = array(
			'userid',
			"xxt_enroll_record e",
			$w,
		);
		/**
		 * 获得填写的登记数据
		 */
		$participants = $this->query_vals_ss($q);

		return $participants;
	}

	/**
	 * 根据邀请到的用户数量进行的排名
	 */
	public function rankByFollower($mpid, $aid, $openid) {
		$modelRec = \TMS_APP::M('matter\enroll\record');
		$user = new \stdClass;
		$user->openid = $openid;
		$last = $modelRec->getLast($mpid, $aid, $user);

		$q = array(
			'count(*)',
			'xxt_enroll_record',
			"state=1 and aid='$aid' and follower_num>$last->follower_num",
		);

		$rank = (int) $this->query_val_ss($q);

		return $rank + 1;
	}
}