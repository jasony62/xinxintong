<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "update xxt_log_matter_read r,xxt_link m set r.matter_title=m.title where r.matter_type='link' and r.matter_id=m.id";
$sqls[] = "update xxt_log_matter_read r,xxt_lottery m set r.matter_title=m.title where r.matter_type='lottery' and r.matter_id=m.id";
$sqls[] = "update xxt_log_matter_read r,xxt_channel m set r.matter_title=m.title where r.matter_type='channel' and r.matter_id=m.id";
$sqls[] = "update xxt_log_matter_read r,xxt_news m set r.matter_title=m.title where r.matter_type='news' and r.matter_id=m.id";
$sqls[] = "update xxt_log_matter_read r,xxt_addressbook m set r.matter_title=m.title where r.matter_type='addressbook' and r.matter_id=m.id";
$sqls[] = "update xxt_log_matter_action l,xxt_link m set l.matter_title=m.title where l.matter_type='link' and l.matter_id=m.id";
$sqls[] = "update xxt_log_matter_action l,xxt_lottery m set l.matter_title=m.title where l.matter_type='lottery' and l.matter_id=m.id";
$sqls[] = "update xxt_log_matter_action l,xxt_channel m set l.matter_title=m.title where l.matter_type='channel' and l.matter_id=m.id";
$sqls[] = "update xxt_log_matter_action l,xxt_news m set l.matter_title=m.title where l.matter_type='news' and l.matter_id=m.id";
$sqls[] = "update xxt_log_matter_action l,xxt_addressbook m set l.matter_title=m.title where l.matter_type='address' and l.matter_id=m.id";
$sqls[] = "update xxt_log_matter_share s,xxt_lottery m set s.matter_title=m.title where s.matter_type='lottery' and s.matter_id=m.id";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
