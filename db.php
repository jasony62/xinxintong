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
	$mysqli = new mysqli($host, $user, $pwd, $dbname, $port);
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
	$mysqli_w = new mysqli($host_w, $user_w, $pwd_w, $dbname_w, $port_w);
	if ($mysqli_w->connect_errno) {
		header('Content-Type: text/plain; charset=utf-8');
		die("数据库连接失败: (" . $mysqli_w->connect_errno . ") " . $mysqli_w->connect_error);
	}
	$mysqli_w->query("SET NAMES UTF8");
} else if (defined('SAE_MYSQL_HOST_M')) {
	/**
	 * 缺省部署在sae
	 */
	$host = SAE_MYSQL_HOST_M;
	$port = SAE_MYSQL_PORT;
	$user = SAE_MYSQL_USER;
	$pwd = SAE_MYSQL_PASS;
	$dbname = SAE_MYSQL_DB;
	/* 读连接 */
	$mysqli = new mysqli($host, $user, $pwd, $dbname, $port);
	if ($mysqli->connect_errno) {
		header('Content-Type: text/plain; charset=utf-8');
		die("数据库连接失败: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
	}
	$mysqli->query("SET NAMES UTF8");
	/* 写连接 */
	$mysqli_w = &$mysqli;
} else {
	header('Content-Type: text/plain; charset=utf-8');
	die('无法获得数据库连接参数');
}