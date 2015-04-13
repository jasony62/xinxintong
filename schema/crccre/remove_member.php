<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "delete from xxt_member where mpid='acb98ae744dc305b8dc51c857982452f' and authed_identity='a-jinfeng'";
$sqls[] = "delete from xxt_member where mpid='acb98ae744dc305b8dc51c857982452f' and authed_identity='a-xiexuhua'";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}

echo 'end delete';
