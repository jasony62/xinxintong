<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "update xxt_log_matter_action a,xxt_log_matter_read r set a.matter_id=r.matter_id where a.act_read=1 and a.original_logid=r.id";
$sqls[] = "update xxt_log_matter_action a,xxt_log_matter_share f set a.matter_id=f.matter_id where a.act_share_friend=1 and f.share_to='F' and a.original_logid=f.id";
$sqls[] = "update xxt_log_matter_action a,xxt_log_matter_share t set a.matter_id=t.matter_id where a.act_share_timeline=1 and t.share_to='T' and a.original_logid=t.id";
$sqls[] = "alter table xxt_log_matter_read add column matter_title varchar(70) not null after matter_type";
$sqls[] = "alter table xxt_log_matter_share add column matter_title varchar(70) not null after matter_type";
$sqls[] = "alter table xxt_log_matter_action add column matter_title varchar(70) not null after matter_type";
$sqls[] = "update xxt_log_matter_read r,xxt_article a set r.matter_title=a.title where r.matter_type='article' and r.matter_id=a.id";
$sqls[] = "update xxt_log_matter_share s,xxt_article a set s.matter_title=a.title where s.matter_type='article' and s.matter_id=a.id";
$sqls[] = "update xxt_log_matter_action l,xxt_article a set l.matter_title=a.title where l.matter_type='article' and l.matter_id=a.id";
$sqls[] = "update xxt_log_matter_read r,xxt_enroll m set r.matter_title=m.title where r.matter_type='enroll' and r.matter_id=m.id";
$sqls[] = "update xxt_log_matter_share s,xxt_enroll m set s.matter_title=m.title where s.matter_type='enroll' and s.matter_id=m.id";
$sqls[] = "update xxt_log_matter_action l,xxt_enroll m set l.matter_title=m.title where l.matter_type='enroll' and l.matter_id=m.id";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
