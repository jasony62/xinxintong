<?php
file_exists(dirname(__FILE__).'/cus/app.php') && include_once(dirname(__FILE__).'/cus/app.php');

if (defined('SAE_MYSQL_HOST_M')) {
    $host = SAE_MYSQL_HOST_M;
    $port = SAE_MYSQL_PORT;
    $user = SAE_MYSQL_USER;
    $pwd = SAE_MYSQL_PASS;
    $dbname = SAE_MYSQL_DB;
} else {
    $host = 'localhost';
    $port = '3306';
    $user = 'jason';
    $pwd = '123456';
    $dbname = 'xxt';
}

$mysqli = new mysqli("{$host}:{$port}", $user, $pwd, $dbname);
if ($mysqli->connect_errno)
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;

$mysqli->query("SET NAMES UTF8");
