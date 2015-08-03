<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE xxt_channel add creater_name varchar(255) not null default '' after create_at";
$sqls[] = "ALTER TABLE xxt_channel add creater_src char(1) not null default 'A' after creater_name";
$sqls[] = "update xxt_channel c,account a set c.creater_name=a.nickname where c.creater=a.uid";
$sqls[] = "ALTER TABLE xxt_news add creater_name varchar(255) not null default '' after create_at";
$sqls[] = "ALTER TABLE xxt_news add creater_src char(1) not null default 'A' after creater_name";
$sqls[] = "update xxt_news n,account a set n.creater_name=a.nickname where n.creater=a.uid";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
