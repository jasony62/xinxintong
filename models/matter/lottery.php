<?php
namespace matter;

require_once dirname(__FILE__) . '/app_base.php';
/**
 * 抽奖活动
 */
class lottery_model extends app_base {
	/**
	 *
	 */
	protected function table() {
		return 'xxt_lottery';
	}
	/**
	 *
	 */
	public function getTypeName() {
		return 'lottery';
	}
	/**
	 *
	 */
	public function getEntryUrl($siteId, $id) {
		$url = "http://" . $_SERVER['HTTP_HOST'];
		$url .= "/rest/site/fe/matter/lottery";
		$url .= "?site=$siteId&app=" . $id;

		return $url;
	}
	/**
	 * 获得抽奖活动的定义
	 *
	 * $lid string
	 * $cascaded array [award|plate]
	 */
	public function &byId($lid, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : array();

		$q = array($fields, 'xxt_lottery', "id='$lid'");
		if ($lot = $this->query_obj_ss($q)) {
			if (in_array('award', $cascaded)) {
				$lot->awards = $this->getAwards($lid);
			}
			if (in_array('plate', $cascaded)) {
				$q = array(
					'size,a0,a1,a2,a3,a4,a5,a6,a7,a8,a9,a10,a11',
					'xxt_lottery_plate',
					"lid='$lid'",
				);
				if ($plate = parent::query_obj_ss($q)) {
					$lot->plate = $plate;
				}
			}
			if (in_array('task', $cascaded)) {
				$lot->tasks = \TMS_APP::M('matter\lottery\task')->byApp($lid);
			}
		}
		return $lot;
	}
	/**
	 * 获得奖品的定义
	 */
	public function &getAwards($lid) {
		$q = array(
			'*',
			'xxt_lottery_award',
			"lid='$lid'",
		);
		$q2['o'] = 'prob';

		$awards = $this->query_objs_ss($q, $q2);

		return $awards;
	}
	/**
	 * 计算奖励的抽奖机会
	 */
	public function freshEarnChance(&$lot, &$user) {
		$modelTsk = \TMS_APP::M('matter\lottery\task');

		$tasks = $modelTsk->byApp($lot->id, array('task_type' => 'add_chance'));
		if (count($tasks)) {
			foreach ($tasks as $lotTask) {
				$userTask = $modelTsk->getTaskByUser($user, $lot->id, $lotTask->tid);
				if ($userTask === false || $userTask->finished === 'Y') {
					/*没有创建过任务或者任务已经完成，创建新任务*/
					$userTask = $modelTsk->addTask4User($user, $lot->id, $lotTask->tid);
				}
				$modelTsk->checkUserTask($user, $lot->id, $lotTask, $userTask);
			}
		}
		return true;
	}
	/**
	 * 还有多少次参与机会
	 */
	public function getChance(&$lot, &$user) {
		$lid = $lot->id;
		/**
		 * 计算奖励的抽奖机会
		 */
		$this->freshEarnChance($lot, $user);
		/**
		 * 获得抽奖设置
		 */
		$q = array(
			'chance,period',
			'xxt_lottery',
			"id='$lid'",
		);
		$setting = $this->query_obj_ss($q);

		$times_threshold = $setting->chance; //每天允许参与的次数
		/**
		 * 计算当前用户的抽奖机会
		 */
		$q = array(
			'times_accumulated,draw_at',
			'xxt_lottery_log',
		);
		$q[2] = "lid='$lid' and userid='{$user->uid}' and last='Y'";

		if (!($last = $this->query_obj_ss($q))) {
			/**
			 * 没有进行过抽奖，可以进行抽奖
			 */
			return $times_threshold;
		}
		/**
		 * 计算剩余的次数
		 */
		switch ($setting->period) {
		case 'A':
			/**
			 * 累计
			 */
			$chance = $times_threshold - (int) $last->times_accumulated;
			break;
		case 'D':
			/**
			 * 以天为周期限制抽奖次数
			 */
			$lastdate = getdate($last->draw_at);
			$nowdate = getdate(time());
			if ($lastdate['year'] === $nowdate['year'] && ($nowdate['yday'] - $lastdate['yday']) > 0) {
				/**
				 * 和最近一次抽奖不是在同一天，允许抽奖
				 */
				$chance = $times_threshold;
			} else {
				$chance = $times_threshold - (int) $last->times_accumulated;
			}

			break;
		default:
			$chance = 0;
		}

		return $chance;
	}
	/**
	 *
	 */
	public function &getWinners($lid) {
		$q = array(
			'l.nickname,a.title award_title',
			'xxt_lottery_log l,xxt_lottery_award a',
			"l.lid='$lid' and l.aid=a.aid and a.type !=0",
		);
		$q2['o'] = 'l.draw_at desc';
		$q2['r']['o'] = 0;
		$q2['r']['l'] = 10;

		$winners = $this->query_objs_ss($q, $q2);

		return $winners;
	}
	/**
	 * 获得抽奖记录
	 */
	public function &getLog($lid, &$user, $includeAll = false, $page = 1, $size = 20) {
		$q = array(
			'l.id,l.aid,l.draw_at,l.prize_url,a.title award_title,a.pic award_pic,a.greeting award_greeting,a.type',
			'xxt_lottery_log l,xxt_lottery_award a',
			"l.lid='$lid' and l.userid='{$user->uid}' and l.aid=a.aid",
		);
		if (!$includeAll) {
			$q[2] .= " and a.type!=0";
		}
		$q2['o'] = 'l.draw_at desc';
		$q2['r']['o'] = $page - 1;
		$q2['r']['l'] = $size;

		$log = $this->query_objs_ss($q, $q2);

		return $log;
	}
	/**
	 * 奖品获奖的数量
	 */
	public function winNumber($aid) {
		$q = array(
			'count(*)',
			'xxt_lottery_log',
			"aid='$aid'",
		);
		return (int) $this->query_val_ss($q);
	}
	/**
	 * 重置抽奖活动数据
	 * 1、抽奖日志数据
	 * 2、奖品抽取情况记录
	 */
	public function clean($lid) {
		/* task log*/
		$this->delete('xxt_lottery_task_log', "lid='$lid'");
		/**
		 * log
		 */
		$this->delete('xxt_lottery_log', "lid='$lid'");
		/**
		 * award
		 */
		$rst = $this->update(
			'xxt_lottery_award',
			array(
				'takeaway' => 0,
				'takeaway_at' => 0,
			),
			"lid='$lid'"
		);
		return $rst;
	}
}