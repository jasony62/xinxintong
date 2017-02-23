<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "alter table xxt_log_user_matter add mission_id int not null default 0 after matter_title";
$sqls[] = "alter table xxt_log_user_matter add mission_title varchar(70) not null default '' after mission_id";
$sqls[] = "update xxt_log_user_matter l,xxt_enroll a set l.mission_id=a.mission_id where l.matter_type='enroll' and l.matter_id=a.id and a.mission_id<>''";
$sqls[] = "update xxt_log_user_matter l,xxt_mission m set l.mission_title=m.title where l.mission_id=m.id";
$sqls[] = "insert into xxt_log_user_matter(siteid,matter_id,matter_type,userid,nickname,operation,operate_at,matter_last_op,matter_op_num,user_last_op,user_op_num) SELECT siteid,aid,'signin',userid,nickname,'submit',signin_at,'Y',1,'Y',1 FROM xxt_signin_record where userid<>''";
$sqls[] = "update xxt_log_user_matter l,xxt_signin a set l.matter_title=a.title where l.matter_type='signin' and l.matter_id=a.id";
$sqls[] = "update xxt_log_user_matter l,xxt_signin a set l.mission_id=a.mission_id where l.matter_type='signin' and l.matter_id=a.id and a.mission_id<>''";
$sqls[] = "update xxt_log_user_matter l,xxt_mission m set l.mission_title=m.title where l.mission_id=m.id";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;