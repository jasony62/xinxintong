<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE xxt_article drop mpid";
$sqls[] = "ALTER TABLE xxt_link drop mpid";
$sqls[] = "ALTER TABLE xxt_text drop mpid";
$sqls[] = "ALTER TABLE xxt_channel drop mpid";
$sqls[] = "ALTER TABLE xxt_tmplmsg drop mpid";
$sqls[] = "ALTER TABLE xxt_matter_acl drop mpid";
//
$sqls[] = "ALTER TABLE xxt_log_matter_read drop mpid";
$sqls[] = "ALTER TABLE xxt_log_matter_share drop mpid";
$sqls[] = "ALTER TABLE xxt_log_tmplmsg drop mpid";
$sqls[] = "ALTER TABLE xxt_log_user_action drop mpid";
$sqls[] = "ALTER TABLE xxt_log_matter_action drop mpid";
$sqls[] = "ALTER TABLE xxt_log_timer drop mpid";
$sqls[] = "ALTER TABLE xxt_matter_download_log drop mpid";
//
$sqls[] = "ALTER TABLE xxt_log_mpreceive change mpid siteid varchar(32) not null";
$sqls[] = "ALTER TABLE xxt_log_mpsend change mpid siteid varchar(32) not null";
$sqls[] = "ALTER TABLE xxt_log_massmsg change mpid siteid varchar(32) not null";
//
$sql[] = "drop table if exists xxt_log_mpa";
$sql[] = "drop table if exists xxt_news";
//
foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: ' . $mysqli->error;
    }
}

echo "end update " . __FILE__ . PHP_EOL;