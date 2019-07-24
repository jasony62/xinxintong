<?php
session_start();

include_once dirname(__FILE__) . '/config.php';

/***************************
 * error and exception handle
 ***************************/
/**
 * file
 * line
 * function
 * args
 * type
 * class
 */
function show_error($message) {
    require_once 'tms/tms_app.php';
    $modelLog = TMS_APP::M('log');
    if ($message instanceof UrlNotMatchException) {
        $msg = $message->getMessage();
    } else if ($message instanceof Exception) {
        $excep = $message->getMessage();
        $trace = $message->getTrace();
        foreach ($trace as $t) {
            $excep .= '<br>';
            foreach ($t as $k => $v) {
                if (!in_array($k, ['file', 'line'])) {
                    continue;
                }
                $excep .= $modelLog->toJson($v) . ' ';
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
    $method = isset($_SERVER['REQUEST_URI']) ? tms_get_server('REQUEST_URI') : 'unknown request';
    $agent = isset($_SERVER['HTTP_USER_AGENT']) ? tms_get_server('HTTP_USER_AGENT') : '';
    $referer = isset($_SERVER['HTTP_REFERER']) ? tms_get_server('HTTP_REFERER') : '';
    if (isset($excep)) {
        $msg = str_replace('<br>', "\n", $excep);
    }

    $msg = $modelLog->escape($msg);
    if ($message instanceof SiteUserException) {
        $modelLog->log($message->getUserid(), $method, $msg, $agent, $referer);
    } else {
        $modelLog->log('error', $method, $msg, $agent, $referer);
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
 * 设置默认数学计算精度
 */
if (defined('APP_TMS_BCSCALE')) {
    bcscale(APP_TMS_BCSCALE);
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