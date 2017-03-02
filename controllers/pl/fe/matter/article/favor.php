<?php
namespace pl\fe\matter\article;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 收藏
 */
class favor extends \pl\fe\matter\base {
	/**
	 * 单图文的收藏列表
	 *
	 */
	public function list_action($site, $id, $page=1, $size=30) {
		$p=['*','xxt_site_favor',"siteid='$site' and matter_id='$id'"];
		$p2['r']['o'] = ($page - 1) * $size;
		$p2['r']['l'] = $size;
		$p2['o'] = 'id desc';
		$result = array();
		if ($sync = $this->model()->query_objs_ss($p, $p2)) {
			$result['data'] = $sync;
			$p[0] = 'count(*)';
			$total = (int) $this->model()->query_val_ss($p);
			$result['total'] = $total;
		} else {
			$result['data'] = array();
			$result['total'] = 0;
		}

		return new \ResponseData($result);
	}
}