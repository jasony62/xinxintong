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
			'l.group_id,l.userid,u.nickname,l.event_name operation,l.event_op,l.event_at operate_at,l.target_type,l.owner_userid,l.owner_nickname',
			'xxt_enroll_log l,xxt_enroll_user u',
			"l.aid='{$app}' and u.userid = l.userid and u.aid = l.aid",
		];

		if (!empty($options['byUser'])) {
			$q[2] .= " and u.nickname like '%" . $this->escape($options['byUser']) . "%'";
		}
		if (!empty($options['byOp'])) {
			if ($options['byOp'] === 'read') {
				$q[0] = 'l.userid,u.nickname,l.operation,l.operate_at,l.user_op_num,l.matter_op_num';
				$q[1] = 'xxt_log_user_matter l,xxt_enroll_user u';
				$q[2] = "l.matter_type='enroll' and l.matter_id='{$app}' and u.userid = l.userid and u.aid = l.matter_id and l.operation = 'read'";
			} else {
				$q[2] .= " and l.event_name = '" . $this->escape($options['byOp']) . "'";
			}
		}
		if (!empty($options['byRid'])) {
			if (!empty($options['byOp']) && $options['byOp'] === 'read') {
				$q[2] .= " and l.operate_data like '%" . '"rid":"' . $this->escape($options['byRid']) . '"' . "%' and u.rid = '" . $this->escape($options['byRid']) . "'";
			} else {
				$q[2] .= " and l.rid = '" . $this->escape($options['byRid']) . "' and u.rid = l.rid";
			}
		} else {
				$q[2] .= " and u.rid = 'ALL'";
		}

		/**
		 * 分页数据
		 */
		$q2 = [
			'o' => 'operate_at desc',
		];
		if (!empty($page) && !empty($size)) {
			$q2['r'] = [
				'o' => (($page - 1) * $size),
				'l' => $size,
			];
		}

		$result->logs = $this->query_objs_ss($q, $q2);

		$q[0] = 'count(l.id)';
		$result->total = $this->query_val_ss($q);

		return $result;
	}
}