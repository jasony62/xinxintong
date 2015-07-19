<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE  `xxt_shop_matter` ADD  `mpid` VARCHAR( 32 ) NOT NULL AFTER  `put_at`";
$sqls[] = "ALTER TABLE  `xxt_shop_matter` ADD  `title` VARCHAR( 70 ) NOT NULL AFTER  `matter_type`";
$sqls[] = "ALTER TABLE  `xxt_shop_matter` ADD  `pic` TEXT NOT NULL AFTER  `title`";
$sqls[] = "ALTER TABLE  `xxt_shop_matter` ADD  `summary` VARCHAR( 240 ) NOT NULL DEFAULT  '' AFTER  `pic`";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
