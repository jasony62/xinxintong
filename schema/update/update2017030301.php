<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_mission_matter drop primary key";
$sqls[] = "alter table xxt_mission_matter add id int not null auto_increment first, add primary key(id)";
$sqls[] = "alter table xxt_mission_matter add is_public char(1) not null default 'Y' after matter_type";
$sqls[] = "alter table xxt_mission_matter change seq seq int not null default 65535";
$sqls[] = "update xxt_mission_matter set seq=65535 where seq=0";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;