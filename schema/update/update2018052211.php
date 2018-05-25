<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "UPDATE xxt_home_matter h SET h.pic = (SELECT a.pic FROM xxt_article a WHERE a.id = h.matter_id) WHERE h.matter_type = 'article'";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;