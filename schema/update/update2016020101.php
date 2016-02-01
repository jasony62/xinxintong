<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_enroll add modifier varchar(40) not null default '' after create_at";
$sqls[] = "alter table xxt_enroll add modifier_name varchar(255) not null default '' after modifier";
$sqls[] = "alter table xxt_enroll add modifier_src char(1) after modifier_name";
$sqls[] = "alter table xxt_enroll add modify_at int not null after modifier_src";
$sqls[] = "update xxt_enroll set modifier=creater,modifier_name=creater_name,modifier_src=creater_src,modify_at=create_at";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;