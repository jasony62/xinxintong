<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_article add column modifier varchar(40) not null default '' after create_at";
$sqls[] = "alter table xxt_article add column modifier_name varchar(255) not null default '' after modifier";
$sqls[] = "alter table xxt_article add column modifier_src char(1) after modifier_name";
$sqls[] = "update xxt_article set modifier=creater";
$sqls[] = "update xxt_article set modifier_name=creater_name";
$sqls[] = "update xxt_article set modifier_src=creater_src";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
