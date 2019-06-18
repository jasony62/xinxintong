<?php
require_once '../../db.php';

$sqls = [];
//
$sqls[] = "ALTER TABLE xxt_article add is_markdown char(1) not null default 'N' after body";
$sqls[] = "ALTER TABLE xxt_article add body_md longtext null after is_markdown";
//
foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: ' . $mysqli->error;
    }
}

echo "end update " . __FILE__ . PHP_EOL;