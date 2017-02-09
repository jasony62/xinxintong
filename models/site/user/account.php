<?php
namespace site\user;
/**
 * 站点访客用户
 */
class account_model extends \TMS_MODEL {
	/**
	 * 缺省的用户组
	 */
	const DEFAULT_LEVEL = 1;
	/**
	 *
	 *
	 * @param string $uid
	 *
	 * @return object
	 */
	public function &byId($uid, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : 'uid,nickname,wx_openid,yx_openid,qy_openid,unionid';
		$q = array(
			$fields,
			'xxt_site_account',
			["uid" => $uid],
		);
		$act = $this->query_obj_ss($q);

		return $act;
	}
	/**
	 * get account object by it's email
	 *
	 * @param string $siteId
	 * @param string $snsName
	 * @param string $openid
	 *
	 * @return object
	 */
	public function &byOpenid($siteId, $snsName, $openid, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : 'uid,nickname,wx_openid,yx_openid,qy_openid,unionid';

		$q = [
			$fields,
			'xxt_site_account',
			["siteid" => $siteId, $snsName . '_openid' => $openid],
		];
		$act = $this->query_obj_ss($q);

		return $act;
	}
	/**
	 * get account object by it's email
	 *
	 * $param string $email
	 *
	 * return object
	 */
	public function &byUname($siteId, $uname, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : 'siteid,uid,nickname,password,salt,wx_openid,yx_openid,qy_openid';
		$q = array(
			$fields,
			'xxt_site_account',
			"siteid='$siteId' and uname='$uname'",
		);
		$act = $this->query_obj_ss($q);

		return $act;
	}
	/**
	 * get account objects by it's unionid
	 *
	 * @param string $unionid
	 *
	 * @return object
	 */
	public function &byUnionid($unionid, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : 'siteid,uid,nickname,wx_openid,yx_openid,qy_openid,is_reg_primary';
		$q = [
			$fields,
			'xxt_site_account',
			["unionid" => $unionid],
		];
		if (isset($options['is_reg_primary'])) {
			$q[2]['is_reg_primary'] = $options['is_reg_primary'];
		}
		$acts = $this->query_objs_ss($q);

		return $acts;
	}
	/**
	 * 创建空站点访客帐号
	 *
	 * 数据合格性检查
	 * 1，一个站点下，对应一个注册账号，只能有一个主访客账号
	 *
	 *
	 * @param string $siteId
	 * @param bool $persisted 是否在数据库中创建
	 * @param array $props
	 *
	 * @return object
	 */
	public function &blank($siteid, $persisted = false, $props = array()) {
		/* new accouont key */
		$uid = isset($props['uid']) ? $props['uid'] : uniqid();
		/* new account */
		$current = time();
		$account = new \stdClass;
		$account->siteid = $siteid;
		$account->uid = $uid;
		$account->unionid = isset($props['unionid']) ? $props['unionid'] : '';
		$account->is_reg_primary = isset($props['is_reg_primary']) ? $props['is_reg_primary'] : 'N';
		$account->level_id = self::DEFAULT_LEVEL;
		$account->reg_time = $current;

		if (empty($props['nickname'])) {
			$account->nickname = '用户' . $uid;
		} else {
			$account->nickname = $props['nickname'];
		}
		if (isset($props['ufrom'])) {
			$account->ufrom = $props['ufrom'];
		}
		if (isset($props['wx_openid'])) {
			$account->wx_openid = $props['wx_openid'];
		}
		if (isset($props['yx_openid'])) {
			$account->yx_openid = $props['yx_openid'];
		}
		if (isset($props['qy_openid'])) {
			$account->qy_openid = $props['qy_openid'];
		}
		if (isset($props['headimgurl'])) {
			$account->headimgurl = $props['headimgurl'];
		}
		if ($persisted === true) {
			$this->insert('xxt_site_account', $account, false);
		}

		return $account;
	}
	/**
	 * 创建站点访客用户帐号
	 *
	 * 数据合格性检查
	 *
	 * @param string $siteId
	 */
	public function &create($siteId, $uname, $password, $props = array()) {
		$current = time();
		/*password*/
		//$pw_salt = $this->gen_salt();
		//$pw_hash = $this->compile_password($uname, $password, $pw_salt);
		/*ip*/
		$from_ip = empty($props['from_ip']) ? '' : $props['from_ip'];
		$nickname = empty($props['nickname']) ? '' : $props['nickname'];
		$unionid = empty($props['unionid']) ? '' : $props['unionid'];
		$assoc_id = empty($props['assoc_id']) ? '' : $props['assoc_id'];
		$is_reg_primary = empty($props['is_reg_primary']) ? '' : $props['is_reg_primary'];

		if (isset($props['uid'])) {
			/* 指定了用户ID */
			$uid = $props['uid'];
			if ($existed = $this->byId($uid)) {
				$account = [
					'unionid' => $unionid,
					'assoc_id' => $assoc_id,
					'is_reg_primary' => $is_reg_primary,
					'nickname' => $nickname,
					//'uname' => $uname,
					//'password' => $pw_hash,
					//'salt' => $pw_salt,
					'reg_time' => $current,
					'reg_ip' => $from_ip,
					'last_login' => $current,
					'last_ip' => $from_ip,
					'last_active' => $current,
				];
				$rst = $this->update(
					'xxt_site_account',
					$account,
					"siteid='$siteId' and uid='$uid'"
				);
			} else {
				$account = [
					'siteid' => $siteId,
					'uid' => $uid,
					'unionid' => $unionid,
					'assoc_id' => $assoc_id,
					'is_reg_primary' => $is_reg_primary,
					'nickname' => $nickname,
					//'uname' => $uname,
					//'password' => $pw_hash,
					//'salt' => $pw_salt,
					'reg_time' => $current,
					'reg_ip' => $from_ip,
					'last_login' => $current,
					'last_ip' => $from_ip,
					'last_active' => $current,
					'level_id' => self::DEFAULT_LEVEL,
				];
				$this->insert('xxt_site_account', $account, false);
			}
		} else {
			/*new accouont key*/
			$uid = uniqid();
			$account = [
				'siteid' => $siteId,
				'uid' => $uid,
				'unionid' => $unionid,
				'assoc_id' => $assoc_id,
				'is_reg_primary' => $is_reg_primary,
				'nickname' => $nickname,
				//'uname' => $uname,
				//'password' => $pw_hash,
				//'salt' => $pw_salt,
				'reg_time' => $current,
				'reg_ip' => $from_ip,
				'last_login' => $current,
				'last_ip' => $from_ip,
				'last_active' => $current,
				'level_id' => self::DEFAULT_LEVEL,
			];
			$this->insert('xxt_site_account', $account, false);
		}
		$account = (object) $account;

		return $account;
	}
	/**
	 * record last login information.
	 */
	public function updateLastLogin($uid, $from_ip) {
		$updated['last_login'] = time();
		$updated['last_ip'] = $from_ip;
		$rst = $this->update(
			'xxt_site_account',
			$updated,
			"uid='$uid'"
		);

		return $rst;
	}
	/**
	 * 修改昵称
	 */
	public function changeNickname($siteId, $uid, $nickname) {
		$rst = $this->update(
			'xxt_site_account',
			['nickname' => $nickname],
			["siteid" => $siteId, "uid" => $uid]
		);

		return $rst;
	}
	/**
	 * 删除一个注册用户
	 *
	 * 如果一个用户从来没有登录过，就可以直接删除
	 *
	 */
	public function remove($uid) {
		$q = array(
			'count(*)',
			'xxt_site_account',
			"uid='$uid' and reg_time=last_login",
		);
		/**
		 * 从来没有登录过，直接删除数据
		 */
		if ('1' === $this->query_val_ss($q)) {
			$this->delete(
				'xxt_site_account',
				"uid='$uid'"
			);
			return true;
		}
		return false;
	}
}