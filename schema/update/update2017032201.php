<?php
require_once '../../db.php';

$sqls = array();

$sqls[] ="ALTER TABLE  `xxt_log_matter_op` ADD  `top` ENUM(  '0',  '1' ) NOT NULL DEFAULT  '0' COMMENT  '置顶' AFTER  `id`";

foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;