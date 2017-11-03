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
		$fields = isset($options['fields']) ? $options['fields'] : 'uid unionid,email uname,nickname,password,salt,from_siteid';
		$q = [
			$fields,
			'account',
			["uid" => $uid],
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
	public function &byUname($uname, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : 'uid unionid,email uname,nickname,password,salt,from_siteid';
		$q = [
			$fields,
			'account',
			["email" => $uname],
		];
		if (isset($options['forbidden'])) {
			/* 帐号是否已关闭 */
			$q[2]['forbidden'] = $options['forbidden'];
		}
		$reg = $this->query_obj_ss($q);

		return $reg;
	}
	/**
	 * 注册用户帐号
	 */
	public function create($siteId, $uname, $password, $options = array()) {
		$this->setOnlyWriteDbConn(true);
		if ($this->checkUname($uname)) {
			return [false, '注册账号已经存在，不能重复注册'];
		}

		$current = time();
		/*password*/
		$pw_salt = $this->gen_salt();
		$pw_hash = $this->compile_password($uname, $password, $pw_salt);

		/*ip*/
		$from_ip = empty($options['from_ip']) ? '' : $options['from_ip'];
		$nickname = empty($options['nickname']) ? '' : $options['nickname'];

		$unionid = md5($uname . $siteId);

		$registration = new \stdClass;
		//$registration->unionid = $unionid;
		$registration->uid = $unionid;
		$registration->from_siteid = $siteId;
		$registration->authed_from = 'xxt_site';
		$registration->authed_id = $this->escape($uname);
		$registration->email = $this->escape($uname);
		$registration->nickname = $this->escape($nickname);
		$registration->password = $pw_hash;
		$registration->salt = $pw_salt;
		$registration->reg_time = $current;
		$registration->reg_ip = $from_ip;
		$registration->last_login = $current;
		$registration->last_ip = $from_ip;
		$registration->last_active = $current;

		$this->insert('account', $registration, false);
		$registration = $this->byId($unionid);

		/* 指定缺省用户组 */
		$account_group = [
			'account_uid' => $unionid,
			'group_id' => 1,
		];
		$this->insert('account_in_group', $account_group, false);

		return [true, $registration];
	}
	/**
	 * uname valid and existed?
	 */
	public function checkUname($uname) {
		$q = [
			'1',
			'account',
			["email" => $uname],
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
			'account',
			$updated,
			["uid" => $uid]
		);

		return $rst;
	}
	/**
	 * 修改昵称
	 */
	public function changeNickname($uname, $nickname) {
		$rst = $this->update(
			'account',
			['nickname' => $nickname],
			["email" => $uname]
		);

		return $rst;
	}
	/**
	 * 修改口令
	 */
	public function changePwd($uname, $password, $pw_salt) {
		$pw_hash = $this->compile_password($uname, $password, $pw_salt);
		$update_data['password'] = $pw_hash;
		$rst = $this->update(
			'account',
			$update_data,
			["email" => $uname]
		);

		return $rst;
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
		if (!$registration = $this->byUname($uname, ['forbidden' => 0])) {
			return '指定的登录用户名不存在';
		}

		$pw_hash = $this->compile_password($uname, $password, $registration->salt);
		if ($pw_hash != $registration->password) {
			return '提供的登录用户名或密码不正确';
		}

		return $registration;
	}
}