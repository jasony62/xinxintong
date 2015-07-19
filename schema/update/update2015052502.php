<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "ALTER TABLE `xxt_article` ADD `read_num` INT NOT NULL DEFAULT '0' AFTER `remark_notice_all`";
$sqls[] = "update xxt_article a set a.read_num=(select count(*) from xxt_log_matter_read l where l.matter_type='article' and a.id=l.matter_id group by l.matter_id) where a.read_num=0";
//
foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
