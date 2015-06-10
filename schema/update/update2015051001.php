<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "update xxt_article a set score=(SELECT COUNT(*) FROM xxt_article_score s where a.id=s.article_id)"; 
$sqls[] = "ALTER TABLE  `xxt_article` ADD  `remark_num` INT NOT NULL";
$sqls[] = "UPDATE xxt_article a SET remark_num=(SELECT COUNT(*) FROM xxt_article_remark r WHERE a.id=r.article_id)";
$sqls[] = "ALTER TABLE  `xxt_channel` ADD  `matter_type` VARCHAR( 20 ) NOT NULL AFTER  `fixed_title`";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
