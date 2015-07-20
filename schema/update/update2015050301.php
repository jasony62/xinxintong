<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "RENAME TABLE  `xxt`.`xxt_activity_enroll_cusdata` TO  `xxt`.`xxt_enroll_record_data`";
$sqls[] = "RENAME TABLE  `xxt`.`xxt_activity_enroll_remark` TO  `xxt`.`xxt_enroll_record_remark`";
$sqls[] = "RENAME TABLE  `xxt`.`xxt_activity_enroll_score` TO  `xxt`.`xxt_enroll_record_score`";
$sqls[] = "RENAME TABLE  `xxt`.`xxt_activity_enroll` TO  `xxt`.`xxt_enroll_record`";
$sqls[] = "RENAME TABLE  `xxt`.`xxt_activity_receiver` TO  `xxt`.`xxt_enroll_receiver`";
$sqls[] = "RENAME TABLE  `xxt`.`xxt_activity_page` TO  `xxt`.`xxt_enroll_page`";
$sqls[] = "RENAME TABLE  `xxt`.`xxt_activity_lottery_round` TO  `xxt`.`xxt_enroll_lottery_round`";
$sqls[] = "RENAME TABLE  `xxt`.`xxt_activity_lottery` TO  `xxt`.`xxt_enroll_lottery`";
$sqls[] = "RENAME TABLE  `xxt`.`xxt_activity` TO  `xxt`.`xxt_enroll`";
$sqls[] = "DELETE FROM `xxt`.`xxt_inner` WHERE `xxt_inner`.`id` = 9";
$sqls[] = "update xxt_inner set name = lcase(name)";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;