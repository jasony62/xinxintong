<?php
/**
 *
 */
class TMS_MODEL {
	/**
	 *
	 */
	public static function insert($table, $data = null, $autoid = DEFAULT_DB_AUTOID) {
		return TMS_DB::db()->insert($table, $data, $autoid);
	}

	public static function update($table, $data = null, $where = null) {
		return TMS_DB::db()->update($table, $data, $where);
	}

	public static function delete($table, $where) {
		return TMS_DB::db()->delete($table, $where);
	}

	public static function query_value($select, $from = null, $where = null) {
		return TMS_DB::db()->query_value($select, $from, $where);
	}

	public static function query_values($select, $from = null, $where = null) {
		return TMS_DB::db()->query_values($select, $from, $where);
	}

	public static function query_obj($select, $from = null, $where = null) {
		return TMS_DB::db()->query_obj($select, $from, $where);
	}

	public static function query_objs($select, $from = null, $where = null, $group = null, $order = null, $limit = null, $offset = null) {
		return TMS_DB::db()->query_objs($select, $from, $where, $group, $order, $limit, $offset);
	}

	public static function found_rows() {
		return TMS_DB::db()->query_value('SELECT FOUND_ROWS()');
	}

	public static function escape($str) {
		return TMS_DB::db()->escape($str);
	}
	/**
	 * Array(
	 * s=>select
	 * f=>from
	 * w=>where
	 * g=>group
	 * o=>order
	 * r=>range
	 * )
	 */
	public static function query_objs_s($params) {
		$select = $params['s'];
		$from = $params['f'] ? $params['f'] : null;
		$where = isset($params['w']) ? $params['w'] : null;
		$group = isset($params['g']) ? $params['g'] : null;
		$order = isset($params['o']) ? $params['o'] : null;
		$offset = $limit = null;
		if (isset($params['r'])) {
			$offset = $params['r']['o'] ? $params['r']['o'] : null;
			$limit = $params['r']['l'] ? $params['r']['l'] : null;
		}
		return self::query_objs($select, $from, $where, $group, $order, $offset, $limit);
	}
	/**
	 *
	 */
	public static function query_val_s($params) {
		$select = $params['s'];
		$from = $params['f'] ? $params['f'] : null;
		$where = isset($params['w']) ? $params['w'] : null;
		return self::query_value($select, $from, $where);
	}
	/**
	 * $p [select,from,where]
	 */
	public static function query_objs_ss($p, $p2 = null) {
		// select,from,where
		$select = $p[0];
		$from = $p[1] ? $p['1'] : null;
		$where = isset($p[2]) ? $p[2] : null;
		// group,order by,limit
		$group = $order = $offset = $limit = null;
		if ($p2) {
			$group = !empty($p2['g']) ? $p2['g'] : null;
			$order = !empty($p2['o']) ? $p2['o'] : null;
			if (!empty($p2['r'])) {
				$offset = $p2['r']['o'];
				$limit = $p2['r']['l'];
			}
		}
		return self::query_objs($select, $from, $where, $group, $order, $offset, $limit);
	}
	/**
	 * $params [select,from,where]
	 */
	public static function query_obj_ss($p, $p2 = null) {
		$select = $p[0];
		$from = $p[1];
		$where = isset($p[2]) ? $p[2] : null;
		return self::query_obj($select, $from, $where);
	}
	/**
	 * $params [select,from,where]
	 */
	public static function query_val_ss($p) {
		$select = $p[0];
		$from = $p[1];
		$where = isset($p[2]) ? $p[2] : null;
		return self::query_value($select, $from, $where);
	}
	/**
	 * $params [select,from,where]
	 */
	public static function query_vals_ss($p) {
		$select = $p[0];
		$from = $p[1];
		$where = isset($p[2]) ? $p[2] : null;
		return self::query_values($select, $from, $where);
	}
	/**
	 *
	 * return 32bit
	 */
	protected static function uuid($prefix) {
		!$prefix && $prefix = TMS_CLIENT::get_client_uid();
		return md5(uniqid($prefix) . mt_rand());
	}
	/**
	 * 用户信息加密解密函数
	 * 待加密内容用\t分割
	 *
	 * @return String 加密或解密字符串
	 * @param String $string 待加密或解密字符串
	 * @param String $operation 操作类型定义 DECODE=解密 ENCODE=加密
	 * @param String $key 加密算子
	 */
	public static function encrypt($string, $operation, $key) {
		/**
		 * 获取密码算子,如未指定，采取系统默认算子
		 * 默认算子是论坛授权码和用户浏览器信息的md5散列值
		 */
		$key_length = strlen($key);
		/**
		 * 如果解密，先对密文解码
		 * 如果加密，将密码算子和待加密字符串进行md5运算后取前8位
		 * 并将这8位字符串和待加密字符串连接成新的待加密字符串
		 */
		$string = $operation == 'DECODE' ? base64_decode(str_replace(array('-', '_'), array('+', '/'), $string)) : substr(md5($string . $key), 0, 8) . $string;
		$string_length = strlen($string);
		$rndkey = $box = array();
		$result = '';
		/**
		 * 初始化加密变量，$rndkey和$box
		 */
		for ($i = 0; $i <= 255; $i++) {
			$rndkey[$i] = ord($key[$i % $key_length]);
			$box[$i] = $i;
		}
		/**
		 * $box数组打散供加密用
		 */
		for ($j = $i = 0; $i < 256; $i++) {
			$j = ($j + $box[$i] + $rndkey[$i]) % 256;
			$tmp = $box[$i];
			$box[$i] = $box[$j];
			$box[$j] = $tmp;
		}
		/**
		 * $box继续打散,并用异或运算实现加密或解密
		 */
		for ($a = $j = $i = 0; $i < $string_length; $i++) {
			$a = ($a + 1) % 256;
			$j = ($j + $box[$a]) % 256;
			$tmp = $box[$a];
			$box[$a] = $box[$j];
			$box[$j] = $tmp;
			$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
		}

		if ($operation == 'DECODE') {
			if (substr($result, 0, 8) == substr(md5(substr($result, 8) . $key), 0, 8)) {
				return substr($result, 8);
			} else {
				return '';
			}
		} else {
			//return str_replace('=', '', base64_encode($result));
			return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($result));
		}
	}
	/**
	 * generate a 32bits salt.
	 */
	public static function gen_salt($length = 32) {
		$alpha_digits = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$alpha_digits_len = strlen($alpha_digits) - 1;

		$salt = '';
		for ($i = 0; $i < $length; $i++) {
			$salt .= $alpha_digits[mt_rand(0, $alpha_digits_len)];
		}

		return $salt;
	}
	/**
	 * 对用户的密码进行加密
	 */
	public static function compile_password($identity, $password, $salt) {
		$sign = $identity . $salt . $password;

		$sha = hash('sha256', $sign);

		return $sha;
	}
	/**
	 *
	 */
	public static function toLocalEncoding($str) {
		if (defined('TMS_LOCAL_ENCODING') && TMS_LOCAL_ENCODING !== 'UTF-8') {
			$str = iconv('UTF-8', TMS_LOCAL_ENCODING, $str);
		}

		return $str;
	}
	/**
	 *
	 */
	public static function toUTF8($str) {
		if (defined('TMS_LOCAL_ENCODING') && TMS_LOCAL_ENCODING !== 'UTF-8') {
			$str = iconv(TMS_LOCAL_ENCODING, 'UTF-8', $str);
		}

		return $str;
	}
	/**
	 *
	 */
	public function &M($model_path) {
		return TMS_APP::M($model_path);
	}
	/**
	 *
	 */
	public static function urlencodeObj($obj) {
		if (is_object($obj)) {
			$newObj = new \stdClass;
			foreach ($obj as $k => $v) {
				$newObj->{urlencode($k)} = self::urlencodeObj($v);
			}
		} else if (is_array($obj)) {
			$newObj = array();
			foreach ($obj as $k => $v) {
				$newObj[urlencode($k)] = self::urlencodeObj($v);
			}
		} else {
			$newObj = urlencode($obj);
		}

		return $newObj;
	}
	/**
	 *
	 */
	public static function toJson($obj) {
		$obj = self::urlencodeObj($obj);
		$json = urldecode(json_encode($obj));

		return $json;
	}
}