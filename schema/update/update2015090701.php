<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE `xxt_lottery_log`  drop PRIMARY KEY";
$sqls[] = "ALTER TABLE `xxt_lottery_log`  ADD `id` INT NOT NULL AUTO_INCREMENT FIRST,  ADD PRIMARY KEY  (`id`)";
$sqls[] = "ALTER TABLE `xxt_lottery_log`  drop mid";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
echo "end update " . __FILE__ . PHP_EOL;