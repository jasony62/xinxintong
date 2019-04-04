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
		\TPL::output('/pl/fe/site/frame');
		exit;
	}
	/**
	 * 素材行为统计数据
	 */
	public function matterActions_action($site, $type, $orderby, $startAt, $endAt, $page = 1, $size = 30) {
		$s = 'l.matter_title,l.matter_type,l.matter_id';
		$s .= ',sum(l.act_read) read_num';
		$s .= ',sum(l.act_share_friend) share_friend_num';
		$s .= ',sum(l.act_share_timeline) share_timeline_num';
		$q[] = $s;
		$q[] = 'xxt_log_matter_action l';
		$w = "l.siteid='$site' and l.matter_type='$type' ";
		if (!empty($startAt)) {
			$w .= " and l.action_at>=$startAt";
		}
		if (!empty($endAt)) {
			$w .= " and l.action_at<=$endAt";
		}
		$q[] = $w;
		$q2 = array(
			'g' => 'matter_type,matter_id',
			'r' => array('o' => ($page - 1) * $size, 'l' => $size),
		);
		//按照阅读数、分享数逆序排列
		if (in_array($orderby, array('read', 'share_friend', 'share_timeline'))) {
			$q2['o'] = $orderby . '_num desc';
		}

		$model = $this->model();
		if ($stat = $model->query_objs_ss($q, $q2)) {
			$b = new \stdClass;
			foreach ($stat as $k => $v) {
				if ($v->matter_type === 'article') {
					$v->matter_title = $model->query_val_ss([
						'title',
						'xxt_article',
						["id" => $v->matter_id],
					]);
				}
				$v->fav_num = $model->query_val_ss([
					'count(*)',
					'xxt_site_favor',
					"siteid='$site' and matter_type='$v->matter_type' and matter_id='$v->matter_id'",
				]);
				$c[$k] = $v->fav_num;
				$b->$k = $v;
			}
			//按照收藏数量逆序排列
			if ($orderby == 'fav') {
				arsort($c);
				foreach ($c as $k2 => $v2) {
					foreach ($b as $k3 => $v3) {
						if ($k2 == $k3 && $v2 == $v3->fav_num) {
							$e[] = $v3;
						}
					}
				}
				$b = (object) $e;
			}

			$stat = $b;
			$q = array(
				'count(distinct matter_type,matter_id)',
				'xxt_log_matter_action',
				"siteid='$site' and matter_type='$type'",
			);
			if (!empty($startAt)) {
				$q[2] .= " and action_at>=$startAt";
			}
			if (!empty($endAt)) {
				$q[2] .= " and action_at<=$endAt";
			}
			$cnt = $model->query_val_ss($q);
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

		$s = 'l.nickname,l.userid';
		$s .= ',sum(l.act_read) read_num';
		$s .= ',sum(l.act_share_friend) share_friend_num';
		$s .= ',sum(l.act_share_timeline) share_timeline_num';
		$q[] = $s;
		$q[] = 'xxt_log_user_action l';
		$w = "l.siteid='$site'";
		if (!empty($startAt)) {
			$w .= " and l.action_at>=$startAt";
		}
		if (!empty($endAt)) {
			$w .= "  and l.action_at<=$endAt";
		}
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
				"siteid='$site'",
			);
			if (!empty($startAt)) {
				$q[2] .= " and action_at>=$startAt";
			}
			if (!empty($endAt)) {
				$q[2] .= "  and action_at<=$endAt";
			}
			$cnt = $this->model()->query_val_ss($q);
		} else {
			$cnt = 0;
		}

		return new \ResponseData(array('users' => $stat, 'total' => $cnt));
	}
}