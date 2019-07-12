<?php
if (file_exists(dirname(__FILE__) . '/cus/db.php')) {
    /**
     * 加载本地化配置
     */
    include_once dirname(__FILE__) . '/cus/db.php';
    /**
     * 缺省数据库连接
     */
    $host = TMS_MYSQL_HOST;
    $port = TMS_MYSQL_PORT;
    $user = TMS_MYSQL_USER;
    $pwd = TMS_MYSQL_PASS;
    $dbname = TMS_MYSQL_DB;
    try {
        $mysqli = new mysqli($host, $user, $pwd, $dbname, $port);
    } catch (Error $e) {
        die('数据库连接异常：' . $e->getMessage());
    }
    if ($mysqli->connect_errno) {
        header('Content-Type: text/plain; charset=utf-8');
        die("数据库连接失败: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
    }
    $mysqli->query("SET NAMES UTF8");
    /**
     * 写数据库连接
     */
    $host_w = defined('TMS_MYSQL_HOST_W') ? TMS_MYSQL_HOST_W : TMS_MYSQL_HOST;
    $port_w = defined('TMS_MYSQL_PORT_W') ? TMS_MYSQL_PORT_W : TMS_MYSQL_PORT;
    $user_w = defined('TMS_MYSQL_USER_W') ? TMS_MYSQL_USER_W : TMS_MYSQL_USER;
    $pwd_w = defined('TMS_MYSQL_PASS_W') ? TMS_MYSQL_PASS_W : TMS_MYSQL_PASS;
    $dbname_w = defined('TMS_MYSQL_DB_W') ? TMS_MYSQL_DB_W : TMS_MYSQL_DB;
    try {
        $mysqli_w = new mysqli($host_w, $user_w, $pwd_w, $dbname_w, $port_w);
    } catch (Error $e) {
        die('数据库连接异常：' . $e->getMessage());
    }
    if ($mysqli_w->connect_errno) {
        header('Content-Type: text/plain; charset=utf-8');
        die("数据库连接失败: (" . $mysqli_w->connect_errno . ") " . $mysqli_w->connect_error);
    }
    $mysqli_w->query("SET NAMES UTF8");
} else {
    header('Content-Type: text/plain; charset=utf-8');
    die('无法获得数据库连接参数');
}