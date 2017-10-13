<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE xxt_site_member_schema add start_at int not null default 0 after create_at";
$sqls[] = "ALTER TABLE xxt_site_member_schema add end_at int not null default 0 after start_at";
$sqls[] = "update xxt_site_member_schema set start_at=create_at";
$sqls[] = "insert into xxt_mission_matter(siteid,mission_id,creater,creater_src,create_at,start_at,end_at,matter_id,matter_type,matter_title) select siteid,matter_id,creater,'A',create_at,start_at,end_at,id,'memberschema',title from xxt_site_member_schema where matter_type='mission' and matter_id>0";
$sqls[] = "update xxt_mission_matter m,account a set m.creater_name=a.nickname where m.creater=a.uid and m.matter_type='memberschema'";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;