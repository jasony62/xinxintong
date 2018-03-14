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
		$w = "f.siteid='$siteId' and f.subscribe_at>0 and f.unsubscribe_at=0 and f.forbidden='N'";
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
	public function byOpenid($siteid, $openid, $fields = '*', $followed = null) {
		$q = [
			$fields,
			'xxt_site_qyfan',
			"siteid='$siteid' and openid='$openid'",
		];
		if ($followed === 'Y') {
			$q[2] .= " and unsubscribe_at=0";
		}
		$fans = $this->query_objs_ss($q);
		if (count($fans) === 0) {
			return false;
		} else if (count($fans) > 1) {
			throw new \Exception('数据库数据错误');
		}

		return $fans[0];
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

		$fan->nickname = isset($options['nickname']) ? $this->escape($options['nickname']) : '';
		isset($options['sex']) && $fan->sex = empty($options['sex'])? 0 : $options['sex'];
		isset($options['headimgurl']) && $fan->headimgurl = $options['headimgurl'];
		isset($options['country']) && $fan->country = $this->escape($options['country']);
		isset($options['province']) && $fan->province = $this->escape($options['province']);
		isset($options['city']) && $fan->city = $this->escape($options['city']);

		$fan->id = $this->insert('xxt_site_qyfan', $fan, true);

		return $fan;
	}
	/**
	 *
	 */
	public function modifyByOpenid($siteid, $openid, $updated) {
		return $this->update('xxt_site_qyfan', $updated, "siteid='$siteid' and openid='$openid'");
	}
	/**
	 *获取企业号通讯录人员信息
	 */
	public function &getMem($site, $keyword = '', $page = 1, $size) {
		$p = array('*', 'xxt_site_qyfan', "siteid = '$site' and subscribe_at > 0 and unsubscribe_at = 0 and nickname like '%$keyword%'");

		$p2['r']['o'] = ($page - 1) * $size;
		$p2['r']['l'] = $size;
		$p2['o'] = 'id desc';
		$result = array();
		if ($data = $this->query_objs_ss($p, $p2)) {
			$result['data'] = $data;
			$p[0] = 'count(*)';
			$total = (int) $this->query_val_ss($p);
			$result['total'] = $total;
		} else {
			$result['data'] = array();
			$result['total'] = 0;
		}

		return $result;
	}
	/**
	 * 更新企业号用户信息
	 */
	public function updateQyFan($site, $luser, $user, $authid, $timestamp = null, $mapDeptR2L = null) {

		empty($timestamp) && $timestamp = time();

		$fan = array();
		$fan['sync_at'] = $timestamp;
		isset($user->mobile) && $fan['mobile'] = $user->mobile;
		isset($user->email) && $fan['email'] = $user->email;
		$extattr = array();
		if (isset($user->extattr) && !empty($user->extattr->attrs)) {
			foreach ($user->extattr->attrs as $ea) {
				$extattr[urlencode($ea->name)] = urlencode($ea->value);
			}
		}
		$fan['tags'] = ''; // 先将成员的标签清空，标签同步的阶段会重新更新
		/**
		 * 处理岗位信息
		 */
		if (!empty($user->position)) {
			$extattr['position'] = urlencode($user->position);
		}
		$fan['extattr'] = urldecode(json_encode($extattr));
		/**
		 * 建立成员和部门之间的关系
		 */
		$udepts = array();
		foreach ($user->department as $ud) {
			if (empty($mapDeptR2L)) {
				$q = array(
					'fullpath',
					'xxt_site_member_department',
					"siteid='$site' and extattr like '%\"id\":$ud,%'",
				);
				$fullpath = $this->query_val_ss($q);
				$udepts[] = explode(',', $fullpath);
			} else {
				isset($mapDeptR2L[$ud]) && $udepts[] = explode(',', $mapDeptR2L[$ud]['path']);
			}
		}
		$fan['depts'] = json_encode($udepts);
		$fan['sex'] = $user->gender;
		/*
			 * 建立企业号通信录成员关联到信信通的账户
		*/
		$openid = $user->userid;
		$uid = $this->query_val_ss([
			'uid',
			'xxt_site_account',
			" siteid='$site' and qy_openid ='$openid' ",
		]);

		if (!empty($uid)) {
			$fan['userid'] = $uid;
		} else {
			$option = array(
				'ufrom' => 'qy',
				'qy_openid' => $openid,
				'nickname' => $user->name,
				'headimgurl' => isset($user->avatar) ? $user->avatar : '',
			);

			$account = \TMS_MODEL::M("site\\user\\account")->blank($site, true, $option);

			$fan['userid'] = $account->uid;
		}
		/**
		 * 成员用户对应的粉丝用户
		 */
		if ($old = \TMS_MODEL::M('sns\qy\fan')->byOpenid($site, $user->userid)) {
			$fan['nickname'] = $user->name;
			isset($user->avatar) && $fan['headimgurl'] = $user->avatar;
			if ($user->status == 1 && $old->subscribe_at == 0) {
				$fan['subscribe_at'] = $timestamp;
			} else if ($user->status == 1 && $old->unsubscribe_at != 0) {
				$fan['unsubscribe_at'] = 0;
			} else if ($user->status == 4 && $old->unsubscribe_at == 0) {
				$fan['unsubscribe_at'] = $timestamp;
			}
			$this->update(
				'xxt_site_qyfan',
				$fan,
				"siteid='$site' and openid='{$user->userid}'"
			);
			$sync_id = $old->id;
		} else {
			$fan['siteid'] = $site;
			$fan['openid'] = $user->userid;
			$fan['nickname'] = $user->name;
			isset($user->avatar) && $fan['headimgurl'] = $user->avatar;
			$user->status == 1 && $fan['subscribe_at'] = $timestamp;
			$sync_id = $this->insert('xxt_site_qyfan', $fan, true);
		}

		return true;
	}

}