<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE xxt_enroll drop creater_src";
$sqls[] = "ALTER TABLE xxt_enroll drop modifier_src";

$sqls[] = "ALTER TABLE xxt_signin drop creater_src";
$sqls[] = "ALTER TABLE xxt_signin drop modifier_src";
$sqls[] = "ALTER TABLE xxt_group drop creater_src";
$sqls[] = "ALTER TABLE xxt_group drop modifier_src";
$sqls[] = "ALTER TABLE xxt_lottery drop creater_src";
$sqls[] = "ALTER TABLE xxt_lottery drop modifier_src";
//
$sqls[] = "ALTER TABLE xxt_article drop creater_src";
$sqls[] = "ALTER TABLE xxt_article drop modifier_src";
$sqls[] = "ALTER TABLE xxt_link drop creater_src";
$sqls[] = "ALTER TABLE xxt_link drop modifier_src";
$sqls[] = "ALTER TABLE xxt_text drop creater_src";
$sqls[] = "ALTER TABLE xxt_text drop modifier_src";
$sqls[] = "ALTER TABLE xxt_news drop creater_src";
$sqls[] = "ALTER TABLE xxt_news drop modifier_src";
$sqls[] = "ALTER TABLE xxt_channel drop creater_src";
$sqls[] = "ALTER TABLE xxt_channel drop modifier_src";
$sqls[] = "ALTER TABLE xxt_channel_matter drop creater_src";
//
$sqls[] = "ALTER TABLE xxt_merchant_product drop creater_src";
//
$sqls[] = "ALTER TABLE xxt_mission drop creater_src";
$sqls[] = "ALTER TABLE xxt_mission drop modifier_src";
$sqls[] = "ALTER TABLE xxt_mission_matter drop creater_src";
//
$sqls[] = "ALTER TABLE xxt_site_matter drop creater_src";
$sqls[] = "ALTER TABLE xxt_wall drop creater_src";
$sqls[] = "ALTER TABLE xxt_wall drop modifier_src";
//
$sqls[] = "ALTER TABLE xxt_log_tmplmsg_batch drop creater_src";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;