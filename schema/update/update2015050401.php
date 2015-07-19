<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "RENAME TABLE `xxt`.`xxt_text_call_reply` TO `xxt`.`xxt_call_text`";
$sqls[] = "RENAME TABLE `xxt`.`xxt_menu_reply` TO `xxt`.`xxt_call_menu`";
$sqls[] = "RENAME TABLE `xxt`.`xxt_qrcode_call_reply` TO `xxt`.`xxt_call_qrcode`";
$sqls[] = "RENAME TABLE `xxt`.`xxt_other_call_reply` TO `xxt`.`xxt_call_other`";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;