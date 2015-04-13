<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE `xxt_activity` ADD `view_page_id` INT NOT NULL DEFAULT '0' AFTER `result_page_id`";
$sqls[] = "insert into xxt_code_page(creater,create_at,modify_at,title,summary) select creater,create_at,create_at,aid,'view' from xxt_activity";
$sqls[] = "update xxt_activity a,xxt_code_page p set a.view_page_id=p.id where a.aid=p.title and p.summary='view'";
$sqls[] = "update xxt_code_page p,xxt_activity a set p.title=a.title where p.id=a.view_page_id";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
