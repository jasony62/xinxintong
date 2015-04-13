<?php
/**
 * database resource.
 */
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
if (!($link = @mysql_connect("{$host}:{$port}",$user,$pwd,true)))
    die('Connect Database Server Failed: '.mysql_error());

if(!mysql_select_db($dbname,$link))
    die("Select Database Failed: " . mysql_error($link));

mysql_query("SET NAMES UTF8");
