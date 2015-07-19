<?php
require_once '../../db.php';

$sql = array();
$sql[] = "drop table if exists xxt_mpreceive_log";
$sql[] = "drop table if exists xxt_mpsend_log";
$sql[] = "drop table if exists xxt_matter_read_log";
$sql[] = "drop table if exists xxt_shareaction_log";
$sql[] = "drop table if exists xxt_tmplmsg_log";
$sql[] = "drop table if exists xxt_user_action_log";
$sql[] = "drop table if exists xxt_matter_action_log";
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
