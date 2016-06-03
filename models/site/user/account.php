<?php
namespace site\user;
/**
 * 站点注册用户
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
		$fields = isset($options['fields']) ? $options['fields'] : 'uid,uname,nickname,email,password,salt';
		$q = array(
			$fields,
			'xxt_site_account',
			"uid='$uid'",
		);
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
	public function &byUname($siteId, $uname) {
		$fields = isset($options['fields']) ? $options['fields'] : 'uid,nickname,password,salt';
		$q = array(
			$fields,
			'xxt_site_account',
			"siteid='$siteId' and uname='$uname'",
		);
		$act = $this->query_obj_ss($q);

		return $act;
	}
	/**
	 * 创建空帐号
	 *
	 * @param bool $persisted 是否在数据库中创建
	 * @param array $options
	 */
	public function &blank($siteid, $persisted = false, $options = array()) {
		/*new accouont key*/
		$uid = uniqid();
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
		if (isset($options['uid'])) {
			$uid = $options['uid'];
			if ($existed = $this->byId($uid)) {
				$account = array(
					'uname' => $uname,
					'nickname' => $nickname,
					'password' => $pw_hash,
					'salt' => $pw_salt,
					'reg_time' => $current,
					'reg_ip' => $from_ip,
					'last_login' => $current,
					'last_ip' => $from_ip,
					'last_active' => $current,
				);
				$rst = $this->update(
					'xxt_site_account',
					$account,
					"siteid='$siteId' and uid='$uid'"
				);
			} else {
				$account = array(
					'siteid' => $siteId,
					'uid' => $uid,
					'uname' => $uname,
					'nickname' => $nickname,
					'password' => $pw_hash,
					'salt' => $pw_salt,
					'reg_time' => $current,
					'reg_ip' => $from_ip,
					'last_login' => $current,
					'last_ip' => $from_ip,
					'last_active' => $current,
					'level_id' => self::DEFAULT_LEVEL,
				);
				$this->insert('xxt_site_account', $account, false);
			}
		} else {
			/*new accouont key*/
			$uid = uniqid();
			$account = array(
				'siteid' => $siteId,
				'uid' => $uid,
				'uname' => $uname,
				'nickname' => $nickname,
				'password' => $pw_hash,
				'salt' => $pw_salt,
				'reg_time' => $current,
				'reg_ip' => $from_ip,
				'last_login' => $current,
				'last_ip' => $from_ip,
				'last_active' => $current,
				'level_id' => self::DEFAULT_LEVEL,
			);
			$this->insert('xxt_site_account', $account, false);
		}
		$account = (object) $account;

		return $account;
	}
	/**
	 * uname valid and existed?
	 */
	public function checkUname($siteId, $uname) {
		$q = array(
			'1',
			'xxt_site_account',
			"siteid='$siteId' and uname='$uname'",
		);
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
			'xxt_site_account',
			$updated,
			"uid='$uid'"
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
	 * @return ResponseData
	 */
	public function validate($siteId, $uname, $password) {
		if (!$account = $this->byUname($siteId, $uname)) {
			return '用户名不存在';
		}
		$pw_hash = $this->compile_password($uname, $password, $account->salt);
		if ($pw_hash != $account->password) {
			return '用户名或密码不正确';
		}

		return $account;
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