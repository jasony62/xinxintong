<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE `xxt_channel` ADD `filter_by_matter_acl` CHAR(1) NOT NULL DEFAULT 'N'";
$sqls[] = "ALTER TABLE `xxt_channel` ADD `show_pic_in_page` CHAR(1) NOT NULL DEFAULT 'Y'";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
