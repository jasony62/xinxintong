<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE xxt_enroll_record_remark add g_transid bigint not null default 0 after id";
$sqls[] = "ALTER TABLE xxt_enroll_log add owner_group_id varchar(32) not null default '' after earn_coin";
//
foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: ' . $mysqli->error;
    }
}

echo "end update " . __FILE__ . PHP_EOL;