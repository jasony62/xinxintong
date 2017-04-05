<?php
namespace matter\signin;

class receiver_model extends \TMS_MODEL {
	/**
	 *
	 * @param string $siteId
	 * @param string $aid
	 */
	public function &byUser($siteId, $userid, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = array(
			$fields,
			'xxt_signin_receiver',
			"siteid='$siteId' and userid='$userid'",
		);

		$receiver = $this->query_obj_ss($q);

		return $receiver;
	}
	/**
	 *
	 * @param string $siteId
	 * @param string $aid
	 */
	public function &byApp($siteId, $aid, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_signin_receiver',
			['aid' => $aid],
		];

		$receivers = $this->query_objs_ss($q);

		return $receivers;
	}
	/**
	 *
	 */
	public function isUserJoined($appId, $userId) {
		$q = [
			'*',
			'xxt_signin_receiver',
			['aid' => $appId, 'userid' => $userId],
		];
		$receiver = $this->query_obj_ss($q);

		return $receiver;
	}
	/**
	 * 获得指定时间戳后加入的登记活动通知接收人
	 *
	 * @param string $siteId
	 * @param string $aid
	 */
	public function &afterJoin($siteId, $aid, $timestamp, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_signin_receiver',
			"aid='$aid' and join_at>=$timestamp",
		];

		$receivers = $this->query_objs_ss($q);

		return $receivers;
	}
}