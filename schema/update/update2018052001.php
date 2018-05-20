<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE xxt_enroll_topic add userid varchar(40) not null after unionid";
$sqls[] = "ALTER TABLE xxt_enroll_topic add group_id varchar(32) not null default '' after userid";
$sqls[] = "ALTER TABLE xxt_enroll_topic add share_in_group char(1) not null default 'N'";
//
$sqls[] = "update xxt_enroll_topic t,xxt_site_account a set t.userid=a.uid where t.siteid=a.siteid and t.unionid=a.unionid and a.is_reg_primary='Y'";
$sqls[] = "update xxt_enroll_topic t,xxt_enroll_user u set t.group_id=u.group_id where t.siteid=u.siteid and t.aid=u.aid and t.userid=u.userid";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;