<?php
require_once '../../db.php';
/*
 * 活动
 */
$sql = array();
$sql[] = 'drop table if exists xxt_address_book';
$sql[] = 'drop table if exists xxt_app_contribute';
/**
 * 执行操作
 */
foreach ($sql as $s) {
    if (!$mysqli->query($s)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}

echo 'finished.';
