<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE xxt_fans add `read_num` int not null default 0";
$sqls[] = "ALTER TABLE xxt_fans add `share_friend_num` int not null default 0";
$sqls[] = "ALTER TABLE xxt_fans add `share_timeline_num` int not null default 0";
$sqls[] = "alter table xxt_log_user_action drop src";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
