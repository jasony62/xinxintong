<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "DROP TABLE xxt_article_channel";
$sqls[] = "DROP TABLE xxt_link_channel";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}

echo "end update ".__FILE__.PHP_EOL;
