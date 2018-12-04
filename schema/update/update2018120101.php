<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE xxt_enroll_record drop wx_openid";
$sqls[] = "ALTER TABLE xxt_enroll_record drop yx_openid";
$sqls[] = "ALTER TABLE xxt_enroll_record drop qy_openid";
$sqls[] = "ALTER TABLE xxt_enroll_record drop headimgurl";
//
$sqls[] = "ALTER TABLE xxt_signin_record drop wx_openid";
$sqls[] = "ALTER TABLE xxt_signin_record drop yx_openid";
$sqls[] = "ALTER TABLE xxt_signin_record drop qy_openid";
$sqls[] = "ALTER TABLE xxt_signin_record drop headimgurl";
//
$sqls[] = "ALTER TABLE xxt_group_player drop wx_openid";
$sqls[] = "ALTER TABLE xxt_group_player drop yx_openid";
$sqls[] = "ALTER TABLE xxt_group_player drop qy_openid";
$sqls[] = "ALTER TABLE xxt_group_player drop headimgurl";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;