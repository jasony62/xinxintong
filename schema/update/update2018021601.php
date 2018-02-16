<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "DROP TABLE xxt_contribute";
$sqls[] = "DROP TABLE xxt_contribute_user";
$sqls[] = "DROP TABLE xxt_article_review_log";
$sqls[] = "DROP TABLE xxt_news_review_log";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;