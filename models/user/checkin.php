<?php
class checkin_model extends TMS_MODEL {
	/**
	 * 创建一个签到活动
	 * 每个账号只有一个签到活动
	 * 所以若没有则创建一个，若有则直接返回
	 *
	 */
	public function get($mpid) {
		$q = array('*', 'xxt_checkin', "mpid='$mpid'");
		if (!($checkin = parent::query_obj_ss($q))) {
			parent::insert('xxt_checkin', array('mpid' => $mpid), false);
			$checkin = parent::query_obj_ss($q);
		}
		return $checkin;
	}
	/**
	 * 参加活动赚取积分
	 *
	 * $mpid
	 * $mid member's id
	 *
	 * return $gain array(credits,times)
	 * 积分（credits）：增量，总数
	 * 次数（times）：累计参加的次数
	 * 是否继续开放（open）：每个人每天只能参加一次
	 */
	public function participate($mpid, $mid) {
		/**
		 * 获得上一次签到记录
		 */
		$q = array('checkin_at,times_accumulated', 'xxt_checkin_log', "mid='$mid' and last=1");
		$last = parent::query_obj_ss($q);
		/**
		 *
		 */
		if (!$this->isOpen($mpid, $mid, $last)) {
			return false;
		}
		/**
		 * 签到记录表，记录用户的每一次签到行为
		 * 每一次签到行为上记录，已经累计签到的次数和是否为最后一次的标识
		 *
		 * 获得最近一次签到时间
		 * 判断是否超过了1填，如果超过，重新计算有效累计次数
		 */
		if (!$last) {
			$times_accumulated = 0;
		} else {
			/**
			 * 检查是否属于连续签到
			 */
			if ($this->isBreak($last)) {
				$times_accumulated = 0;
			} else {
				$times_accumulated = (int) ($last->times_accumulated);
			}
			if (!parent::update('xxt_checkin_log', array('last' => 0), "mid='$mid' and last=1")) {
				die('unknown exception.');
			}
		}
		$new_credits = $this->earnCredits($times_accumulated + 1);
		/**
		 * insert new one
		 */
		$newone['mid'] = $mid;
		$newone['mpid'] = $mpid;
		$newone['checkin_at'] = time();
		$newone['times_accumulated'] = $times_accumulated + 1;
		$newone['last'] = 1;
		parent::insert('xxt_checkin_log', $newone, false);
		/**
		 * get current credits
		 */
		$q = array('credits', 'xxt_member', "mid='$mid'");
		$existing_credits = (int) parent::query_val_ss($q);
		$all_credits = $existing_credits + $new_credits;
		parent::update('xxt_member', array('credits' => $all_credits), "mid='$mid'");
		/**
		 * return
		 */
		$gain = array();
		$gain['credits_new'] = $new_credits;
		$gain['times_accumulated'] = $times_accumulated + 1;
		$gain['credits_all'] = $all_credits;
		$level = $this->calcLevel($all_credits);
		$gain['level'] = $level[0];
		$gain['level_title'] = $level[1];
		$gain['open'] = 0;

		return $gain;
	}
	/**
	 * 当前用户是否可以参加活动？
	 *
	 * 规则：
	 * 间隔1天以上才可以再次参加活动
	 */
	//todo
	public function isOpen($mpid, $mid, $last = null) {
		if ($last === null) {
			$q = array('checkin_at,times_accumulated', 'xxt_checkin_log', "mid='$mid' and last=1");
			$last = parent::query_obj_ss($q);
		}
		if ($last) {
			$lastdate = getdate($last->checkin_at);
			$nowdate = getdate(time());
			if ($lastdate['year'] === $nowdate['year'] &&
				$nowdate['yday'] === $lastdate['yday']) {
				return false;
			}
		}
		return true;
	}
	/**
	 *
	 */
	//todo
	public function isBreak($last) {
		$lastdate = getdate($last->checkin_at);
		$nowdate = getdate(time());
		if ($lastdate['year'] === $nowdate['year'] &&
			($nowdate['yday'] - $lastdate['yday']) > 1) {
			return true;
		}
		return false;
	}
	/**
	 *
	 */
	//todo 如何把这个规则变为可描述的？
	protected function earnCredits($times) {
		if ($times <= 7) {
			return $times;
		} else {
			return 7;
		}
	}
	/**
	 * 根据积分计算等级
	 *
	 */
	//todo 这应该是一个通用方法，但是放在哪里合适？
	public function &calcLevel($credits = 0) {
		//todo why???
		if ($credits === 0) {
			$level = array(1, '小水草');
			return $level;
		}
		switch ($credits) {
		case $credits <= 20:
			$level = array(1, '小水草');
			break;
		case $credits <= 50:
			$level = array(2, '娇珊瑚');
			break;
		case $credits <= 100:
			$level = array(3, '萌海星');
			break;
		case $credits <= 200:
			$level = array(4, '水母阿呆');
			break;
		case $credits <= 500:
			$level = array(5, '霸道蟹');
			break;
		case $credits <= 1000:
			$level = array(6, '机灵海马');
			break;
		case $credits <= 2000:
			$level = array(7, '蝴蝶鱼');
			break;
		case $credits <= 5000:
			$level = array(8, '海豚精灵');
			break;
		case $credits <= 10000:
			$level = array(9, '小蓝鲸');
			break;
		default:
			$level = array(10, '海洋霸主');
		}
		return $level;
	}
}