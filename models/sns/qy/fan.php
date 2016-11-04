<?php
namespace sns\qy;
/**
 * 微信公众号关注用户
 */
class fan_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function &bySite($siteId, $page = 1, $size = 30, $options = []) {
		$keyword = isset($options['keyword']) ? $options['keyword'] : null;

		$result = new \stdClass;

		$q[] = 'f.openid,f.subscribe_at,f.nickname,f.sex,f.city';
		$q[] = 'xxt_site_qyfan f';
		$w = "f.siteid='$siteId' and f.unsubscribe_at=0 and f.forbidden='N'";
		/**
		 * search by keyword
		 */
		if (!empty($keyword)) {
			$w .= " and (f.nickname like '%$keyword%'";
			$w .= ")";
		}
		
		$q[] = $w;

		/**
		 * order by and pagination
		 */
		$q2['o'] = 'subscribe_at desc';
		$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];

		if ($result->fans = $this->query_objs_ss($q, $q2)) {
			$q[0] = 'count(*)';
			$result->total = (int) $this->query_val_ss($q);
		}

		return $result;
	}
	/**
	 *
	 */
	public function &byOpenid($siteid, $openid, $fields = '*', $followed = null) {
		$q = [
			$fields,
			'xxt_site_qyfan',
			"siteid='$siteid' and openid='$openid'",
		];
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

		$q = [
			'count(*)',
			'xxt_site_qyfan',
			"siteid='$siteid' and openid='$openid' and subscribe_at>0 and unsubscribe_at=0",
		];

		$isFollow = (1 === (int) $this->query_val_ss($q));

		return $isFollow;
	}
	/**
	 * 创建空的关注用户
	 */
	public function &blank($siteId, $openid, $persisted = true, $options = array()) {
		$fan = new \stdClass;
		$fan->siteid = $siteId;
		$fan->openid = $openid;
		//!empty($options['userid']) && $fan->userid = $options['userid'];
		$fan->subscribe_at = isset($options['subscribe_at']) ? $options['subscribe_at'] : 0;
		$fan->sync_at = isset($options['sync_at']) ? $options['sync_at'] : 0;

		$fan->nickname = isset($options['nickname']) ? $options['nickname'] : '';
		isset($options['sex']) && $fan->sex = $options['sex'];
		isset($options['headimgurl']) && $fan->headimgurl = $options['headimgurl'];
		isset($options['country']) && $fan->country = $options['country'];
		isset($options['province']) && $fan->province = $options['province'];
		isset($options['city']) && $fan->city = $options['city'];

		$fan->id = $this->insert('xxt_site_qyfan', $fan, true);

		//$fan = $this->byOpenid($siteId, $openid);

		return $fan;
	}
	/**
	 *
	 */
	public function modifyByOpenid($siteid, $openid, $updated) {
		return $this->update('xxt_site_qyfan', $updated, "siteid='$siteid' and openid='$openid'");
	}
}