<?php
//@removed
class account_model extends TMS_MODEL {

	const TABLE_A = 'account';
	const TABLE_G = 'account_group';
	const TABLE_AG = 'account_in_group';
	const DEFAULT_GROUP = 1; // 初级用户
	/**
	 * 用户账号信息
	 *
	 * @param string $uid
	 *
	 * return
	 */
	public function byId($uid, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : 'uid,nickname,email,password,salt';
		$q = [
			$fields,
			'account',
			["uid" => $uid],
		];
		$oAccount = $this->query_obj_ss($q);
		if ($oAccount) {
			if (!empty($aOptions['cascaded'])) {
				if (in_array('group', $aOptions['cascaded'])) {
					$q = [
						'*',
						'account_group ag',
						"exists(select 1 from account_in_group aig where ag.group_id=aig.group_id and aig.account_uid='{$uid}')",
					];
					$oAccount->group = $this->query_obj_ss($q);
				}
			}
		}

		return $oAccount;
	}
	/**
	 *
	 * $param string $authed_id
	 * $param string $authed_from
	 *
	 * return account
	 */
	public function byAuthedId($authid, $authed_from) {
		$q = array(
			'a.uid,a.nickname,a.email,a.password,a.salt',
			'account a,account_in_group ag,account_group g',
			"a.uid=ag.account_uid and ag.group_id=g.group_id and a.authed_id='$authid' and a.authed_from='$authed_from'",
		);
		if ($act = $this->query_obj_ss($q)) {
			return $act;
		} else {
			return false;
		}

	}
	/**
	 * register a new account.
	 */
	public function register($email, $password, $nickname, $from_ip) {
		/**
		 * new accouont key
		 */
		$uid = uniqid();
		/**
		 * password.
		 */
		$pw_salt = $this->gen_salt();
		$pw_hash = $this->compile_password($email, $password, $pw_salt);
		/*
			        * new account
		*/
		$current = time();
		$account = array(
			'uid' => $uid,
			'authed_from' => 'xxt',
			'authed_id' => $email,
			'nickname' => $nickname,
			'password' => $pw_hash,
			'salt' => $pw_salt,
			'email' => $email,
			'reg_time' => $current,
			'reg_ip' => $from_ip,
			'last_login' => $current,
			'last_ip' => $from_ip,
			'last_active' => $current,
		);
		$this->insert(self::TABLE_A, $account, false);
		/**
		 * account group
		 */
		$account_group = array(
			'account_uid' => $uid,
			'group_id' => self::DEFAULT_GROUP,
		);
		$this->insert(self::TABLE_AG, $account_group, false);

		return (object) $account;
	}
	/**
	 * 对外部认证通过的用户进行注册
	 */
	public function authed_from($authed_id, $authed_from, $from_ip, $nickname = '') {
		/**
		 * new accouont key
		 */
		$uid = uniqid();
		/**
		 * new account
		 */
		$current = time();
		$account = array(
			'uid' => $uid,
			'authed_id' => $authed_id,
			'authed_from' => $authed_from,
			'nickname' => $nickname,
			'reg_time' => $current,
			'reg_ip' => $from_ip,
			'last_login' => $current,
			'last_ip' => $from_ip,
			'last_active' => $current,
		);
		$this->insert(self::TABLE_A, $account);
		/**
		 * account group
		 */
		$account_group = array(
			'account_uid' => $uid,
			'group_id' => self::DEFAULT_GROUP,
		);
		$this->insert(self::TABLE_AG, $account_group);

		return (object) $account;
	}
	/**
	 * email valid and existed?
	 */
	public function checkUname($uname) {
		return $this->query_value('1', self::TABLE_A, "email='$uname'");
	}
	/**
	 * record last login information.
	 */
	public function update_last_login($uid, $from_ip) {
		$updated['last_login'] = time();
		$updated['last_ip'] = $from_ip;
		$rst = $this->update(self::TABLE_A, $updated, ["uid" => $uid]);

		return $rst;
	}
	/**
	 *
	 */
	public function change_password($email, $password, $pw_salt) {
		$pw_hash = $this->compile_password($email, $password, $pw_salt);
		$update_data['password'] = $pw_hash;
		$rst = $this->update(self::TABLE_A, $update_data, ["email" => $email]);

		return $rst;
	}
	/**
	 *
	 */
	public function is_email_used($email) {
		return (bool) $this->query_value('1', self::TABLE_A, ["email" => $email]);
	}
	/**
	 * validate login information.
	 *
	 * $param string $email
	 * $param string $password
	 *
	 * return ResponseData
	 */
	public function validate($email, $password) {
		if (!$account = $this->get_account_by_email($email)) {
			return new ParameterError('您输入的用户名不存在。');
		}
		$pw_hash = $this->compile_password($email, $password, $account->salt);
		if ($pw_hash != $account->password) {
			return new ParameterError('您输入的用户名或密码不正确。');
		}

		return new ResponseData($account);
	}
	/**
	 * get account object by it's email
	 *
	 * $param string $email
	 *
	 * return object
	 */
	public function get_account_by_email($email) {
		$s = 'a.uid,a.nickname,a.email,a.password,a.salt';
		$f = 'account a,account_in_group ag,account_group g';
		$w = 'a.uid=ag.account_uid and ag.group_id=g.group_id';
		$w .= " and a.email='$email'";
		if ($account = $this->query_obj($s, $f, $w)) {
			//if ($account->permission) {
			//    $account->permission = unserialize($account->permission);
			//}
			return $account;
		} else {
			return false;
		}
	}
	/**
	 * get account object by it's authedid
	 *
	 * $param string $authed_id
	 *
	 * return object
	 */
	public function getAccountByAuthedId($authed_id) {
		$s = 'a.uid,a.nickname,a.email,a.password,a.salt';
		$f = 'account a,account_in_group ag,account_group g';
		$w = 'a.uid=ag.account_uid and ag.group_id=g.group_id';
		$w .= " and (a.authed_id='$authed_id' || a.email='$authed_id')";

		$account = $this->query_obj($s, $f, $w);

		return $account;
	}
	/**
	 *
	 */
	public function getAccount($page = 1, $size = 30, $filter = null) {
		$q = [
			'a.uid,a.nickname,a.email,a.reg_time,a.last_login,a.forbidden,a.coin,g.group_id,g.group_name',
			'account a,account_group g,account_in_group i',
			'a.uid=i.account_uid and i.group_id=g.group_id',
		];
		if (isset($filter->email)) {
			$q[2] .= " and a.email like '%" . $this->escape($filter->email) . "%'";
		}

		$q2['o'] = 'reg_time desc';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;

		$accounts = $this->query_objs_ss($q, $q2);

		$q = [
			'count(*)',
			'account',
		];
		$amount = (int) $this->query_val_ss($q);

		return [$accounts, $amount];
	}
	/**
	 *
	 */
	public function getGroup($gid = null) {
		$q = array(
			'g.group_id,g.group_name,g.asdefault,g.view_name,g.p_mpgroup_create,g.p_mp_create,g.p_mp_permission,g.p_platform_manage,p_create_site,count(i.account_uid) account_count',
			'account_group g left join account_in_group i on g.group_id=i.group_id',
		);
		if (empty($gid)) {
			$q2['g'] = 'g.group_id';
			$groups = $this->query_objs_ss($q, $q2);

			return $groups;
		} else {
			$q[] = "g.group_id=$gid";
			$group = $this->query_obj_ss($q);

			return $group;
		}
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
			'account',
			"uid='$uid' and reg_time=last_login",
		);
		/**
		 * 从来没有登录过，直接删除数据
		 */
		if (1 === (int) $this->query_val_ss($q)) {
			$this->delete(
				'account_in_group',
				"account_uid='$uid'"
			);
			$this->delete(
				'account',
				"uid='$uid'"
			);
			return true;
		}
		return false;
	}
	/**
	 * 检查用户所在组的权限
	 */
	public function canManagePlatform($uid) {
		$q = [
			'g.group_id,g.p_platform_manage',
			'account_group g,account_in_group i',
			"i.group_id=g.group_id and i.account_uid='$uid'",
		];

		$right = $this->query_obj_ss($q);

		return isset($right->p_platform_manage) && $right->p_platform_manage === '1';
	}
	/**
	 * 检查用户所在组的权限
	 */
	public function canCreateSite($uid) {
		$q = [
			'g.group_id,g.p_create_site',
			'account_group g,account_in_group i',
			"i.group_id=g.group_id and i.account_uid='$uid'",
		];

		$right = $this->query_obj_ss($q);

		return isset($right->p_create_site) && $right->p_create_site === '1';
	}
}