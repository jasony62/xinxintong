<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE  `xxt_article_tag` ADD  `siteid` VARCHAR( 32 ) NOT NULL default '' COMMENT  'siteid' FIRST";
$sqls[] = "UPDATE  `xxt_article_tag` SET siteid = mpid";
$sqls[] = "ALTER TABLE  `xxt_tag` ADD  `siteid` VARCHAR( 32 ) NOT NULL default '' AFTER  `id`";
$sqls[] = "UPDATE  `xxt_tag` SET siteid = mpid";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;