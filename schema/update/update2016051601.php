<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_enroll add mission_id int not null default 0 after pic";
$sqls[] = "alter table xxt_group add mission_id int not null default 0 after pic";
$sqls[] = "alter table xxt_group add scenario varchar(255) not null default '' after mission_id";
$sqls[] = "alter table xxt_group add group_rule text after scenario";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;