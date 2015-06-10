<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE `xxt_article` DROP `can_carousel`";
$sqls[] = "ALTER TABLE  `xxt_article` ADD  `remark_notice_all` CHAR( 1 ) NULL DEFAULT  'N' AFTER  `remark_notice`";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
