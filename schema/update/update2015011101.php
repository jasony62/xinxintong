<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "RENAME TABLE  `xxt`.`xxt_ab_org` TO  `xxt`.`xxt_ab_dept`";
$sqls[] = "RENAME TABLE  `xxt`.`xxt_ab_person_org` TO  `xxt`.`xxt_ab_person_dept`";
$sqls[] = "ALTER TABLE `xxt_ab_dept` DROP `out_of_date`";
$sqls[] = "ALTER TABLE  `xxt_ab_dept` CHANGE  `superior_id`  `pid` INT( 11 ) NOT NULL DEFAULT  '0'";
$sqls[] = "ALTER TABLE  `xxt_ab_dept` ADD  `seq` INT NOT NULL DEFAULT  '0' AFTER  `pid`";
$sqls[] = "ALTER TABLE  `xxt_ab_dept` ADD  `fullpath` TEXT NOT NULL AFTER  `seq`";
$sqls[] = "update xxt_ab_dept set fullpath=id";
$sqls[] = "ALTER TABLE  `xxt_ab_person_dept` CHANGE  `org_id`  `dept_id` INT( 11 ) NOT NULL DEFAULT  '0'";
$sqls[] = "ALTER TABLE `xxt_activity` DROP `unenroll_state_ele`";
$sqls[] = "ALTER TABLE  `xxt_activity` ADD  `enroll_js` TEXT NOT NULL AFTER  `enroll_ele`";

foreach ($sqls as $sql) {
    if (!mysql_query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.mysql_error();
    }
}
echo "end update ".__FILE__.PHP_EOL;
