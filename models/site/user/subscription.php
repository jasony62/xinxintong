<?php
namespace site\user;
/**
 * 站点用户关注的团队信息
 */
class subscription_model extends \TMS_MODEL {
	/**
	 * 用户可以看到的关注团队信息
	 *
	 * @param string $userId
	 * @param string $siteId
	 */
	public function byUser($unionid, $siteId = '', $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$page = isset($options['page']) ? $options['page'] : ['at' => 1, 'size' => 30];
		$result = new \stdClass;

		$q = [
			$fields,
			'xxt_site_subscription',
			['unionid' => $unionid],
		];
		if (!empty($siteId)) {
			$q[2]['siteid'] = $siteId;
		}
		$q2 = ['o' => 'put_at desc', 'r' => ['o' => ($page['at'] - 1) * $page['size'], 'l' => $page['size']]];

		$result->matters = $this->query_objs_ss($q, $q2);

		$q[0] = 'count(*)';
		$result->total = $this->query_val_ss($q);

		return $result;
	}
	/**
	 *
	 *
	 * @param string $userId
	 */
	public function countByUser($unionid, $options = []) {
		$q = [
			'count(*)',
			'xxt_site_subscription',
			"unionid='{$unionid}'",
		];
		if (!empty($options['afterAt'])) {
			$q[2] .= " and put_at>='{$options['afterAt']}'";
		}

		$count = $this->query_val_ss($q);

		return $count;
	}
}