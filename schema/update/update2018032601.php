<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "alter table xxt_article_attachment RENAME TO xxt_matter_attachment";
$sqls[] = "ALTER TABLE xxt_matter_attachment change article_id matter_id varchar(40) not null";
$sqls[] = "ALTER TABLE xxt_matter_attachment add matter_type char(20) not null after matter_id";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}

echo "end update " . __FILE__ . PHP_EOL;