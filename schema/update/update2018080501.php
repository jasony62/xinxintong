<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE xxt_enroll_page drop user_schemas";
$sqls[] = "ALTER TABLE xxt_signin_page drop user_schemas";
//
$sqls[] = "ALTER TABLE xxt_log_user_matter drop mpid";
$sqls[] = "ALTER TABLE xxt_log_user_matter drop openid";
$sqls[] = "ALTER TABLE xxt_log_user_matter drop last_action_at";
$sqls[] = "ALTER TABLE xxt_log_user_matter drop read_num";
$sqls[] = "ALTER TABLE xxt_log_user_matter drop share_friend_num";
$sqls[] = "ALTER TABLE xxt_log_user_matter drop share_timeline_num";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;