<?php
require_once '../../db.php';

$sqls = [];
$sqls[] = "alter table xxt_site_admin add urole char(1) not null default 'A' after ulabel";
$sqls[] = "INSERT INTO xxt_site_admin(siteid,uid,ulabel,urole,creater,creater_name,create_at) SELECT id,creater,creater_name,'O',creater,creater_name,create_at FROM xxt_site";
$sqls[] = "insert into xxt_mission_acl(siteid,mission_id,title,summary,pic,creater,creater_name,create_at,inviter,inviter_label,invite_at,coworker,coworker_label,coworker_role,join_at,last_invite) select m.siteid,m.id,m.title,m.summary,m.pic,m.creater,m.creater_name,m.create_at,m.creater,m.creater_name,m.create_at,s.creater,s.creater_name,'A',m.create_at,'Y' from xxt_mission m,xxt_site s where m.siteid=s.id and not exists(select 1 from xxt_mission_acl a where m.id=a.mission_id and s.creater=a.coworker)";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;