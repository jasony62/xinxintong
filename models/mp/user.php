<?php
namespace mp;
/**
 * 平台管理端注册用户
 */
class user_model extends \TMS_MODEL {

	const DEFAULT_GROUP = 1; // 缺省的用户组
	/**
	 *
	 *
	 * $param string $uid
	 *
	 * return
	 */
	public function byId($uid, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : 'uid,nickname,email,password,salt';
		$q = array(
			$fields,
			'account',
			"uid='$uid'",
		);
		$act = $this->query_obj_ss($q);

		return $act;
	}
	/**
	 * 根据第三方应用中的用户ID，返回用户信息
	 *
	 * @param string $authed_id 第三方应用中用户的ID
	 * @param string $authed_from 第三方应用的标示
	 *
	 * @return object account
	 */
	public function byAuthedId($authid, $authed_from) {
		$q = array(
			'a.uid,a.nickname,a.email,a.password,a.salt',
			'account a,account_in_group ag,account_group g',
			"a.uid=ag.account_uid and ag.group_id=g.group_id and a.authed_id='$authid' and a.authed_from='$authed_from'",
		);
		$this->query_obj_ss($q);

		return $act;
	}
	/**
	 * 注册用户帐号
	 */
	public function register($email, $password, $nickname, $from_ip) {
		/*new accouont key*/
		$uid = uniqid();
		/*password*/
		$pw_salt = $this->gen_salt();
		$pw_hash = $this->compile_password($email, $password, $pw_salt);
		/*new account*/
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
		$this->insert('account', $account, false);
		/**
		 * account group
		 */
		$account_group = array(
			'account_uid' => $uid,
			'group_id' => self::DEFAULT_GROUP,
		);
		$this->insert('account_group', $account_group, false);

		return (object) $account;
	}
	/**
	 * 第三方应用认证通过的用户进行注册
	 */
	public function authed_from($authed_id, $authed_from, $from_ip, $nickname = '') {
		/*new accouont key*/
		$uid = uniqid();
		/*new account*/
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
		$this->insert('account', $account, false);
		/*account group*/
		$account_group = array(
			'account_uid' => $uid,
			'group_id' => self::DEFAULT_GROUP,
		);
		$this->insert('account_group', $account_group, false);

		return (object) $account;
	}
	/**
	 * email valid and existed?
	 */
	public function check_email($email) {
		$rst = $this->query_val_ss(
			'1',
			'account',
			"email='$email'"
		);

		return $rst;
	}
	/**
	 * record last login information.
	 */
	public function update_last_login($uid, $from_ip) {
		$updated['last_login'] = time();
		$updated['last_ip'] = $from_ip;
		$rst = $this->update(
			'account',
			$updated, "uid='$uid'"
		);

		return $rst;
	}
	/**
	 * 修改口令
	 */
	public function change_password($email, $password, $pw_salt) {
		$pw_hash = $this->compile_password($email, $password, $pw_salt);
		$update_data['password'] = $pw_hash;
		$rst = $this->update(
			'account',
			$update_data,
			"email='$email'"
		);

		return $rst;
	}
	/**
	 * 检查邮箱是否已被注册
	 */
	public function is_email_used($email) {
		$rst = $this->query_val_ss(
			'1',
			'account',
			"email='$email'"
		);

		return $rst === '1';
	}
	/**
	 * validate login information.
	 *
	 * @param string $email
	 * @param string $password
	 *
	 * @return ResponseData
	 */
	public function validate($email, $password) {
		if (!$account = $this->get_account_by_email($email)) {
			return array(false, '您输入的用户名不存在。');
		}
		$pw_hash = $this->compile_password($email, $password, $account->salt);
		if ($pw_hash != $account->password) {
			return array(false, '您输入的用户名或密码不正确。');
		}

		return array(true, $account);
	}
	/**
	 * get account object by it's email
	 *
	 * $param string $email
	 *
	 * return object
	 */
	public function get_account_by_email($email) {
		$q = array(
			'a.uid,a.nickname,a.email,a.password,a.salt',
			'account a,account_in_group ag,account_group g',
			'a.uid=ag.account_uid and ag.group_id=g.group_id' . " and a.email='$email'",
		);
		if ($account = $this->query_obj_ss($q)) {
			//if ($account->permission) {
			//    $account->permission = unserialize($account->permission);
			//}
		}

		return $account;
	}
	/**
	 * get account object by it's authedid
	 *
	 * $param string $authed_id
	 *
	 * return object
	 */
	public function getAccountByAuthedId($authed_id) {
		$q = array('a.uid,a.nickname,a.email,a.password,a.salt',
			'account a,account_in_group ag,account_group g',
			'a.uid=ag.account_uid and ag.group_id=g.group_id' . " and (a.authed_id='$authed_id' || a.email='$authed_id')",
		);
		$account = $this->query_obj_ss($q);

		return $account;
	}
	/**
	 * 获得账户列表
	 */
	public function getAccount($page = 1, $size = 30) {
		$q = array(
			'a.uid,a.nickname,a.email,a.reg_time,a.last_login,a.forbidden,g.group_id,g.group_name',
			'account a,account_group g,account_in_group i',
			'a.uid=i.account_uid and i.group_id=g.group_id',
		);
		$q2['o'] = 'reg_time desc';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;
		$accounts = $this->query_objs_ss($q, $q2);

		$q = array(
			'count(*)',
			'account',
		);
		$amount = (int) $this->query_val_ss($q);

		return array($accounts, $amount);
	}
	/**
	 * 获得用户组列表
	 */
	public function getGroup($gid = null) {
		$q = array(
			'g.group_id,g.group_name,g.asdefault,g.p_mpgroup_create,g.p_mp_create,g.p_mp_permission,count(i.account_uid) account_count',
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
}