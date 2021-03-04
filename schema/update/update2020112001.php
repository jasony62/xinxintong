<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE account change nickname nickname varchar(255) default null";
$sqls[] = "ALTER TABLE xxt_site_account change nickname nickname varchar(255) default null";
$sqls[] = "ALTER TABLE xxt_site_favor change nickname nickname varchar(255) default null";
//
foreach ($sqls as $sql) {
  if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: ' . $mysqli->error;
  }
}

echo "end update " . __FILE__ . PHP_EOL;
