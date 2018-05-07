<?php
namespace matter\enroll;
/**
 *
 */
class log_model extends \TMS_MODEL {
	/**
	 * 返回登记活动用户互动日志
	 *
	 * @param $act
	 * @param $oApp
	 * @param $aOptions
	 *
	 */
	public function list($app, $options, $page = '', $size = '') {
		$result = new \stdClass;
		$q = [
			'group_id,userid,nickname,event_name,event_op,event_at,target_type,owner_userid,owner_nickname',
			'xxt_enroll_log',
			"aid='{$app}'",
		];

		if (!empty($options['byUser'])) {
			$q[2] .= " and nickname like '%" . $this->escape($options['byUser']) . "%'";
		}
		if (!empty($options['byOp'])) {
			$q[2] .= " and event_name = '" . $this->escape($options['byOp']) . "'";
		}
		if (!empty($options['byRid'])) {
			$q[2] .= " and rid = '" . $this->escape($options['byRid']) . "'";
		}

		/**
		 * 分页数据
		 */
		$q2 = [
			'o' => 'event_at desc',
		];
		if (!empty($page) && !empty($size)) {
			$q2['r'] = [
				'o' => (($page - 1) * $size),
				'l' => $size,
			];
		}

		$result->logs = $this->query_objs_ss($q, $q2);

		$q[0] = 'count(*)';
		$result->total = $this->query_val_ss($q);

		return $result;
	}
}