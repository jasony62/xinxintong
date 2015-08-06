<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_channel add orderby varchar(20) not null default 'time' after bottom_id";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
