<?php
namespace matter\signin;
/**
 *
 */
class log_model extends \TMS_MODEL {
	/**
	 *
	 * 获取登记记录对应的签到日志
	 *
	 * @param string $ek 签到记录的ID
	 * @param string $roundId 签到轮次的ID
	 *
	 */
	public function &byRecord($ek, $roundId = null, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_signin_log',
			['enroll_key' => $ek],
		];
		if (empty($roundId)) {
			$q2 = ['o' => 'signin_at'];
			$logs = $this->query_objs_ss($q, $q2);

			return $logs;
		} else {
			$q[2]['rid'] = $roundId;
			$log = $this->query_obj_ss($q);

			return $log;
		}
	}
}