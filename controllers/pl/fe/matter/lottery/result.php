<?php
namespace pl\fe\matter\lottery;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 抽奖活动主控制器
 */
class result extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/lottery/frame');
		exit;
	}
	/**
	 *
	 */
	public function get_action($id) {
		$log = $this->model('matter\lottery\log')->byId($id);

		return new \ResponseData($log);
	}
	/**
	 *
	 */
	public function list_action($lid, $startAt = null, $endAt = null, $page = 1, $size = 30, $award = null) {
		$r = $this->model('matter\lottery')->byId($lid, 'access_control');
		/**
		 * 参与抽奖的用户不一定是关注用户，所以粉丝表里不一定有对应的记录
		 */
		$q = array(
			"l.nickname,l.userid,l.draw_at,a.title award_title,l.takeaway",
			"xxt_lottery_log l left join xxt_lottery_award a on l.aid=a.aid",
			"l.lid='$lid'",
		);
		/**
		 * 指定时间范围
		 */
		if ($startAt !== null && $endAt !== null) {
			$q[2] .= " and l.draw_at>=$startAt and l.draw_at<=$endAt";
		}
		/**
		 * 指定奖项
		 */
		if (!empty($award)) {
			$q[2] .= " and l.aid='$award'";
		}
		/**
		 * 排序和分页
		 */
		$q2['o'] = 'draw_at desc';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;

		$result = $this->model()->query_objs_ss($q, $q2);
		/**
		 * 总数
		 */
		$q[0] = 'count(*)';
		$amount = $this->model()->query_val_ss($q);

		return new \ResponseData(array('result' => $result, 'total' => $amount));
	}
}