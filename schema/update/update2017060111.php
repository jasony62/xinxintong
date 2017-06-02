<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_mission_matter add matter_title varchar(70) not null after matter_type";
$sqls[] = "update xxt_mission_matter m set matter_title = (select title from xxt_article a where a.id = m.matter_id) where m.matter_type = 'article'";
$sqls[] = "update xxt_mission_matter m set matter_title = (select title from xxt_enroll e where e.id = m.matter_id) where m.matter_type = 'enroll'";
$sqls[] = "update xxt_mission_matter m set matter_title = (select title from xxt_group g where g.id = m.matter_id) where m.matter_type = 'group'";
$sqls[] = "update xxt_mission_matter m set matter_title = (select title from xxt_wall w where w.id = m.matter_id) where m.matter_type = 'wall'";
$sqls[] = "update xxt_mission_matter m set matter_title = (select title from xxt_signin s where s.id = m.matter_id) where m.matter_type = 'signin'";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;