<?php
require_once '../../db.php';

$sqls = array();
//
$sql = "alter table xxt_mission_matter drop primary key";
$sql = "alter table xxt_mission_matter add id int not null auto_increment first, add primary key(id)";
$sql = "alter table xxt_mission_matter add is_public char(1) not null default 'Y' after matter_type";
$sql = "alter table xxt_mission_matter change seq seq int not null default 65535";
$sql = "update xxt_mission_matter set seq=65535 where seq=0";
$sqls[] = $sql;
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;