<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "ALTER TABLE  `xxt_article` ADD  `page_id` INT NOT NULL DEFAULT  '0' AFTER  `css`";
$sqls[] = "ALTER TABLE  `xxt_lottery` ADD  `custom_body` CHAR( 1 ) NOT NULL DEFAULT  'N' AFTER  `extra_js`";
$sqls[] = "ALTER TABLE  `xxt_lottery` ADD  `page_id` INT NOT NULL DEFAULT  '0' AFTER  `custom_body`";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
