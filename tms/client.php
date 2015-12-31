<?php

class TMS_CLIENT {
	/**
	 * 获得当前用户的ID
	 *
	 * 先通过session获得uid
	 * 若取不到再通过cookie获得uid
	 */
	public static function get_client_uid() {
		if (isset($_SESSION['account_info'])) {
			return $_SESSION['account_info']->uid;
		} else {
			if (isset($_COOKIE[G_COOKIE_PREFIX . "_user_login"])) {
				$sso = TMS_CLIENT::decode_hash($_COOKIE[G_COOKIE_PREFIX . "_user_login"]);
				if ($sso['uid'] && $_SERVER['HTTP_USER_AGENT'] == $sso['UA']) {
					return $sso['uid'];
				}
			}
		}
		return false;
	}
	/**
	 * 当前访问者是否已登录,uid为空则未登录
	 */
	public static function is_authenticated() {
		$uid = self::get_client_uid();
		return !empty($uid);
	}
	/**
	 * get/set 当前用户信息
	 *
	 * $param object $account
	 */
	public static function account($account = null) {
		if ($account == null) {
			if (isset($_SESSION['account_info'])) {
				return $_SESSION['account_info'];
			} else {
				return false;
			}
		} else {
			/**
			 * store account information in cookie.
			 */
			self::setcookie_login(
				$account->uid,
				$account->nickname
			);
			/**
			 * store account information in session.
			 */
			$_SESSION['account_info'] = $account;
		}
	}
	/**
	 * 结束登录状态
	 */
	public static function logout() {
		/**
		 * clean cookie
		 */
		self::set_cookie('_user_login', '');
		self::set_cookie('_nickname', '');
		/**
		 * clean session
		 */
		session_destroy();
	}
	/**
	 * 设置登录时候的COOKIE信息
	 */
	private static function setcookie_login($uid, $nickname, $expire = null) {
		if (empty($uid)) {
			return false;
		}
		$hash = self::get_login_cookie_hash($uid);

		self::set_cookie('_user_login', $hash, $expire);
		self::set_cookie('_nickname', $nickname, $expire);

		return true;
	}
	/**
	 * 设置 COOKIE
	 * @param string $name
	 * @param string $value
	 * @param int $expire
	 * @param string $path
	 * @param string $domain
	 * @param string $secure
	 */
	private static function set_cookie($name, $value = '', $expire = null, $path = '/', $domain = null, $secure = false) {
		if (!$domain and G_COOKIE_DOMAIN) {
			$domain = G_COOKIE_DOMAIN;
		}
		return setcookie(G_COOKIE_PREFIX . $name, $value, $expire, $path, $domain, $secure);
	}
	/**
	 * 将用户信息编码为加密串
	 *
	 * 加密串中包含:uid,UA
	 */
	private static function get_login_cookie_hash($uid) {
		return self::encode_hash(array(
			'uid' => $uid,
			'UA' => $_SERVER['HTTP_USER_AGENT']));
	}
	/**
	 * 加密hash，生成发送给用户的hash字符串
	 *
	 * @param array $hash_arr
	 * @return string
	 */
	private static function encode_hash($hash_arr, $hash_key = false) {
		if (empty($hash_arr)) {
			return false;
		}

		$hash_str = "";

		foreach ($hash_arr as $key => $value) {
			$hash_str .= $key . "^]+" . $value . "!;-";
		}

		$hash_str = substr($hash_str, 0, -3);

		// 加密干扰码，加密解密时需要用到的KEY
		if (!$hash_key) {
			$hash_key = G_COOKIE_HASH_KEY;
		}

		// 加密过程
		$tmp_str = '';

		for ($i = 1; $i <= strlen($hash_str); $i++) {
			$char = substr($hash_str, $i - 1, 1);
			$keychar = substr($hash_key, ($i % strlen($hash_key)) - 2, 1);
			$char = chr(ord($char) + ord($keychar));
			$tmp_str .= $char;
		}

		$hash_str = base64_encode($tmp_str);
		$hash_str = str_replace(array('+', '/', '='), array('-', '_', '.'), $hash_str);

		return $hash_str;
	}

	/**
	 * 解密hash，从用户回链的hash字符串解密出里面的内容
	 *
	 * @param string $hash_str
	 * @param boolean $b_urldecode	当$hash_str不是通过浏览器传递的时候就需要urldecode,否则会解密失败，反之也一样
	 * @return array
	 */
	public static function decode_hash($hash_str, $b_urldecode = false, $hash_key = false) {
		if (empty($hash_str)) {
			return array();
		}

		// 加密干扰码，加密解密时需要用到的KEY
		if (!$hash_key) {
			$hash_key = G_COOKIE_HASH_KEY;
		}

		//解密过程
		$tmp_str = '';

		if (strpos($hash_str, "-") || strpos($hash_str, "_") || strpos($hash_str, ".")) {
			$hash_str = str_replace(array('-', '_', '.'), array('+', '/', '='), $hash_str);
		}

		$hash_str = base64_decode($hash_str);

		for ($i = 1; $i <= strlen($hash_str); $i++) {
			$char = substr($hash_str, $i - 1, 1);
			$keychar = substr($hash_key, ($i % strlen($hash_key)) - 2, 1);
			$char = chr(ord($char) - ord($keychar));
			$tmp_str .= $char;
		}

		$hash_arr = array();
		$arr = explode("!;-", $tmp_str);

		foreach ($arr as $value) {
			list($k, $v) = explode("^]+", $value);
			if ($k) {
				$hash_arr[$k] = $v;
			}
		}

		return $hash_arr;
	}
}