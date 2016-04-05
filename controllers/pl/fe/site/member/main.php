<?php
namespace pl\fe\site\member;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 自定义用户控制器
 */
class main extends \pl\fe\base {
	/**
	 *
	 */
	public function list_action($schema, $page = 1, $size = 30) {
		$model = $this->model();

		$w = "m.schema_id=$schema and m.forbidden='N'";
		if (!empty($kw) && !empty($by)) {
			$w .= " and m.$by like '%$kw%'";
		}
		if (!empty($dept)) {
			$w .= " and m.depts like '%\"$dept\"%'";
		}
		if (!empty($tag)) {
			$w .= " and concat(',',m.tags,',') like '%,$tag,%'";
		}
		$result = array();
		$q = array(
			'm.*',
			'xxt_site_member m',
			$w,
		);
		$q2['o'] = 'm.create_at desc';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;
		if ($members = $model->query_objs_ss($q, $q2)) {
			$result['members'] = $members;
			$q[0] = 'count(*)';
			$total = (int) $model->query_val_ss($q);
			$result['total'] = $total;
		} else {
			$result['members'] = array();
			$result['total'] = 0;
		}

		return new \ResponseData($result);
	}
}