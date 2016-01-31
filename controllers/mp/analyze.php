<?php
namespace mp;

require_once dirname(__FILE__) . '/mp_controller.php';

class analyze extends mp_controller {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';
		return $rule_action;
	}
	/**
	 * 素材行为统计数据
	 */
	public function mpActions_action($startAt, $endAt, $page = 1, $size = 30) {
		$q = array(
			'*',
			"xxt_log_mpa",
			"mpid='$this->mpid'",
		);
		$q2 = array(
			'o' => 'year desc,month desc,day desc',
			'r' => array('o' => ($page - 1) * $size, 'l' => $size),
		);
		if ($logs = $this->model()->query_objs_ss($q, $q2)) {
			/**
			 * 总数
			 */
			$q[0] = 'count(*)';
			$cnt = $this->model()->query_val_ss($q);
		} else {
			$cnt = 0;
		}

		return new \ResponseData(array('logs' => $logs, 'total' => $cnt));
	}
	/**
	 * 用户行为统计数据
	 */
	public function userActions_action($orderby, $startAt, $endAt, $page = 1, $size = 30) {
		$q = array();
		$s = 'l.openid,l.nickname';
		$s .= ',sum(l.act_read) read_num';
		$s .= ',sum(l.act_share_friend) share_friend_num';
		$s .= ',sum(l.act_share_timeline) share_timeline_num';
		$q[] = $s;
		$q[] = 'xxt_log_user_action l';
		$w = "l.mpid='$this->mpid'";
		$w .= " and l.action_at>=$startAt and l.action_at<=$endAt";
		$q[] = $w;
		$q2 = array(
			'g' => 'openid',
			'o' => $orderby . '_num desc',
			'r' => array('o' => ($page - 1) * $size, 'l' => $size),
		);
		if ($stat = $this->model()->query_objs_ss($q, $q2)) {
			$q = array(
				'count(distinct openid)',
				'xxt_log_user_action',
				"mpid='$this->mpid' and action_at>=$startAt and action_at<=$endAt",
			);
			$cnt = $this->model()->query_val_ss($q);
		} else {
			$cnt = 0;
		}

		return new \ResponseData(array('users' => $stat, 'total' => $cnt));
	}
	/**
	 * 素材行为统计数据
	 */
	public function matterActions_action($orderby, $startAt, $endAt, $page = 1, $size = 30) {
		$s = 'l.matter_title,l.matter_type,l.matter_id';
		$s .= ',sum(l.act_read) read_num';
		$s .= ',sum(l.act_share_friend) share_friend_num';
		$s .= ',sum(l.act_share_timeline) share_timeline_num';
		$q[] = $s;
		$q[] = 'xxt_log_matter_action l';
		$w = "l.mpid='$this->mpid'";
		$w .= " and l.action_at>=$startAt and l.action_at<=$endAt";
		$q[] = $w;
		$q2 = array(
			'g' => 'matter_type,matter_id',
			'o' => $orderby . '_num desc',
			'r' => array('o' => ($page - 1) * $size, 'l' => $size),
		);
		if ($stat = $this->model()->query_objs_ss($q, $q2)) {
			$q = array(
				'count(distinct matter_type,matter_id)',
				'xxt_log_matter_action',
				"mpid='$this->mpid' and action_at>=$startAt and action_at<=$endAt",
			);
			$cnt = $this->model()->query_val_ss($q);
		} else {
			$cnt = 0;
		}

		return new \ResponseData(array('matters' => $stat, 'total' => $cnt));
	}
	/**
	 * 群发消息事件统计
	 */
	public function massmsg_action() {
		$logs = $this->model('log')->massByMpid($this->mpid);

		return new \ResponseData($logs);
	}
	/**
	 * 积分排行榜
	 * 积分的增量并不是按日进行更新的，是最后一次产生积分时各个周期的增量，所以要检查最后一次获得积分时间是否在现有的统计周期内
	 */
	public function coin_action($period, $page = 1, $size = 30) {
		switch ($period) {
		case 'A':
			$period = 'coin';
			$begin = 0;
			break;
		case 'Y':
			$period = 'coin_year';
			$begin = mktime(0, 0, 0, 1, 1, date('Y'));
			break;
		case 'M':
			$period = 'coin_month';
			$begin = mktime(0, 0, 0, date('n'), 1, date('Y'));
			break;
		case 'W': // 周一是第一天
			$period = 'coin_week';
			$firstDay = time();
			$w = (int) date('N');
			$firstDay -= $w * 86400;
			$begin = mktime(0, 0, 0, date('n'), date('j', $firstDay), date('Y'));
			break;
		case 'D':
			$period = 'coin_day';
			$begin = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
			break;
		}
		$q = array(
			'openid,nickname,' . $period . ' coin',
			'xxt_fans',
			"mpid='$this->mpid' and coin_last_at>=$begin",
		);
		$q2 = array(
			'o' => $period . ' desc',
			'r' => array('o' => ($page - 1) * $size, 'l' => $size),
		);

		$fans = $this->model()->query_objs_ss($q, $q2);
		if (!empty($fans)) {
			$q[0] = 'count(*)';
			$total = $this->model()->query_val_ss($q);
			$result = array('fans' => $fans, 'total' => $total);
		} else {
			$result = array('fans' => array(), 'total' => 0);
		}

		return new \ResponseData($result);
	}
}
