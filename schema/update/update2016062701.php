<?php
require_once '../../db.php';
$sqls = array();
//
$sqls[] = "alter table xxt_log_matter_op add matter_scenario varchar(255) not null default '' after matter_pic";
$sqls[] = "update xxt_log_matter_op l,xxt_enroll e set l.matter_scenario=e.scenario where l.matter_type='enroll' and l.matter_id=e.id";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;