<?php
require_once '../../db.php';

$sqls = array();
//$sqls[] = "RENAME TABLE  `xxt`.`xxt_templatemsg_log` TO  `xxt`.`xxt_tmplmsg_log`";
//$sqls[] = "ALTER TABLE `xxt_tmplmsg_log` DROP `src`";
$sqls[] = "drop table xxt_templatemsg_log";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
