<?php
require_once '../../db.php';
$sqls = array();
//
$sqls[] = "alter table xxt_mission_acl add coworker_role char(1) not null default 'C' after coworker_label";
$sqls[] = "update xxt_mission_acl set coworker_role='O'";
$sqls[] = "insert into xxt_mission_acl(siteid,mission_id,title,summary,pic,creater,create_at,inviter,invite_at,coworker,coworker_role,join_at,state) select m.siteid,m.id,m.title,m.summary,m.pic,m.creater,m.create_at,m.creater,m.create_at,a.uid,'A',m.create_at,m.state from xxt_mission m,xxt_site_admin a where m.siteid=a.siteid";
$sqls[] = "update xxt_mission_acl ma,account a set ma.inviter_label=a.email where ma.inviter=a.uid and ma.coworker_role='A'";
$sqls[] = "update xxt_mission_acl ma,account a set ma.coworker_label=a.email where ma.coworker=a.uid and ma.coworker_role='A'";
//
$sqls[] = "alter table xxt_site_admin add ulabel varchar(255) not null after uid";
$sqls[] = "update xxt_site_admin sa,account a set sa.ulabel=a.email where sa.uid=a.uid";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;