<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE xxt_wall add scenario varchar(255) not null default 'discuss' after end_at";
$sqls[] = "ALTER TABLE xxt_wall add scenario_config text after scenario";
$sqls[] = "ALTER TABLE xxt_wall add interact_matter text after source_app";
$sqls[] = "ALTER TABLE xxt_wall add matters_img text after interact_matter";
$sqls[] = "ALTER TABLE xxt_wall add result_img text after matters_img";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;