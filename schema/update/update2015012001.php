<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE  `xxt_article` ADD  `can_share` CHAR( 1 ) NOT NULL DEFAULT  'Y' AFTER  `can_picviewer`";
$sqls[] = "ALTER TABLE  `xxt_mpaccount` ADD  `wx_jsapi_ticket` TEXT NOT NULL AFTER  `wx_token_expire_at`";
$sqls[] = "ALTER TABLE  `xxt_mpaccount` ADD  `wx_jsapi_ticket_expire_at` INT NOT NULL AFTER  `wx_jsapi_ticket`";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
