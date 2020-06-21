<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE xxt_mission_matter change creater creator varchar(40) not null";
$sqls[] = "ALTER TABLE xxt_mission_matter change creater_name creator_name varchar(255) not null default ''";
//
$sqls[] = "ALTER TABLE xxt_article add start_at int not null default 0 after title";
$sqls[] = "ALTER TABLE xxt_article drop target_mps";
$sqls[] = "ALTER TABLE xxt_link add start_at int not null default 0 after title";
$sqls[] = "ALTER TABLE xxt_channel add start_at int not null default 0 after title";

//
foreach ($sqls as $sql) {
  if (!$mysqli->query($sql)) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'database error: ' . $mysqli->error;
  }
}

echo "end update " . __FILE__ . PHP_EOL;
