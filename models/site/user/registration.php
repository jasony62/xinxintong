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
	public function &byId($uid, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : 'uid unionid,email uname,nickname,password,salt,from_siteid,forbidden';
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
	public function &byUname($uname, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : 'uid unionid,email uname,nickname,password,salt,from_siteid';
		$q = [
			$fields,
			'account',
			["email" => $uname],
		];
		if (isset($aOptions['forbidden'])) {
			/* 帐号是否已关闭 */
			$q[2]['forbidden'] = $aOptions['forbidden'];
		}
		$reg = $this->query_obj_ss($q);

		return $reg;
	}
	/**
	 * 注册用户帐号
	 */
	public function create($siteId, $uname, $password, $aOptions = []) {
		$this->setOnlyWriteDbConn(true);
		if ($this->checkUname($uname)) {
			return [false, '注册账号已经存在，不能重复注册'];
		}

		$current = time();
		/*password*/
		$pw_salt = $this->gen_salt();
		$pw_hash = $this->compile_password($uname, $password, $pw_salt);

		/*ip*/
		$from_ip = empty($aOptions['from_ip']) ? '' : $aOptions['from_ip'];
		$nickname = empty($aOptions['nickname']) ? '' : $aOptions['nickname'];

		$unionid = md5($uname . $siteId);

		$registration = new \stdClass;
		//$registration->unionid = $unionid;
		$registration->uid = $unionid;
		$registration->from_siteid = $siteId;
		$registration->authed_from = empty($aOptions['authed_from']) ? 'xxt_site' : $aOptions['authed_from'];
		$registration->authed_id = empty($aOptions['authed_id']) ? $uname : $aOptions['authed_id'];
		$registration->email = $uname;
		$registration->nickname = $nickname;
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
		];
		$account_group['group_id'] = empty($aOptions['group_id'])? 1 : $aOptions['group_id'];
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
	 * @return array
	 */
	public function validate($uname, $password) {
		if (!$oRegistration = $this->byUname($uname, ['forbidden' => 0, 'fields' => 'uid unionid,email uname,nickname,password,salt,from_siteid,login_limit_expire,pwd_error_num'])) {
			return [false, '用户名或密码错误'];
		}

		$current = time();
		if ($oRegistration->login_limit_expire > $current) {
			$residueTime = $oRegistration->login_limit_expire - $current;
			$residue = ($residueTime > 60) ? round($residueTime / 60) . '分' : $residueTime . '秒';

			return [false, '错误次数超过（' . TMS_APP_PASSWORD_ERROR_AUTHLOCK . '）次，请在 ' . $residue . ' 后再次尝试'];
		}
		// 校验密码
		$pw_hash = $this->compile_password($uname, $password, $oRegistration->salt);
		if ($pw_hash != $oRegistration->password) {
			if (TMS_APP_PASSWORD_ERROR_AUTHLOCK > 0) {
				$errorNum = $oRegistration->pwd_error_num + 1; // 总错误次数
				if ($errorNum < TMS_APP_PASSWORD_ERROR_AUTHLOCK) {
					$this->update('account', ['pwd_error_num' => $errorNum], ['uid' => $oRegistration->unionid]);

					return [false, '用户名或密码错误，错误次数超过 ' . TMS_APP_PASSWORD_ERROR_AUTHLOCK . ' 次后，将锁定登录' . TMS_APP_PASSWORD_ERROR_AUTHLOCK_EXPIRE . '分钟, 剩余 ' . (TMS_APP_PASSWORD_ERROR_AUTHLOCK - $errorNum) . ' 次'];
				} else {
					// 锁定登录, 错误次数归零。
					if (TMS_APP_PASSWORD_ERROR_AUTHLOCK_EXPIRE > 0) {
						$expire = $current + (TMS_APP_PASSWORD_ERROR_AUTHLOCK_EXPIRE * 60);
					} else { // 默认60分钟
						$expire = $current + (60 * 60);
					}
					$updata = [
						'pwd_error_num' => 0,
						'login_limit_expire' => $expire
					];
					$this->update('account', $updata, ['uid' => $oRegistration->unionid]);

					return [false, '错误次数超过（' . TMS_APP_PASSWORD_ERROR_AUTHLOCK . '）次，请在 ' . TMS_APP_PASSWORD_ERROR_AUTHLOCK_EXPIRE . '分钟后再次尝试'];
				}
			} else {
				return [false, '用户名或密码错误'];
			}
		}

		// 密码正确需要重置密码错误次数
		$this->update('account', ['pwd_error_num' => 0], ['uid' => $oRegistration->unionid]);

		return [true, $oRegistration];
	}
}