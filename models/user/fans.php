<?php
/**
 *
 */
class fans_model extends TMS_MODEL {
	/**
	 *
	 */
	public function byId($fid, $fields = '*') {
		$q = array(
			$fields,
			'xxt_fans',
			"fid='$fid'",
		);
		$fan = $this->query_obj_ss($q);

		return $fan;
	}
	/**
	 *
	 */
	public function &byOpenid($mpid, $openid, $fields = '*', $followed = null) {
		$q = array(
			$fields,
			'xxt_fans',
			"mpid='$mpid' and openid='$openid'",
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
	public function byMid($mid) {
		$q = array(
			'f.*',
			'xxt_fans f',
			"exists(select 1 from xxt_member m where f.fid=m.fid and m.mid='$mid')",
		);

		$fan = $this->query_obj_ss($q);

		return $fan;

	}
	/**
	 * 是否关注了公众号
	 *
	 * todo 企业号的用户如何判断？
	 */
	public function isFollow($mpid, $openid) {
		if (empty($openid)) {
			return false;
		}

		$q = array(
			'count(*)',
			'xxt_fans',
			"mpid='$mpid' and openid='$openid' and unsubscribe_at=0",
		);

		$isFollow = (1 === (int) $this->query_val_ss($q));

		return $isFollow;
	}
	/**
	 * 计算关注用户的内部id
	 */
	public function calcId($mpid, $openid) {
		return md5($mpid . $openid);
	}
	/**
	 * 创建空的关注用户
	 */
	public function blank($mpid, $openid, $persisted = true, $options = array()) {
		$fan = new stdClass;
		$fan->fid = $this->calcId($mpid, $openid);
		$fan->mpid = $mpid;
		$fan->openid = $openid;
		!empty($options['userid']) && $fan->userid = $options['userid'];

		$this->insert('xxt_fans', $fan, false);

		return $fan;
	}
	/**
	 *
	 */
	public function getGroups($mpid) {
		$q = array(
			'id,name',
			'xxt_fansgroup',
			"mpid='$mpid'",
		);
		$q2 = array('o' => 'id');

		$groups = $this->query_objs_ss($q, $q2);

		return $groups;
	}
}