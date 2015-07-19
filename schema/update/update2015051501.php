<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "insert into xxt_enroll_page(mpid,aid,creater,create_at,type,title,name,code_id,share_page) select mpid,id,creater,create_at,'V','查看结果页',concat('z',create_at),result_code_id,'N' from xxt_enroll";
$sqls[] = "ALTER TABLE `xxt_enroll` DROP `result_code_id`";
$sqls[] = "ALTER TABLE `xxt_enroll` CHANGE `fans_only` `fans_only` CHAR(1) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'Y'";
$sqls[] = "ALTER TABLE `xxt_enroll_page` ADD `share_summary` VARCHAR(240) NOT NULL";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
