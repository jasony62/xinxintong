<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "RENAME TABLE  `xxt`.`xxt_mpreceive_log` TO  `xxt`.`xxt_log_mpreceive`";
$sqls[] = "RENAME TABLE  `xxt`.`xxt_mpsend_log` TO  `xxt`.`xxt_log_mpsend`";
$sqls[] = "RENAME TABLE  `xxt`.`xxt_matter_read_log` TO  `xxt`.`xxt_log_matter_read`";
$sqls[] = "RENAME TABLE  `xxt`.`xxt_shareaction_log` TO  `xxt`.`xxt_log_matter_share`";
$sqls[] = "RENAME TABLE  `xxt`.`xxt_tmplmsg_log` TO  `xxt`.`xxt_log_tmplmsg`";
$sqls[] = "RENAME TABLE  `xxt`.`xxt_user_action_log` TO  `xxt`.`xxt_log_user_action`";
$sqls[] = "RENAME TABLE  `xxt`.`xxt_matter_action_log` TO  `xxt`.`xxt_log_matter_action`";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
