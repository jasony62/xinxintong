<?php
namespace matter\lottery;

class result_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function &byId($id, $cascaded = 'N') {
		$q = array(
			'*',
			'xxt_lottery_log',
			"id='$id'",
		);
		if ($log = $this->query_obj_ss($q)) {
			$log->award = \TMS_APP::M('app\lottery\award')->byId($log->aid);
		}

		return $log;
	}
	/**
	 * 记录抽奖结果
	 * 在已有记录上进行累加和更新，不进行逻辑判断
	 * 逻辑判断可单独进行，如果需要可事先更新数据状态
	 */
	public function add($site, $lid, &$user, $award, $enrollKey = null) {
		/**
		 * 获得之前的签到情况
		 */
		$q = array(
			'times_accumulated,draw_at',
			'xxt_lottery_log',
			"lid='$lid' and userid='{$user->uid}' and last='Y'",
		);

		if ($last = $this->query_obj_ss($q)) {
			$times = (int) $last->times_accumulated + 1;
			/**
			 * 更新之前的数据状态
			 */
			$w = "lid='$lid' and userid='{$user->uid}' and last='Y'";
			$this->update('xxt_lottery_log', array('last' => 'N'), $w);
		} else {
			$times = 1;
		}
		/**
		 * 新抽奖记录
		 */
		$current = time();
		$i['siteid'] = $site;
		$i['lid'] = $lid;
		$i['userid'] = $user->uid;
		$i['nickname'] = $user->nickname;
		$i['draw_at'] = $current;
		$i['aid'] = $award->aid;
		$i['award_title'] = $award->title;
		$i['times_accumulated'] = $times;
		$i['enroll_key'] = $enrollKey;

		$id = $this->insert('xxt_lottery_log', $i, true);

		$log = new \stdClass;
		$log->id = $id;
		$log->aid = $award->aid;
		$log->draw_at = $current;
		$log->award_title = $award->title;
		$log->award_greeting = $award->greeting;
		$log->award_pic = $award->pic;
		$log->type = $award->type;

		return $log;
	}
	/**
	 * 当前用户是否还有继续玩的机会
	 */
	public function canPlay(&$lot, &$user, $autoUpdateState = false) {
		/**
		 * 最近一次抽奖情况
		 */
		$q = array(
			'times_accumulated,draw_at',
			'xxt_lottery_log',
			"lid='{$lot->id}' and userid='{$user->uid}' and last='Y'",
		);
		if (!($last = $this->query_obj_ss($q))) {
			/**
			 * 没有进行过抽奖，可以进行抽奖
			 */
			return true;
		}
		/**
		 * 检查规则
		 */
		switch ($lot->period) {
		case 'A': // 总计
			return (int) $last->times_accumulated < (int) $lot->chance;
		case 'D': // 天
			$lastdate = getdate($last->draw_at);
			$nowdate = getdate(time());
			if ($lastdate['year'] === $nowdate['year'] &&
				($nowdate['yday'] - $lastdate['yday']) > 0) {
				/**
				 * 和最近一次抽奖不是在同一天，允许抽奖
				 */
				if ($autoUpdateState) {
					$w = "lid='$lot->id' and userid='{$user->uid}' and last='Y'";
					$this->update('xxt_lottery_log', array('last' => 'N'), $w);
				}
				return true;
			} else {
				return (int) $last->times_accumulated < (int) $lot->chance;
			}
		}
	}
	/**
	 * 领取奖品
	 * 奖品分为:
	 * 应用内奖品，例如：积分，再玩一次的机会。这类奖品抽完奖后即可领取。
	 * 实物奖品：需要在线下进行兑奖
	 */
	public function acceptAward($lid, &$user, $award) {
		$takeaway = false;
		switch ($award['type']) {
		case 1: // 积分
			$takeaway = true;
			$this->earnCredits($lid, $user, $award);
			break;
		case 2: // 再来一次
			$takeaway = true;
			$this->earnPlayAgain($lid, $user, $award);
			break;
		case 3: // 完成任务
			$takeaway = true;
			$this->earnTask($lid, $user, $award);
			break;
		}
		/**
		 * 更新抽奖状态
		 */
		if ($takeaway) {
			$w = "lid='$lid' and userid='$userid' and last='Y'";
			$this->update('xxt_lottery_log', array('takeaway' => 'Y'), $w);
		}

		return $takeaway;
	}
	/**
	 * 获得积分奖励
	 */
	public function earnCredits($lid, &$user, &$award) {
		$credits = $award['quantity'];
		//\TMS_APP::model('user/member')->addCredits($mid, $credits);
		return true;
	}
	/**
	 * 将累计的抽奖次数减1
	 */
	public function earnPlayAgain($lid, &$user, &$award) {
		$w = "lid='$lid' and userid='{$user->uid}' and last='Y'";

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
	public function earnTask($lid, &$user, &$award) {
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
		$t['userid'] = $user->uid;
		$t['nickname'] = $user->nickname;
		$t['create_at'] = time();
		$t['tid'] = $award['taskid'];

		$this->insert('xxt_lottery_task_log', $t, false);

		return true;
		//} else
		//    return false;
	}
}