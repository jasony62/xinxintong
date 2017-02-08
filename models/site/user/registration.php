<?php
namespace site\user;
/**
 * 站点注册用户
 */
class registration_model extends \TMS_MODEL {
	/**
	 *
	 *
	 * @param string $uid
	 *
	 * @return object
	 */
	public function &byId($uid, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : 'uname,nickname,password,salt';
		$q = [
			$fields,
			'xxt_site_registration',
			["unionid" => $uid],
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
	public function &byUname($uname) {
		$fields = isset($options['fields']) ? $options['fields'] : 'unionid,nickname,password,salt';
		$q = [
			$fields,
			'xxt_site_registration',
			["uname" => $uname],
		];
		$reg = $this->query_obj_ss($q);

		return $reg;
	}
	/**
	 * 创建空帐号
	 *
	 * @param bool $persisted 是否在数据库中创建
	 * @param array $options
	 */
	public function &blank($siteid, $persisted = false, $options = array()) {
		/*new accouont key*/
		$uid = isset($options['uid']) ? $options['uid'] : uniqid();
		/*new account*/
		$current = time();
		$account = new \stdClass;
		$account->siteid = $siteid;
		$account->uid = $uid;
		$account->level_id = self::DEFAULT_LEVEL;
		$account->reg_time = $current;
		if (isset($options['ufrom'])) {
			$account->ufrom = $options['ufrom'];
		}
		if (isset($options['wx_openid'])) {
			$account->wx_openid = $options['wx_openid'];
		}
		if (isset($options['yx_openid'])) {
			$account->yx_openid = $options['yx_openid'];
		}
		if (isset($options['qy_openid'])) {
			$account->qy_openid = $options['qy_openid'];
		}
		if (isset($options['nickname'])) {
			$account->nickname = $options['nickname'];
		}
		if (isset($options['headimgurl'])) {
			$account->headimgurl = $options['headimgurl'];
		}
		if ($persisted === true) {
			$this->insert('xxt_site_account', $account, false);
		}

		return $account;
	}
	/**
	 * 注册用户帐号
	 */
	public function &create($siteId, $uname, $password, $options = array()) {
		$current = time();
		/*password*/
		$pw_salt = $this->gen_salt();
		$pw_hash = $this->compile_password($uname, $password, $pw_salt);

		/*ip*/
		$from_ip = empty($options['from_ip']) ? '' : $options['from_ip'];
		$nickname = empty($options['nickname']) ? '' : $options['nickname'];

		$unionid = md5($siteid . uniqid());

		$account = new \stdClass;
		$registration->unionid = $unionid;
		$registration->uname = $uname;
		$registration->nickname = $nickname;
		$registration->password = $pw_hash;
		$registration->salt = $pw_salt;
		$registration->reg_time = $current;
		$registration->reg_ip = $from_ip;
		$registration->last_login = $current;
		$registration->last_ip = $from_ip;
		$registration->last_active = $current;

		$this->insert('xxt_site_registration', $registration, false);

		return $registration;
	}
	/**
	 * uname valid and existed?
	 */
	public function checkUname($uname) {
		$q = [
			'1',
			'xxt_site_registration',
			["uname" => $uname],
		];
		$rst = $this->query_val_ss($q);

		return $rst;
	}
	/**
	 * record last login information.
	 */
	public function updateLastLogin($uid, $from_ip) {
		$updated['last_login'] = time();
		$updated['last_ip'] = $from_ip;
		$rst = $this->update(
			'xxt_site_registration',
			$updated,
			["unionid" => $uid]
		);

		return $rst;
	}
	/**
	 * 修改昵称
	 */
	public function changeNickname($siteId, $uname, $nickname) {
		$rst = $this->update(
			'xxt_site_account',
			array('nickname' => $nickname),
			"siteid='$siteId' and uname='$uname'"
		);

		return $rst;
	}
	/**
	 * 修改口令
	 */
	public function changePwd($siteId, $uname, $password, $pw_salt) {
		$pw_hash = $this->compile_password($uname, $password, $pw_salt);
		$update_data['password'] = $pw_hash;
		$rst = $this->update(
			'xxt_site_account',
			$update_data,
			"siteid='$siteId' and uname='$uname'"
		);

		return $rst;
	}
	/**
	 * 检查邮箱是否已被注册
	 */
	public function isUnameUsed($siteId, $uname) {
		$rst = $this->query_val_ss(
			'1',
			'xxt_site_account',
			"siteid='$siteId' and uname='$uname'"
		);

		return $rst === '1';
	}
	/**
	 * validate login information.
	 *
	 * @param string $uname
	 * @param string $password
	 *
	 * @return object|string
	 */
	public function validate($uname, $password) {
		if (!$registration = $this->byUname($uname)) {
			return '用户名不存在';
		}
		$pw_hash = $this->compile_password($uname, $password, $registration->salt);
		if ($pw_hash != $registration->password) {
			return '用户名或密码不正确';
		}

		return $registration;
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