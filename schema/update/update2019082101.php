<?php
require_once '../../db.php';

$sqls = [];
// 全局事物表
$sql = "create table if not exists tms_transaction(";
$sql .= "id bigint not null auto_increment";
$sql .= ",begin_at double(13,3) not null";
$sql .= ",end_at double(13,3) not null default 0";
$sql .= ",request_uri text null";
$sql .= ",user_agent text null";
$sql .= ",referer text null";
$sql .= ",remote_addr text null";
$sql .= ",userid varchar(40) not null default ''";
$sql .= ",primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$sqls[] = $sql;
//
$sqls[] = "ALTER TABLE xxt_enroll_log add g_transid bigint not null default 0 after id";
$sqls[] = "ALTER TABLE xxt_enroll_log add coin_event tinyint not null default 0 after target_type";
$sqls[] = "ALTER TABLE xxt_enroll_log add owner_coin_event tinyint not null default 0 after owner_nickname";
$sqls[] = "ALTER TABLE xxt_enroll_log add state tinyint not null default 1";
$sqls[] = "ALTER TABLE xxt_enroll_log add reset_at int not null default 0";
$sqls[] = "ALTER TABLE xxt_enroll_log add reset_event_id int not null default 0";
//
$sqls[] = "ALTER TABLE xxt_log_matter_op add g_transid bigint not null default 0 after id";
$sqls[] = "ALTER TABLE xxt_log_matter_op change id id bigint not null auto_increment";
//
foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: ' . $mysqli->error;
    }
}

echo "end update " . __FILE__ . PHP_EOL;