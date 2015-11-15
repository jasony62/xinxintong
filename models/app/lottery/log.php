<?php
namespace app\lottery;

require_once dirname(dirname(dirname(__FILE__))) . '/matter/lottery.php';

class log_model extends \matter\lottery_model {
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
	public function add($mpid, $lid, $openid, $award, $enrollKey = null) {
		if (empty($mpid)) {
			$mpid = $this->query_value('mpid', 'xxt_lottery', "id='$lid'");
		}
		/**
		 * 获得之前的签到情况
		 */
		$q = array(
			'times_accumulated,draw_at',
			'xxt_lottery_log',
			"lid='$lid' and openid='$openid' and last='Y'",
		);

		if ($last = $this->query_obj_ss($q)) {
			$times = (int) $last->times_accumulated + 1;
			/**
			 * 更新之前的数据状态
			 */
			$w = "lid='$lid' and openid='$openid' and last='Y'";
			$this->update('xxt_lottery_log', array('last' => 'N'), $w);
		} else {
			$times = 1;
		}
		/**
		 * 新抽奖记录
		 */
		$current = time();
		$i['mpid'] = $mpid;
		$i['lid'] = $lid;
		$i['openid'] = $openid;
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
}