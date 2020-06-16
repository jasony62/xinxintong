<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE xxt_site_wx change creater creator varchar(40) not null";
$sqls[] = "ALTER TABLE xxt_site_wx add tms_msg_wx_channel_code varchar(50) not null default ''";
//
foreach ($sqls as $sql) {
  if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: ' . $mysqli->error;
  }
}

echo "end update " . __FILE__ . PHP_EOL;
