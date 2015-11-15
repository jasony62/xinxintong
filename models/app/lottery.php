<?php
namespace app;

require_once dirname(dirname(__FILE__)) . '/matter/lottery.php';
/**
 * 抽奖活动
 */
class lottery_model extends \matter\lottery_model {
	/**
	 * 获得抽奖的定义
	 *
	 * $lid string
	 * $cascaded array [award|plate]
	 */
	public function &byId($lid, $fields = '*', $cascaded = array()) {
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

		return $tasks;
	}
	/**
	 * 还有多少次参与机会
	 */
	public function getChance($lid, $mid = null, $openid = null) {
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

		if (empty($mid) && empty($openid)) {
			return $times_threshold;
		}

		/**
		 * 计算当前用户的抽奖机会
		 */
		$q = array(
			'times_accumulated,draw_at',
			'xxt_lottery_log',
		);
		if (!empty($mid)) {
			$q[2] = "lid='$lid' and mid='$mid' and last='Y'";
		} else {
			$q[2] = "lid='$lid' and openid='$openid' and last='Y'";
		}

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
	public function hasTask($lid, $mid, $openid) {
		if (!empty($openid)) {
			$whichuser = "lid='$lid' and openid='$openid'";
		} else if (!empty($mid)) {
			$whichuser = "lid='$lid' and mid='$mid'";
		} else {
			return false;
		}

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
			//if ($taskid === 'share2friend001' || $taskid === 'share2friend002') {
			/**
			 * todo 具体的规则应该在任务中进行定义，而不应该写死
			 * 检查是否分享了好友
			 * 没有对分享的时间点进行检查
			 */
			$q = array(
				'count(*)',
				'xxt_log_matter_share',
				"openid='$openid' and (share_to='F' or share_to='T') and matter_type='lottery' and matter_id='$lid' and share_at>$tasklog->create_at",
			);
			if (3 <= (int) $this->query_val_ss($q)) {
				/**
				 * 任务完成，奖励一次抽奖机会
				 */
				$award = array('quantity' => 1);
				$this->earnPlayAgain($lid, $mid, $openid, $award);
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
			//}
			/**
			 * 检查任务是否已经完成了
			 */
			return $task;
		}
		return false;
	}
	/**
	 * 当前用户是否还有继续玩的机会
	 *
	 * 当前用户既可以是注册用户（mid），也可以是粉丝用户（openid和src）
	 */
	public function canPlay($lid, $mid, $openid, $autoUpdateState = false) {
		/**
		 * 最近一次抽奖情况
		 */
		$q = array(
			'times_accumulated,draw_at',
			'xxt_lottery_log',
		);
		if (!empty($mid)) {
			$q[2] = "lid='$lid' and mid='$mid' and last='Y'";
		} else {
			$q[2] = "lid='$lid' and openid='$openid' and last='Y'";
		}

		if (!($last = $this->query_obj_ss($q))) {
			/**
			 * 没有进行过抽奖，可以进行抽奖
			 */
			return true;
		}
		/**
		 * 获得抽奖设置
		 */
		$q = array(
			'chance,period',
			'xxt_lottery',
			"id='$lid'",
		);
		$setting = $this->query_obj_ss($q);

		switch ($setting->period) {
		case 'A': // 总计
			return (int) $last->times_accumulated < (int) $setting->chance;
		case 'D': // 天
			$lastdate = getdate($last->draw_at);
			$nowdate = getdate(time());
			if ($lastdate['year'] === $nowdate['year'] &&
				($nowdate['yday'] - $lastdate['yday']) > 0) {
				/**
				 * 和最近一次抽奖不是在同一天，允许抽奖
				 */
				if ($autoUpdateState) {
					if (!empty($mid)) {
						$w = "lid='$lid' and mid='$mid' and last='Y'";
					} else {
						$w = "lid='$lid' and openid='$openid' and last='Y'";
					}

					$this->update('xxt_lottery_log', array('last' => 'N'), $w);
				}
				return true;
			} else {
				return (int) $last->times_accumulated < (int) $setting->chance;
			}
		}
	}
	/**
	 *
	 */
	public function &getWinners($lid) {
		$q = array(
			'm.nickname,a.title award_title',
			'xxt_lottery_log l,xxt_lottery_award a,xxt_member m',
			"l.lid='$lid' and l.mid=m.mid and m.forbidden='N' and l.aid=a.aid and a.type !=0",
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
	public function &getLog($lid, $mid = null, $openid = null, $includeAll = false, $page = 1, $size = 20) {
		if (empty($mid) && empty($openid)) {
			$log = array();
		} else {
			$q = array(
				'l.id,l.aid,l.draw_at,l.prize_url,a.title award_title,a.pic award_pic,a.greeting award_greeting,a.type',
				'xxt_lottery_log l,xxt_lottery_award a',
			);
			if (!empty($mid)) {
				$q[2] = "l.lid='$lid' and l.mid='$mid' and l.aid=a.aid";
			} else {
				$q[2] = "l.lid='$lid' and l.openid='$openid' and l.aid=a.aid";
			}

			if (!$includeAll) {
				$q[2] .= " and a.type!=0";
			}

			$q2['o'] = 'l.draw_at desc';
			$q2['r']['o'] = $page - 1;
			$q2['r']['l'] = $size;

			$log = $this->query_objs_ss($q, $q2);
		}
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
	 * 领取奖品
	 * 奖品分为:
	 * 应用内奖品，例如：积分，再玩一次的机会。这类奖品抽完奖后即可领取。
	 * 实物奖品：需要在线下进行兑奖
	 */
	public function acceptAward($lid, $mid, $openid, $award) {
		$takeaway = false;
		switch ($award['type']) {
		case 1: // 积分
			$takeaway = true;
			$this->earnCredits($lid, $mid, $openid, $award);
			break;
		case 2: // 再来一次
			$takeaway = true;
			$this->earnPlayAgain($lid, $mid, $openid, $award);
			break;
		case 3: // 完成任务
			$takeaway = true;
			$this->earnTask($lid, $mid, $openid, $award);
			break;
		}
		/**
		 * 更新抽奖状态
		 */
		if ($takeaway) {
			if (!empty($mid)) {
				$w = "lid='$lid' and mid='$mid' and last='Y'";
			} else {
				$w = "lid='$lid' and openid='$openid' and last='Y'";
			}

			$this->update('xxt_lottery_log', array('takeaway' => 'Y'), $w);
		}

		return $takeaway;
	}
	/**
	 * 获得积分奖励
	 */
	public function earnCredits($lid, $mid, $openid, &$award) {
		$credits = $award['quantity'];
		\TMS_APP::model('user/member')->addCredits($mid, $credits);
		return true;
	}
	/**
	 * 将累计的抽奖次数减1
	 */
	public function earnPlayAgain($lid, $mid, $openid, &$award) {
		if (!empty($mid)) {
			$w = "lid='$lid' and mid='$mid' and last='Y'";
		} else {
			$w = "lid='$lid' and openid='$openid' and last='Y'";
		}

		$times = $award['quantity'];
		$sql = 'update xxt_lottery_log';
		$sql .= " set times_accumulated=times_accumulated-$times";
		$sql .= " where $w";
		$this->update($sql);

		return true;
	}
	/**
	 * 获得执行任务
	 * todo 要变成可配置的
	 *
	 * 一个抽奖，一个用户，只允许生成一次任务
	 */
	public function earnTask($lid, $mid, $openid, &$award) {
		/**
		 * 检查是否已经生成过任务
		 */
		//$q = array(
		//    'count(*)',
		//    'xxt_lottery_task_log'
		//);
		//if (!empty($openid))
		//    $q[2] = "lid='$lid' and openid='$openid'";
		//else if (!empty($mid))
		//    $q[2] = "lid='$lid' and mid='$mid'";
		//else
		//    return false;

		//if (0 === (int)$this->query_val_ss($q)) {
		$t = array();
		$t['lid'] = $lid;
		$t['mid'] = $mid;
		$t['openid'] = $openid;
		$t['create_at'] = time();
		$t['tid'] = $award['taskid'];

		$this->insert('xxt_lottery_task_log', $t, false);

		return true;
		//} else
		//    return false;
	}
	/**
	 * 重置抽奖活动数据
	 * 1、抽奖日志数据
	 * 2、奖品抽取情况记录
	 */
	public function clean($lid) {
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