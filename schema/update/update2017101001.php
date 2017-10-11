<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "update xxt_mission_matter m,xxt_enroll e set m.start_at=e.start_at,m.end_at=e.end_at where m.matter_id=e.id and m.matter_type='enroll'";
$sqls[] = "update xxt_mission_matter m,xxt_signin s set m.start_at=s.start_at,m.end_at=s.end_at where m.matter_id=s.id and m.matter_type='signin'";
$sqls[] = "update xxt_mission_matter m,xxt_group g set m.start_at=g.start_at,m.end_at=g.end_at where m.matter_id=g.id and m.matter_type='group'";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;