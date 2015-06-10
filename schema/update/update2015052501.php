<?php
require_once '../../db.php';

$sqls = array();
//
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'reply_text','N','N','N','N' from xxt_mppermission where permission='reply'";
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'reply_menu','N','N','N','N' from xxt_mppermission where permission='reply'";
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'reply_qrcode','N','N','N','N' from xxt_mppermission where permission='reply'";
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'reply_other','N','N','N','N' from xxt_mppermission where permission='reply'";
//
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'app_enroll','N','N','N','N' from xxt_mppermission where permission='app'";
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'app_lottery','N','N','N','N' from xxt_mppermission where permission='app'";
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'app_wall','N','N','N','N' from xxt_mppermission where permission='app'";
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'app_addressbook','N','N','N','N' from xxt_mppermission where permission='app'";
$sqls[] = "insert into xxt_mppermission(mpid,uid,permission,create_p,read_p,update_p,delete_p) select distinct mpid,uid,'app_contribute','N','N','N','N' from xxt_mppermission where permission='app'";
//
foreach ($sqls as $sql) {
    if (!$mysqli->query($sql)) {
        header('HTTP/1.0 500 Internal Server Error');
        echo 'database error: '.$mysqli->error;
    }
}
echo "end update ".__FILE__.PHP_EOL;
