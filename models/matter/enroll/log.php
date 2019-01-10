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
	public function list($app, $aOptions, $page = '', $size = '') {
		$result = new \stdClass;
		$q = [
			'l.group_id,l.userid,u.nickname,l.event_name operation,l.event_op,l.event_at operate_at,l.target_type,l.owner_userid,l.owner_nickname',
			'xxt_enroll_log l,xxt_enroll_user u',
			"l.aid='{$app}' and u.userid = l.userid and u.aid = l.aid",
		];

		if (!empty($aOptions['byUser'])) {
			$q[2] .= " and u.nickname like '%" . $aOptions['byUser'] . "%'";
		}
		if (!empty($aOptions['byOp'])) {
			if ($aOptions['byOp'] === 'read') {
				$q[0] = 'l.userid,u.nickname,l.operation,l.operate_at,l.user_op_num,l.matter_op_num';
				$q[1] = 'xxt_log_user_matter l,xxt_enroll_user u';
				$q[2] = "l.matter_type='enroll' and l.matter_id='{$app}' and u.userid = l.userid and u.aid = l.matter_id and l.operation = 'read'";
			} else {
				$q[2] .= " and l.event_name = '" . $aOptions['byOp'] . "'";
			}
		}
		if (!empty($aOptions['byRid'])) {
			if (!empty($aOptions['byOp']) && $aOptions['byOp'] === 'read') {
				$q[2] .= " and l.operate_data like '%" . '"rid":"' . $aOptions['byRid'] . '"' . "%' and u.rid = '" . $aOptions['byRid'] . "'";
			} else {
				$q[2] .= " and l.rid = '" . $aOptions['byRid'] . "' and u.rid = l.rid";
			}
		} else {
			$q[2] .= " and u.rid = 'ALL'";
		}

		if (!empty($aOptions['startAt'])) {
			if (!empty($aOptions['byOp']) && $aOptions['byOp'] === 'read') {
				$q[2] .= " and l.operate_at > {$aOptions['startAt']}";
			} else {
				$q[2] .= " and l.event_at > {$aOptions['startAt']}";
			}
		}
		if (!empty($aOptions['endAt'])) {
			if (!empty($aOptions['byOp']) && $aOptions['byOp'] === 'read') {
				$q[2] .= " and l.operate_at < {$aOptions['endAt']}";
			} else {
				$q[2] .= " and l.event_at < {$aOptions['endAt']}";
			}
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