<?php
namespace pl\sns\wx;
/**
 * 微信公众号关注用户
 */
class fan_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function &byOpenid($openid, $fields = '*', $followed = null) {
		$q = array(
			$fields,
			'xxt_pl_wxfan',
			"openid='$openid'",
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
	public function &byUser($userid, $fields = '*', $followed = null) {
		$q = array(
			$fields,
			'xxt_pl_wxfan',
			"userid='$userid'",
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
	public function isFollow($openid) {
		if (empty($openid)) {
			return false;
		}

		$q = array(
			'count(*)',
			'xxt_pl_wxfan',
			"openid='$openid' and unsubscribe_at=0",
		);

		$isFollow = (1 === (int) $this->query_val_ss($q));

		return $isFollow;
	}
	/**
	 * 创建空的关注用户
	 */
	public function &blank($openid, $persisted = true, $options = array()) {
		$fan = new \stdClass;
		$fan->openid = $openid;
		!empty($options['userid']) && $fan->userid = $options['userid'];
		!empty($options['subscribe_at']) && $fan->subscribe_at = $options['subscribe_at'];
		!empty($options['sync_at']) && $fan->sync_at = $options['sync_at'];

		$fan->nickname = isset($options['nickname']) ? $options['nickname'] : '';
		isset($options['sex']) && $fan->sex = $options['sex'];
		isset($options['headimgurl']) && $fan->headimgurl = $options['headimgurl'];
		isset($options['country']) && $fan->country = $options['country'];
		isset($options['province']) && $fan->province = $options['province'];
		isset($options['city']) && $fan->city = $options['city'];

		$fan->id = $this->insert('xxt_pl_wxfan', $fan, true);

		return $fan;
	}
	/**
	 *
	 */
	public function &getGroups($siteid) {
		$q = array(
			'id,name',
			'xxt_pl_wxfangroup',
			"1=1",
		);
		$q2 = array('o' => 'id');

		$groups = $this->query_objs_ss($q, $q2);

		return $groups;
	}
	/**
	 *
	 */
	public function modifyByOpenid($openid, $updated) {
		return $this->update('xxt_pl_wxfan', $updated, "openid='$openid'");
	}
}