<?php
namespace sns\yx;
/**
 * 易信公众号关注用户
 */
class fan_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function &byOpenid($siteid, $openid, $fields = '*', $followed = null) {
		$q = array(
			$fields,
			'xxt_site_yxfan',
			"siteid='$siteid' and openid='$openid'",
		);
		if ($followed === 'Y') {
			$q[2] .= " and unsubscribe_at=0";
		}
		$fan = $this->query_obj_ss($q);

		return $fan;
	}
	/**
	 *
	 */
	public function &byUser($siteid, $userid, $fields = '*', $followed = null) {
		$q = array(
			$fields,
			'xxt_site_yxfan',
			"siteid='$siteid' and userid='$userid'",
		);
		if ($followed === 'Y') {
			$q[2] .= " and unsubscribe_at=0";
		}
		$fan = $this->query_obj_ss($q);

		return $fan;
	}
	/**
	 * 是否关注了公众号
	 *
	 * todo 企业号的用户如何判断？
	 */
	public function isFollow($siteid, $openid) {
		if (empty($openid)) {
			return false;
		}

		$q = array(
			'count(*)',
			'xxt_site_yxfan',
			"siteid='$siteid' and openid='$openid' and unsubscribe_at=0",
		);

		$isFollow = (1 === (int) $this->query_val_ss($q));

		return $isFollow;
	}
	/**
	 * 创建空的关注用户
	 */
	public function &blank($siteid, $openid, $persisted = true, $options = array()) {
		$fan = new \stdClass;
		$fan->siteid = $siteid;
		$fan->openid = $openid;
		$fan->nickname = '';
		!empty($options['userid']) && $fan->userid = $options['userid'];
		!empty($options['subscribe_at']) && $fan->subscribe_at = $options['subscribe_at'];
		!empty($options['sync_at']) && $fan->sync_at = $options['sync_at'];

		$fan->id = $this->insert('xxt_site_yxfan', $fan, true);

		return $fan;
	}
	/**
	 *
	 */
	public function &getGroups($siteid) {
		$q = array(
			'id,name',
			'xxt_site_yxfangroup',
			"siteid='$siteid'",
		);
		$q2 = array('o' => 'id');

		$groups = $this->query_objs_ss($q, $q2);

		return $groups;
	}
	/**
	 *
	 */
	public function modifyByOpenid($siteid, $openid, $updated) {
		return $this->update('xxt_site_yxfan', $updated, "siteid='$siteid' and openid='$openid'");
	}
}