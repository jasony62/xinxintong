<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "alter table xxt_enroll drop column nonfans_alert";
$sqls[] = "alter table xxt_enroll drop column entry_page";
$sqls[] = "alter table xxt_enroll drop column wxyx_only";
$sqls[] = "alter table xxt_enroll drop column fans_only";
$sqls[] = "alter table xxt_enroll drop column fans_enter_only";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
