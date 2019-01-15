<?php
/**
 * @author Jason
 * @copyright 2012
 */
class ResponseData {
	public $err_code;
	public $err_msg;
	public $data;

	public function __construct($data, $code = 0, $msg = 'success') {
		$this->err_code = $code;
		$this->err_msg = $msg;
		$this->data = $data;
	}

	public function toJsonString() {
		return json_encode($this);
	}
}
class ResponseError extends ResponseData {
	public function __construct($msg, $data = null) {
		parent::__construct($data, -1, $msg);
	}
}
class ResponseTimeout extends ResponseData {
	public function __construct($msg = '') {
		empty($msg) && $msg = '长时间未操作，请重新<a href="/rest/pl/fe/user/auth" target="_self">登录</a>！';
		parent::__construct(null, -2, $msg);
	}
}
class InvalidAccessToken extends ResponseData {
	public function __construct($msg = '没有获得有效访问令牌') {
		parent::__construct(null, -3, $msg);
	}
}
class ParameterError extends ResponseData {
	public function __construct($msg = '参数错误。') {
		parent::__construct(null, 100, $msg);
	}
}
class ObjectNotFoundError extends ResponseData {
	public function __construct($msg = '指定的对象不存在。') {
		parent::__construct(null, 100, $msg);
	}
}
class ResultEmptyError extends ResponseData {
	public function __construct($msg = '获得的结果为空。') {
		parent::__construct(null, 101, $msg);
	}
}
class ComplianceError extends ResponseData {
	public function __construct($msg = '业务逻辑错误。') {
		parent::__construct(null, 200, $msg);
	}
}
class DataExistedError extends ResponseData {
	public function __construct($msg = '数据已经存在。') {
		parent::__construct(null, 201, $msg);
	}
}
class DatabaseError extends ResponseData {
	public function __construct($msg = '数据库错误。') {
		parent::__construct(null, 900, $msg);
	}
}
/**
 * url找不到匹配的处理接口
 */
class UrlNotMatchException extends Exception {

}
/**************************
 * 常用方法
 **************************/
/**
 * 数组中查找对象并返回
 */
function tms_array_search($array, $callback) {
	if (empty($array) || !is_array($array)) {
		return false;
	}

	foreach ($array as $item) {
		if ($callback($item)) {
			return $item;
		}
	}

	return false;
}
/**
 * 合并对象
 */
function tms_object_merge(&$oHost, $oNew, $fromProps = []) {
	if (empty($oHost) || empty($oNew) || !is_object($oHost)) {
		return $oHost;
	}
	if (empty($fromProps)) {
		foreach ($oNew as $prop => $val) {
			$oHost->{$prop} = $val;
		}
	} else {
		if (is_object($oNew)) {
			foreach ($fromProps as $prop) {
				if (isset($oNew->{$prop})) {
					$oHost->{$prop} = $oNew->{$prop};
				}
			}
		} else if (is_array($oNew)) {
			foreach ($fromProps as $prop) {
				if (isset($oNew[$prop])) {
					$oHost->{$prop} = $oNew[$prop];
				}
			}
		}
	}

	return $oHost;
}