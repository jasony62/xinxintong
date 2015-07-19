<?php
require_once '../../db.php';
/*
 * 活动
 */
$sql = array();
$sql[] = 'drop table if exists xxt_activity';
$sql[] = 'drop table if exists xxt_activity_page';
$sql[] = 'drop table if exists xxt_activity_round';
$sql[] = 'drop table if exists xxt_activity_enroll_receiver';
$sql[] = 'drop table if exists xxt_activity_enroll';
$sql[] = 'drop table if exists xxt_activity_cusdata';
$sql[] = 'drop table if exists xxt_activity_enroll_remark';
$sql[] = 'drop table if exists xxt_activity_enroll_score';
$sql[] = 'drop table if exists xxt_activity_lottery';
$sql[] = 'drop table if exists xxt_activity_lottery_round';
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
