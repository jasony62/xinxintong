<?php

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
 * 加载本地化设置
 */
file_exists(dirname(__FILE__) . '/cus/config.php') && include_once dirname(__FILE__) . '/cus/config.php';
/**
* 错误输出级别
*/
!defined('APP_REEOR_REPORTING_LEVEL') && define('APP_REEOR_REPORTING_LEVEL', 'ALL');

/*********************************************
 * 常量定义不允许被覆盖，需要检查常量是否已经被定义
 *********************************************/
/**
 * ICP备案
 */
!defined('APP_ICP_BEIAN') && define('APP_ICP_BEIAN', '');
/**
 * 定义应用的主机名
 */
!defined('APP_HTTP_HOST') && define('APP_HTTP_HOST', $_SERVER['HTTP_HOST']);
/**
 * https
 */
!defined('APP_PROTOCOL') && define('APP_PROTOCOL', 'http://');
/**
 * 定义应用的标题
 */
!defined('APP_TITLE') && define('APP_TITLE', '信信通');
/**
 * 定义应用的logo
 */
!defined('APP_LOGO') && define('APP_LOGO', '/static/img/logo.png');
/**
 * 定义应用登录注册页的bannner图
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
 * 异步执行后台任务
 */
!defined('ASYNC_DAEMON_TASKS') && define('ASYNC_DAEMON_TASKS', true);
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
/* 应用程序起始目录 */
define('TMS_APP_DIR', dirname(__FILE__));
/* 应用程序视图名称，起始路径为：TMS_APP_DIR.'/views/'.TMS_APP_VIEW_NAME */
!defined('TMS_APP_VIEW_NAME_DEFAULT') && define('TMS_APP_VIEW_NAME_DEFAULT', 'default');
!defined('TMS_APP_VIEW_NAME_NOVICE') && define('TMS_APP_VIEW_NAME_NOVICE', 'novice');
!defined('TMS_APP_VIEW_NAME') && define('TMS_APP_VIEW_NAME', TMS_APP_VIEW_NAME_DEFAULT);
/* 应用程序模版起始目录 */
define('TMS_APP_TEMPLATE_DEFAULT', dirname(__FILE__) . '/_template');
!defined('TMS_APP_TEMPLATE') && define('TMS_APP_TEMPLATE', TMS_APP_TEMPLATE_DEFAULT);
/**
 * 限制上传文件最大值 ，单位 M ，0不限制
 */
!defined('TMS_UPLOAD_FILE_MAXSIZE') && define('TMS_UPLOAD_FILE_MAXSIZE', 0);
/**
 * 限制上传文件类型, 空为不限制  多个用 “,” 号隔开
 * 如：'doc,xls'
 */
!defined('TMS_UPLOAD_FILE_CONTENTTYPE_WHITE') && define('TMS_UPLOAD_FILE_CONTENTTYPE_WHITE', '');
/**
 * app's uri.
 */
!defined('TMS_APP_URI') && define('TMS_APP_URI', '');
/**
 * 万能登录验证码
 */
!defined('LOGIN_MASTER_VERIFY_CODE') && define('LOGIN_MASTER_VERIFY_CODE', '');
/**
 * 校验密码强度，0 不校验，1 校验
 */
!defined('TMS_APP_PASSWORD_STRENGTH_CHECK') && define('TMS_APP_PASSWORD_STRENGTH_CHECK', 1);
/**
 * 注册等级检测，0 不检查，9 关闭注册
 */
!defined('TMS_APP_REGISTER_CHECK_LEVEL') && define('TMS_APP_REGISTER_CHECK_LEVEL', 0);
/**
 * 身份验证检测标准 0 不检查， 1 检查(登录注册页只能由https协议打开，登录注册处理函数只接受来自https的请求)
 */
!defined('TMS_APP_AUTH_HTTPS_CHECK') && define('TMS_APP_AUTH_HTTPS_CHECK', 0);
/**
 * 用户口令输入错误最大次数； 0 不限制错误次数
 */
!defined('TMS_APP_PASSWORD_ERROR_MAXNUM') && define('TMS_APP_PASSWORD_ERROR_MAXNUM', 0);
/**
 * 用户口令输入错误超限后禁止登录的时间（分钟），如果值 <= 0 以默认30分钟为准
 */
!defined('TMS_APP_PASSWORD_ERROR_AUTHLOCK_EXPIRE') && define('TMS_APP_PASSWORD_ERROR_AUTHLOCK_EXPIRE', 30);
/**
 * 用户无操作时间限制。 0 为不限制 单位 分
 */
!defined('TMS_APP_NOHOOK_MAXTIME') && define('TMS_APP_NOHOOK_MAXTIME', 0);
/**
 * 是否对请求数据进行过滤
 */
!defined('TMS_APP_REQUEST_DATA_ESCAPE') && define('TMS_APP_REQUEST_DATA_ESCAPE', 1);
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
define('TMS_UPLOAD_DIR', 'kcfinder/upload/');
/**
 * 用户上传文件存储位置
 */
!defined('APP_FS_USER') && define('APP_FS_USER', 'local');
/**
 * 支持微信录音转码（amr->mp3）
 */
!defined('WX_VOICE_AMR_2_MP3') && define('WX_VOICE_AMR_2_MP3', 'N');
/**
 * 设置默认数学计算精度
 */
!defined('APP_TMS_BCSCALE') && define('APP_TMS_BCSCALE', 2);
/**
 * mysql int类型的最大值
 */
define('MYSQL_INT_MAX', 2147483647);
