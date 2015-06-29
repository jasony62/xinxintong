<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "DROP TABLE xxt_ab_title";
$sqls[] = "ALTER TABLE `xxt_ab_person_dept` DROP `title_id`";
$sqls[] = "ALTER TABLE `xxt_ab_person` ADD `remark` TEXT NOT NULL";
$sqls[] = "ALTER TABLE `xxt_ab_person` ADD `tags` TEXT NOT NULL";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
