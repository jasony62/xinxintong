<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE xxt_log_user_action drop mpid";
$sqls[] = "ALTER TABLE xxt_log_user_action drop vid";
$sqls[] = "ALTER TABLE xxt_log_user_action drop openid";
$sqls[] = "delete from xxt_log_user_action where userid=''";
$sqls[] = "update xxt_site_account a inner join (select userid,sum(act_read) rn from xxt_log_user_action group by userid) l on a.uid=l.userid set a.read_num=l.rn";
$sqls[] = "update xxt_site_account a inner join (select userid,sum(act_share_friend) fn from xxt_log_user_action group by userid) l on a.uid=l.userid set a.share_friend_num=l.fn";
$sqls[] = "update xxt_site_account a inner join (select userid,sum(act_share_timeline) tn from xxt_log_user_action group by userid) l on a.uid=l.userid set a.share_timeline_num=l.tn";
$sqls[] = "update xxt_site_account a inner join (select userid,max(action_at) at from xxt_log_user_action group by userid) l on a.uid=l.userid set a.last_active=l.at";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;