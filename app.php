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
 * memory limit
 */
!defined('SAE_TMP_PATH') && ini_set('memory_limit', '-1');
/**
 * database resource.
 */
error_reporting(E_ERROR); // 控制系统的报错信息，否则数据库连接失败会报warning
/**
 * 加载本地化设置
 */
file_exists(dirname(__FILE__) . '/cus/app.php') && include_once dirname(__FILE__) . '/cus/app.php';
/*********************************************
 * 常量定义不允许被覆盖，需要检查常量是否已经被定义
 *********************************************/
/**
 * 定义应用的主机名
 */
!defined('APP_HTTP_HOST') && define('APP_HTTP_HOST', $_SERVER['HTTP_HOST']);
/**
 * 定义应用的标题
 */
!defined('APP_TITLE') && define('APP_TITLE', '信信通');
/**
 * 定义应用的logo
 */
!defined('APP_LOGO') && define('APP_LOGO', '/static/img/logo.png');
/**
 * 定义应用的logo
 */
!defined('APP_ACCESS_BANNER') && define('APP_ACCESS_BANNER', '/static/img/access.png');
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
define('TMS_COOKIE_SITE_LOGIN_EXPIRE', 7);
/* 重新绑定公众号未关注用户信息的间隔 */
define('TMS_COOKIE_SITE_USER_BIND_INTERVAL', 600);
/**
 * app's local position.
 */
/* 应用程序起始目录 */
define('TMS_APP_DIR', dirname(__FILE__));
/* 应用程序视图名称，起始路径为：TMS_APP_DIR.'/views/'.TMS_APP_VIEW_NAME */
!defined('TMS_APP_VIEW_NAME_DEFAULT') && define('TMS_APP_VIEW_NAME_DEFAULT', 'default');
!defined('TMS_APP_VIEW_NAME') && define('TMS_APP_VIEW_NAME', TMS_APP_VIEW_NAME_DEFAULT);
/* 应用程序模版起始目录 */
define('TMS_APP_TEMPLATE_DEFAULT', dirname(__FILE__) . '/_template');
!defined('TMS_APP_TEMPLATE') && define('TMS_APP_TEMPLATE', TMS_APP_TEMPLATE_DEFAULT);
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

/***********************
 * 设置平台入口
 ***********************/
/**
 * 平台首页，未指定，或未找到指定地址时跳转到首页。
 */
!defined('TMS_APP_HOME') && define('TMS_APP_HOME', '/rest/home');
/**
 * 用户未认证通过时缺省页
 */
!defined('TMS_APP_UNAUTH') && define('TMS_APP_UNAUTH', '/rest/site/fe/user/access');
define('TMS_APP_AUTHED', '/pl/fe'); // 认证通过后的缺省页

/*************************
 * default upload directory
 *************************/
if (defined('SAE_TMP_PATH')) {
	define('TMS_UPLOAD_DIR', SAE_TMP_PATH);
} else {
	define('TMS_UPLOAD_DIR', 'kcfinder/upload/');
}

/***************************
 * error and exception handle
 ***************************/
function show_error($message) {
	require_once 'tms/tms_app.php';
	if ($message instanceof UrlNotMatchException) {
		$msg = $message->getMessage();
	} else if ($message instanceof Exception) {
		$excep = $message->getMessage() . "\n";
		$trace = $message->getTrace();
		foreach ($trace as $t) {
			foreach ($t as $k => $v) {
				$excep .= $k . ':' . json_encode($v) . "\n";
			}
		}
		if (defined('TMS_APP_EXCEPTION_TRACE') && TMS_APP_EXCEPTION_TRACE === 'Y') {
			$msg = $excep;
		} else {
			$msg = '应用程序内部错误';
		}
	} else {
		$msg = $message;
	}
	/* 返回信息 */
	header("HTTP/1.1 500 Internal Server Error");
	header('Content-Type: text/plain; charset=utf-8');
	echo $msg;

	/* 记录日志 */
	$method = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'unknown request';
	$agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
	$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
	if (isset($excep)) {
		TMS_APP::M('log')->log('error', $method, $excep, $agent, $referer);
	} else {
		TMS_APP::M('log')->log('error', $method, $msg, $agent, $referer);
	}

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
 *  Given a file, i.e. /css/base.css, replaces it with a string containing the
 *  file's mtime, i.e. /css/base.1221534296.css.
 *
 *  @param $file  The file to be loaded.  Must be an absolute path (i.e.
 *                starting with slash).
 */
function auto_version($file) {
	if (strpos($file, DIRECTORY_SEPARATOR) !== 0 || !file_exists(TMS_APP_DIR . $file)) {
		return $file;
	}
	$mtime = filemtime(TMS_APP_DIR . $file);
	$file .= '?_=' . $mtime;
	return $file;
}
/**
 * 获得文件的定制版本
 */
function custom_version($file) {
	if (0 !== strpos($file, DIRECTORY_SEPARATOR)) {
		$file = DIRECTORY_SEPARATOR . $file;
	}

	$full = '/views/' . TMS_APP_VIEW_NAME . $file;
	if (!file_exists(TMS_APP_DIR . $full)) {
		$full = '/views/' . TMS_APP_VIEW_NAME_DEFAULT . $file;
	}

	$full = auto_version($full);

	$full = TMS_APP_URI . $full;

	return $full;
}
/**
 * error handle
 */
ini_set('display_errors', 'On');
//ini_set('display_errors', 'Off');
error_reporting(E_ALL);
//error_reporting(E_ERROR);
/*************************
 * run application.
 *************************/
require_once 'tms/tms_app.php';

$config = array();
TMS_APP::run($config);