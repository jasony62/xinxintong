<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE xxt_article add pic2 text null after pic";
$sqls[] = "ALTER TABLE xxt_link add pic2 text null after pic";
$sqls[] = "ALTER TABLE xxt_channel add pic2 text null after pic";
$sqls[] = "ALTER TABLE xxt_mission add pic2 text null after pic";
$sqls[] = "ALTER TABLE xxt_enroll add pic2 text null after pic";
$sqls[] = "ALTER TABLE xxt_signin add pic2 text null after pic";
$sqls[] = "ALTER TABLE xxt_group add pic2 text null after pic";
//
foreach ($sqls as $sql) {
  if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: ' . $mysqli->error;
  }
}

echo "end update " . __FILE__ . PHP_EOL;
