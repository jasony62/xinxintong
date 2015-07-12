<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_article add column author varchar(16) default '' after title";
$sqls[] = "ALTER TABLE xxt_article DROP css";
$sqls[] = "update xxt_article set author=creater_name";
$sqls[] = "alter table xxt_article add column media_id varchar(256) default ''";
$sqls[] = "alter table xxt_article add column uploaded_at int default 0";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
