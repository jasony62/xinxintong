<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE xxt_group add assigned_nickname text after data_schemas";
$sqls[] = "update xxt_group g,xxt_enroll e set g.assigned_nickname=e.assigned_nickname where g.source_app like '%enroll%' and g.source_app like concat('%',e.id,'%')";
$sqls[] = "update xxt_group g,xxt_signin s set g.assigned_nickname=s.assigned_nickname where g.source_app like '%signin%' and g.source_app like concat('%',s.id,'%')";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;