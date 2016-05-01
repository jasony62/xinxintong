<?php
session_start();
/**
 * for long time execution.
 */
@set_time_limit(0);
/**
 * local timezone
 */
date_default_timezone_set('Asia/Shanghai');
/**
 * character set
 */
ini_set('default_charset', 'utf-8');
/**
 * error and exception handle
 */
function show_error($message) {
	require_once 'tms/tms_app.php';
	header("HTTP/1.1 500 Internal Server Error");
	header('Content-Type: text/plain; charset=utf-8');
	if ($message instanceof Exception) {
		$msg = $message->getMessage() . "\n";
		$trace = $message->getTrace();
		foreach ($trace as $t) {
			foreach ($t as $k => $v) {
				$msg .= $k . ':' . json_encode($v) . "\n";
			}
		}
	} else {
		$msg = $message;
	}
	echo $msg;
	TMS_APP::M('log')->log('error', 'error', $msg);
	exit;
}

function tms_error_handler($errno, $errstr, $errfile, $errline, $errcontext) {
	switch ($errno) {
	case E_ERROR:show_error(new ErrorException('ERROR:' . $errstr, 0, $errno, $errfile, $errline));
	case E_WARNING:show_error(new ErrorException('E_WARNING:' . $errstr, 0, $errno, $errfile, $errline));
	case E_PARSE:show_error(new ErrorException('E_PARSE:' . $errstr, 0, $errno, $errfile, $errline));
	case E_NOTICE:show_error(new ErrorException('E_NOTICE:' . $errstr, 0, $errno, $errfile, $errline));
	case E_CORE_ERROR:show_error(new ErrorException('E_CORE_ERROR:' . $errstr, 0, $errno, $errfile, $errline));
	case E_CORE_WARNING:show_error(new ErrorException('E_CORE_WARNING:' . $errstr, 0, $errno, $errfile, $errline));
	case E_COMPILE_ERROR:show_error(new ErrorException('E_COMPILE_ERROR:' . $errstr, 0, $errno, $errfile, $errline));
	case E_COMPILE_WARNING:show_error(new ErrorException('E_COMPILE_WARNING:' . $errstr, 0, $errno, $errfile, $errline));
	case E_USER_ERROR:show_error(new ErrorException('E_USER_ERROR:' . $errstr, 0, $errno, $errfile, $errline));
	case E_USER_WARNING:show_error(new ErrorException('E_USER_WARNING:' . $errstr, 0, $errno, $errfile, $errline));
	case E_USER_NOTICE:show_error(new ErrorException('E_USER_NOTICE:' . $errstr, 0, $errno, $errfile, $errline));
	case E_STRICT:show_error(new ErrorException('E_STRICT:' . $errstr, 0, $errno, $errfile, $errline));
	case E_RECOVERABLE_ERROR:show_error(new ErrorException('E_RECOVERABLE_ERROR:' . $errstr, 0, $errno, $errfile, $errline));
	case E_DEPRECATED:show_error(new ErrorException('E_DEPRECATED:' . $errstr, 0, $errno, $errfile, $errline));
	case E_USER_DEPRECATED:show_error(new ErrorException('E_USER_DEPRECATED:' . $errstr, 0, $errno, $errfile, $errline));
	}
}
set_error_handler('tms_error_handler');

function tms_exception_handler($exception) {
	show_error($exception);
}
set_exception_handler('tms_exception_handler');
/**
 * error handle
 */
ini_set('display_errors', 'On');
//ini_set('display_errors', 'Off');
error_reporting(E_ALL);
//error_reporting(E_ERROR);
/**
 * database resource.
 */
include_once 'db.php';
/**
 * 加载本地化设置
 */
file_exists(dirname(__FILE__) . '/cus/app.php') && include_once dirname(__FILE__) . '/cus/app.php';
/**
 * 常量定义不允许被覆盖，需要检查常量是否已经被定义
 */
/**
 * 微信要求采用TLSv1
 */
!defined('APP_TITLE') && define('APP_TITLE', '信信通');
/**
 * 微信要求采用TLSv1
 */
!defined('CURL_SSLVERSION_TLSv1') && define('CURL_SSLVERSION_TLSv1', 1);
/**
 * 是否缺省返回autoid
 */
define('DEFAULT_DB_AUTOID', true);
/**
 * cookie
 */
/* 定义 Cookies 作用域 */
define('G_COOKIE_DOMAIN', '');
/* 定义 Cookies 前缀 */
define('G_COOKIE_PREFIX', 'xxt');
/* 定义应用加密 KEY */
define('G_COOKIE_HASH_KEY', 'gzuhhqnckcryrrd');
/* 用户信息在cookie中保存的天数 */
define('TMS_COOKIE_SITE_USER_EXPIRE', 3650);
define('TMS_COOKIE_SITE_LOGIN_EXPIRE', 30);
/* 重新绑定公众号未关注用户信息的间隔 */
define('TMS_COOKIE_SITE_USER_BIND_INTERVAL', 600);
/**
 * app's local position.
 */
/* 应用程序起始目录 */
define('TMS_APP_DIR', dirname(__FILE__));
/* 应用程序视图名称，起始路径为：TMS_APP_DIR.'/views/'.TMS_APP_VIEW_NAME */
!defined('TMS_APP_VIEW_NAME') && define('TMS_APP_VIEW_NAME', 'default');
/* 应用程序模版起始目录 */
!defined('TMS_APP_TEMPLATE') && define('TMS_APP_TEMPLATE', dirname(__FILE__) . '/_template');
/**
 * app's uri.
 */
!defined('TMS_APP_URI') && define('TMS_APP_URI', '');
/**
 * prefix for rest.
 * 需要和web服务器的配置一致
 */
!defined('TMS_APP_API_PREFIX') && define('TMS_APP_API_PREFIX', '/rest'); // 前缀API前缀
!defined('TMS_APP_VIEW_PREFIX') && define('TMS_APP_VIEW_PREFIX', '/page'); // 请求页面前缀
/**
 * default page.
 */
!defined('TMS_APP_UNAUTH') && define('TMS_APP_UNAUTH', '/pl/fe/user/auth'); // 未认证通过的缺省页
define('TMS_APP_AUTHED', '/pl/fe'); // 认证通过后的缺省页
/**
 * default upload directory
 */
if (defined('SAE_TMP_PATH')) {
	define('TMS_UPLOAD_DIR', SAE_TMP_PATH);
} else {
	define('TMS_UPLOAD_DIR', 'kcfinder/upload/');
}
/**
 * run application.
 */
require_once 'tms/tms_app.php';

$config = array();
TMS_APP::run($config);