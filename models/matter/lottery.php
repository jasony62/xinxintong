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
	public function getEntryUrl($runningMpid, $id) {
		$url = "http://" . $_SERVER['HTTP_HOST'];
		$url .= "/rest/site/fe/matter/lottery";
		$url .= "?mpid=$runningMpid&lottery=" . $id;

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
			if (in_array('task', $cascaded)) {
				$lot->tasks = $this->getTasks($lid);
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
	 * 获得抽奖活动的任务
	 */
	public function &getTasks($lid) {
		$q = array(
			'*',
			'xxt_lottery_task',
			"lid='$lid'",
		);

		$tasks = $this->query_objs_ss($q);
		foreach ($tasks as &$task) {
			$task->task_params = json_decode($task->task_params);
		}

		return $tasks;
	}
	/**
	 * 还有多少次参与机会
	 */
	public function getChance($lid, &$user) {
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
			if ($lastdate['year'] === $nowdate['year'] && ($nowdate['yday'] - $lastdate['yday']) > 0)
			/**
			 * 和最近一次抽奖不是在同一天，允许抽奖
			 */
			{
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
	 * 当前用户是否有为完成的任务
	 *
	 * 如果有返回任务的定义
	 */
	public function hasTask($lid, &$user) {
		$whichuser = "lid='$lid' and userid='{$user->uid}'";
		/**
		 * 一个活动可能产生多个任务，只有最新创建的任务是有效任务
		 */
		$q = array(
			'id,tid,finished,create_at',
			'xxt_lottery_task_log',
			$whichuser,
		);
		$q2 = array(
			'o' => 'create_at desc',
			'r' => array('o' => 0, 'l' => 1),
		);
		if ($tasklog = $this->query_objs_ss($q, $q2)) {
			$tasklog = $tasklog[0];
			if ($tasklog->finished === 'Y') {
				return false;
			}
			/**
			 * 有任务，没有完成
			 */
			$q = array(
				'*',
				'xxt_lottery_task',
				"tid='$tasklog->tid'",
			);
			$task = $this->query_obj_ss($q);
			if ($task->task_type === 'sns_share') {
				$task->task_params = json_decode($task->params);
				/**
				 * 检查是否分享了好友
				 * 没有对分享的时间点进行检查
				 */
				$q = array(
					'count(*)',
					'xxt_log_matter_share',
					"userid='{$user->uid}' and (share_to='F' or share_to='T') and matter_type='lottery' and matter_id='$lid' and share_at>$tasklog->create_at",
				);
				if ($task->task_params->shareCount <= (int) $this->query_val_ss($q)) {
					/**
					 * 任务完成，奖励一次抽奖机会
					 */
					$award = array('quantity' => 1);
					\TMS_APP::M('matter\lottery\record')->earnPlayAgain($lid, $user, $award);
					/**
					 * 修改任务状态
					 */
					$this->update(
						'xxt_lottery_task_log',
						array('finished' => 'Y'),
						"id=$tasklog->id"
					);
					return false;
				}
			}
			/**
			 * 检查任务是否已经完成了
			 */
			return $task;
		}

		return false;
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