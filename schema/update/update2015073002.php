<?php
require_once '../../db.php';

$sqls = array();
$sqls[] = "create table xxt_log_enroll_stat(mid varchar(40),r int,f int,t int)";
$sqls[] = "insert into xxt_log_enroll_stat(mid,r,f,t) select matter_id,sum(act_read),sum(act_share_friend),sum(act_share_timeline) from xxt_log_matter_action where matter_type='enroll' group by matter_id";
$sqls[] = "update xxt_enroll e,xxt_log_enroll_stat s set e.read_num=s.r,e.share_friend_num=s.f,e.share_timeline_num=s.t where e.id=s.mid";
$sqls[] = "drop table xxt_log_enroll_stat";

foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
