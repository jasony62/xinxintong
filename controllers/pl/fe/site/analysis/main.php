<?php
namespace pl\fe\site\analysis;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 团队运行统计管理控制器
 */
class main extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/analysis');
		exit;
	}
	/**
	 * 素材行为统计数据
	 */
	public function matterActions_action($site,$type,$orderby, $startAt, $endAt, $page = 1, $size = 30) {
		$s = 'l.matter_title,l.matter_type,l.matter_id';
		$s .= ',sum(l.act_read) read_num';
		$s .= ',sum(l.act_share_friend) share_friend_num';
		$s .= ',sum(l.act_share_timeline) share_timeline_num';
		$q[] = $s;
		$q[] = 'xxt_log_matter_action l';
		$w = "l.siteid='$site' and l.matter_type='$type' ";
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
				"siteid='$site' and matter_type='$type' and action_at>=$startAt and action_at<=$endAt",
			);
			$cnt = $this->model()->query_val_ss($q);
		} else {
			$cnt = 0;
		}

		return new \ResponseData(array('matters' => $stat, 'total' => $cnt));
	}
	/**
	 * 用户行为统计数据
	 */
	public function userActions_action($site, $orderby, $startAt, $endAt, $page = 1, $size = 30) {
		$q = array();

		$s = 'l.openid,l.nickname,l.userid';
		$s .= ',sum(l.act_read) read_num';
		$s .= ',sum(l.act_share_friend) share_friend_num';
		$s .= ',sum(l.act_share_timeline) share_timeline_num';
		$q[] = $s;
		$q[] = 'xxt_log_user_action l';
		$w = "l.siteid='$site'";
		$w .= " and l.action_at>=$startAt and l.action_at<=$endAt";
		$q[] = $w;
		$q2 = array(
			'g' => 'userid',
			'o' => $orderby . '_num desc',
			'r' => array('o' => ($page - 1) * $size, 'l' => $size),
		);
		if ($stat = $this->model()->query_objs_ss($q, $q2)) {
			$q = array(
				'count(distinct userid)',
				'xxt_log_user_action',
				"siteid='$site' and action_at>=$startAt and action_at<=$endAt",
			);
			$cnt = $this->model()->query_val_ss($q);
		} else {
			$cnt = 0;
		}

		return new \ResponseData(array('users' => $stat, 'total' => $cnt));
	}
}